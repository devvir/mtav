# A server's first boot (servers.tf puts SERVER, DATA and GATE above this line; what may change, it reads from its
# settings, /mtav/<server>/config, through mtav-reconfigure). Every step can run again. From here on the server is
# reached only through Systems Manager, and changed only by deploys and by mtav-reconfigure.
#
#  0. the data disk: the environments' databases, files and settings live there, apart from the server
#  1. swap, security updates by themselves, services nothing uses stopped
#  2. Docker, publishing on the loopback only, its logs kept small
#  3. the gate deploys go through (deploy/host/gate), and each environment's directory
#  4. the front web server: HTTPS for CloudFront alone, each name to its environment's switch
#  5. its certificate, from Let's Encrypt over DNS, renewed by itself; tried again hourly until it is there
#  6. nightly backups of every environment's database and files, to S3
set -euo pipefail
exec > >(tee -a /var/log/mtav-setup.log) 2>&1
echo "== $(date -u +%FT%TZ) setting up the $SERVER server"
export DEBIAN_FRONTEND=noninteractive

# -- 0. the data disk -------------------------------------------------------------------------------------------------
# Attached just after the server is made: waited for. Formatted only if it holds nothing yet (the first time); after
# that, whatever server mounts it finds its environments as they were. Docker's volumes (each environment's database
# and files) and /mtav (each environment's .env and its releases' records) are folders on it.
disk="/dev/disk/by-id/nvme-Amazon_Elastic_Block_Store_${DATA//-/}"
for _ in $(seq 1 60); do [[ -e "$disk" ]] && break; sleep 5; done
[[ -e "$disk" ]] || { echo "the data disk $DATA never came"; exit 1; }
blkid "$disk" > /dev/null || mkfs.ext4 -q -L mtav-data "$disk"
install -d /srv/mtav-data /var/lib/docker/volumes /mtav
grep -q mtav-data /etc/fstab || cat >> /etc/fstab <<EOF
LABEL=mtav-data /srv/mtav-data ext4 defaults,nofail,x-systemd.device-timeout=5min 0 2
/srv/mtav-data/docker-volumes /var/lib/docker/volumes none bind,nofail,x-systemd.requires-mounts-for=/srv/mtav-data 0 0
/srv/mtav-data/mtav /mtav none bind,nofail,x-systemd.requires-mounts-for=/srv/mtav-data 0 0
EOF
mountpoint -q /srv/mtav-data || mount /srv/mtav-data
install -d /srv/mtav-data/docker-volumes /srv/mtav-data/mtav
mountpoint -q /var/lib/docker/volumes || mount /var/lib/docker/volumes
mountpoint -q /mtav || mount /mtav
# Docker waits for its volumes' folder
install -d /etc/systemd/system/docker.service.d
cat > /etc/systemd/system/docker.service.d/mtav-data.conf <<'EOF'
[Unit]
RequiresMountsFor=/var/lib/docker/volumes /mtav
EOF
systemctl daemon-reload

# -- 1. the machine ---------------------------------------------------------------------------------------------------
hostnamectl set-hostname "mtav-$SERVER"
if [[ ! -f /swapfile ]]; then
    fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    echo 'vm.swappiness=10' > /etc/sysctl.d/90-mtav.conf && sysctl -q -p /etc/sysctl.d/90-mtav.conf
fi
# Docker from Docker's own repository, as on every machine MTAV runs on: Ubuntu's own Compose fails a deploy whose
# workers have their health check switched off, where Docker's waits for them to be running
install -d -m 755 /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
    > /etc/apt/sources.list.d/docker.list
apt-get update -qq
apt-get remove -y -qq docker.io docker-compose-v2 > /dev/null 2>&1 || true
apt-get install -y -qq unattended-upgrades docker-ce docker-ce-cli containerd.io docker-compose-plugin nginx certbot \
    python3-certbot-dns-route53 jq > /dev/null
cat > /etc/apt/apt.conf.d/20auto-upgrades <<'EOF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
EOF
snap install aws-cli --classic > /dev/null 2>&1 || true
for unit in rpcbind.socket rpcbind.service; do systemctl disable --now "$unit" 2> /dev/null || true; done

# -- 2. Docker --------------------------------------------------------------------------------------------------------
cat > /etc/docker/daemon.json <<'EOF'
{
  "ip": "127.0.0.1",
  "log-driver": "json-file",
  "log-opts": { "max-size": "10m", "max-file": "3" }
}
EOF
systemctl daemon-reload
systemctl enable docker.socket docker > /dev/null 2>&1
systemctl reset-failed docker 2> /dev/null || true
systemctl restart docker

