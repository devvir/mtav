#!/bin/bash
# The server's deploy steps, installed from the files beside this script: the gate (deploy/host/gate) and the deploy
# with the compose files it runs (deploy/host/deploy, deploy/compose.yml, deploy/slot.yml,
# deploy/switch/live.conf.template), and the nightly backups (backups.sh). The mtav-host document
# (infra/aws/main/deploy.tf) carries them all and runs this, as root, on every server: when the server first comes
# online, and whenever `tofu apply` changes any of them. They are the owner's alone to change: no release, no deploy
# and no deploy identity can (the gate runs nothing from a release's image but its containers).
#
#   install.sh <version>   (the files' checksum, kept in /usr/local/lib/mtav/version)
#
# Each file replaces the one before in one step: a deploy running meanwhile keeps the steps it started with.
set -euo pipefail
from="$(cd "$(dirname "$0")" && pwd)"
files=/usr/local/lib/mtav

for script in gate deploy backups.sh; do bash -n "$from/$script"; done

put() {
    install -o root -g root -m "$3" "$from/$1" "$2.next"
    mv -f "$2.next" "$2"
}
install -d -o root -g root -m 755 "$files"
put deploy "$files/deploy" 755
put compose.yml "$files/compose.yml" 644
put slot.yml "$files/slot.yml" 644
put live.conf.template "$files/live.conf.template" 644
put backups.sh /usr/local/sbin/mtav-backups 755
# Last, once what it runs is in place
put gate /usr/local/sbin/mtav-gate 755

# A release recorded by its tag alone (<slot> <repository>:sha-<commit>) is pinned to the image this server holds under
# that tag, as it was pulled; one no longer here stays as it is, and going back to it is refused. An environment being
# deployed meanwhile is waited for, two minutes at most (its deploy holds its lock; the mtav-host association waits 15
# for the whole install)
pin() {
    local record="$1" slot image pinned
    [[ -f "$record" ]] || return 0
    read -r slot image _ < "$record"
    [[ "$image" == *:sha-* && "$image" != *@* ]] || return 0
    pinned="$(docker image inspect -f '{{range .RepoDigests}}{{println .}}{{end}}' "$image" 2> /dev/null \
        | grep -m1 "^${image%%:sha-*}@sha256:" || true)"
    if [[ -n "$pinned" ]]; then
        echo "$slot $pinned ${image##*:sha-}" > "$record.next" && mv -f "$record.next" "$record"
        echo "$record: $pinned"
    else
        echo "$record: $image is not on this server: going back to it is refused"
    fi
}
if command -v docker > /dev/null; then
    for dir in /mtav/*/; do
        [[ -f "$dir/live" ]] || continue
        (
            flock -w 120 9 || { echo "$dir: still deploying, its records are left as they are"; exit 0; }
            pin "${dir}live"
            pin "${dir}previous"
        ) 9> "${dir}.deploying"
    done
fi

echo "${1:-unknown}" > "$files/version"
echo "deploy steps installed: ${1:-unknown}"
