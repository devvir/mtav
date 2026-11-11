#!/bin/bash
# Every environment with a release live: its database and its uploaded files, through the gate, to S3
# (s3://<bucket>/<server>/<environment>/<date>/), where the bucket keeps every copy five weeks, whatever is written
# after it. The server can write them, never read them back, delete them or shorten how long they are kept
# (infra/aws/main/backups.tf). Installed by install.sh as /usr/local/sbin/mtav-backups, run nightly by its timer
# (setup.sh) and by the warden's "Back up now".
set -uo pipefail
source /etc/mtav/server
day="$(date -u +%F)"
failed=0
for dir in /mtav/*/; do
    env="$(basename "$dir")"
    [[ -f "$dir/live" ]] || continue
    for what in database files; do
        target="s3://$BACKUPS/$SERVER/$env/$day/$what.$( [[ $what == database ]] && echo sql || echo tar ).gz"
        if MTAV_REQUEST="backup $env $what" /usr/local/sbin/mtav-gate backups | aws s3 cp --region us-east-2 --only-show-errors - "$target"; then
            echo "$env $what: $target"
        else
            echo "$env $what: FAILED" >&2
            failed=1
        fi
    done
done
exit $failed