# -- 3. the gate, and the server's settings ---------------------------------------------------------------------------
echo "$GATE" | base64 -d | gunzip > /usr/local/sbin/mtav-gate
chown root:root /usr/local/sbin/mtav-gate && chmod 755 /usr/local/sbin/mtav-gate
install -d -m 755 /mtav
install -d -m 700 /etc/mtav
printf "SERVER='%s'\nDATA='%s'\n" "$SERVER" "$DATA" > /etc/mtav/identity
cat > /usr/local/sbin/mtav-reconfigure <<'SCRIPT'
#!/bin/bash
# The server's settings read again (/mtav/<server>/config, kept by infra/aws/main): its environments get their
# directories, its certificate covers every name it answers for, its front web server routes each one. Run at the
# first boot, and whenever an environment moves between servers.
set -euo pipefail
source /etc/mtav/identity
config="$(aws ssm get-parameter --region us-east-2 --name "/mtav/$SERVER/config" --query Parameter.Value --output text | cat)"
[[ -n "$config" ]] || { echo "no settings for $SERVER"; exit 1; }
{ cat /etc/mtav/identity; echo "$config"; } > /etc/mtav/server.next
chmod 600 /etc/mtav/server.next && mv /etc/mtav/server.next /etc/mtav/server
source /etc/mtav/server
for env in $(jq -r 'keys[]' <<< "$ENVIRONMENTS"); do
    # Its .env is written before its first deploy (from /mtav/<server>/env/<environment>)
    install -d -m 750 "/mtav/$env"
done
echo "settings read: $(jq -r 'keys | join(", ")' <<< "$ENVIRONMENTS"); names: $NAMES"
# A certificate already issued grows to the names it does not cover yet; none yet: its hourly try does it
if [[ -f /etc/letsencrypt/live/mtav/fullchain.pem ]]; then
    /usr/local/sbin/mtav-certificate || echo "the certificate is tried again within the hour"
fi
[[ -x /usr/local/sbin/mtav-front ]] && /usr/local/sbin/mtav-front || true
SCRIPT
chmod 755 /usr/local/sbin/mtav-reconfigure

# -- 4. the front web server ------------------------------------------------------------------------------------------
# Written once the certificate is there (step 5): until then nginx serves nothing but its default page, on no name.
cat > /usr/local/sbin/mtav-front <<'SCRIPT'
#!/bin/bash
# Writes the front web server's configuration for this server's environments and reloads it: HTTPS with the
# server's certificate, answering only requests carrying CloudFront's secret header, each name to its environment's
# switch (its pages, and /app to its realtime service). Run after the certificate is issued or renewed.
set -euo pipefail
source /etc/mtav/server
[[ -f /etc/letsencrypt/live/mtav/fullchain.pem ]] || { echo "no certificate yet"; exit 0; }
secret="$(aws ssm get-parameter --region us-east-2 --name "/mtav/$SERVER/origin-secret" --with-decryption --query Parameter.Value --output text)"
conf=/etc/nginx/sites-available/mtav
{
    cat <<EOF
# MTAV (written by mtav-front): HTTPS for CloudFront alone, each name to its environment
server_tokens off;
client_max_body_size 105m;
ssl_certificate /etc/letsencrypt/live/mtav/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/mtav/privkey.pem;
ssl_protocols TLSv1.2 TLSv1.3;

# Whatever name it is asked for, a request without CloudFront's secret gets nothing
server {
    listen 443 ssl http2 default_server;
    server_name _;
    return 444;
}
EOF
    for env in $(jq -r 'keys[]' <<< "$ENVIRONMENTS"); do
        names="$(jq -r --arg e "$env" '.[$e].names | join(" ")' <<< "$ENVIRONMENTS")"
        web="$(jq -r --arg e "$env" '.[$e].web' <<< "$ENVIRONMENTS")"
        ws="$(jq -r --arg e "$env" '.[$e].ws' <<< "$ENVIRONMENTS")"
        cat <<EOF

# $env
server {
    listen 443 ssl http2;
    server_name $names;
    if (\$http_x_origin_verify != "$secret") { return 403; }
    location /app {
        proxy_pass http://127.0.0.1:$ws;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host localhost;
        proxy_buffering off;
        proxy_read_timeout 1h;
    }
    location / {
        proxy_pass http://127.0.0.1:$web;
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$http_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_buffer_size 128k;
        proxy_buffers 4 256k;
        proxy_busy_buffers_size 256k;
        proxy_redirect off;
    }
}
EOF
    done
    if [[ -n "$APEX" ]]; then
        read -r apex to <<< "$APEX"
        cat <<EOF

# The bare domain, to www
server {
    listen 443 ssl http2;
    server_name $apex;
    if (\$http_x_origin_verify != "$secret") { return 403; }
    return 301 https://$to\$request_uri;
}
EOF
    fi
} > "$conf.next"
mv "$conf.next" "$conf"
chmod 640 "$conf"
ln -sfn "$conf" /etc/nginx/sites-enabled/mtav
rm -f /etc/nginx/sites-enabled/default
nginx -t -q && systemctl reload nginx
echo "front web server written for: $(jq -r 'keys | join(", ")' <<< "$ENVIRONMENTS")"
SCRIPT
chmod 755 /usr/local/sbin/mtav-front
# Nothing on port 80: CloudFront speaks HTTPS to the server, and the certificate is proven over DNS
rm -f /etc/nginx/sites-enabled/default
sed -i -E 's/^(\s*listen .*80)/#\1/' /etc/nginx/sites-available/default 2> /dev/null || true
systemctl enable nginx > /dev/null 2>&1
systemctl restart nginx || true

# -- 5. the certificate -----------------------------------------------------------------------------------------------
cat > /usr/local/sbin/mtav-certificate <<'SCRIPT'
#!/bin/bash
# The server's certificate, for every name it answers for, from Let's Encrypt, proven by a TXT record in mtav.uy's
# zone (Route 53). Once issued, certbot's own timer renews it and the hook rewrites the front web server.
set -euo pipefail
source /etc/mtav/server
domains=()
for name in $NAMES; do domains+=(-d "$name"); done
certbot certonly --dns-route53 --non-interactive --agree-tos --register-unsafely-without-email \
    --cert-name mtav --keep-until-expiring --expand "${domains[@]}"
/usr/local/sbin/mtav-front
systemctl disable --now mtav-certificate.timer 2> /dev/null || true
SCRIPT
chmod 755 /usr/local/sbin/mtav-certificate
install -d /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/mtav-front <<'EOF'
#!/bin/sh
# A renewed certificate is served at once
exec /usr/local/sbin/mtav-front
EOF
chmod 755 /etc/letsencrypt/renewal-hooks/deploy/mtav-front
cat > /etc/systemd/system/mtav-certificate.service <<'EOF'
[Unit]
Description=MTAV: the server's certificate, until it is issued
After=network-online.target
[Service]
Type=oneshot
ExecStart=/usr/local/sbin/mtav-certificate
EOF
cat > /etc/systemd/system/mtav-certificate.timer <<'EOF'
[Unit]
Description=MTAV: try the certificate again hourly, until it is issued
[Timer]
OnBootSec=1min
OnUnitActiveSec=1h
[Install]
WantedBy=timers.target
EOF

# -- 6. nightly backups -----------------------------------------------------------------------------------------------
cat > /usr/local/sbin/mtav-backups <<'SCRIPT'
#!/bin/bash
# Every environment with a release live: its database and its uploaded files, through the gate, to S3
# (s3://<bucket>/<server>/<environment>/<date>/), where the bucket keeps them five weeks. The server can write them,
# never read them back or delete them.
set -uo pipefail
source /etc/mtav/server
day="$(date -u +%F)"
failed=0
for dir in /mtav/*/; do
    env="$(basename "$dir")"
    [[ -f "$dir/live" ]] || continue
    for what in database files; do
        target="s3://$BACKUPS/$SERVER/$env/$day/$what.$( [[ $what == database ]] && echo sql || echo tar ).gz"
        if SSH_ORIGINAL_COMMAND="backup $env $what" /usr/local/sbin/mtav-gate backups | aws s3 cp --region us-east-2 --only-show-errors - "$target"; then
            echo "$env $what: $target"
        else
            echo "$env $what: FAILED" >&2
            failed=1
        fi
    done
done
exit $failed
SCRIPT
chmod 755 /usr/local/sbin/mtav-backups
cat > /etc/systemd/system/mtav-backups.service <<'EOF'
[Unit]
Description=MTAV: every environment's database and files, to S3
[Service]
Type=oneshot
ExecStart=/usr/local/sbin/mtav-backups
EOF
cat > /etc/systemd/system/mtav-backups.timer <<'EOF'
[Unit]
Description=MTAV: nightly backups
[Timer]
OnCalendar=*-*-* 06:00:00 UTC
RandomizedDelaySec=15min
Persistent=true
[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
/usr/local/sbin/mtav-reconfigure
systemctl enable --now mtav-certificate.timer mtav-backups.timer > /dev/null 2>&1
echo "== $(date -u +%FT%TZ) the $SERVER server is set up; the certificate is tried within a minute"
