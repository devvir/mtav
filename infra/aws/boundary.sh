#!/usr/bin/env bash
# mtav-boundary: the most any role the `mtav` identity makes may do (every role in infra/aws/main names it as its
# permissions boundary), as boundary.json says, with this project's account and mtav.uy's zone filled in. `mtav` may
# not change it (it would widen its own reach): run this as the project's administrator (the mtav-admin profile,
# signed in with `aws login --profile mtav-admin`) whenever boundary.json changes. It becomes the policy's new
# version; the oldest goes first when five are kept (IAM's limit).
set -euo pipefail
here="$(cd "$(dirname "$0")" && pwd)"
A=(--profile mtav-admin)

account="$(aws sts get-caller-identity "${A[@]}" --query Account --output text)"
zone="$(aws route53 list-hosted-zones-by-name --dns-name mtav.uy --max-items 1 "${A[@]}" --query 'HostedZones[0].Id' --output text)"
zone="${zone##*/}"
arn="arn:aws:iam::$account:policy/mtav-boundary"
document="$(sed -e "s/{{account}}/$account/g" -e "s/{{zone}}/$zone/g" "$here/boundary.json")"

mapfile -t older < <(aws iam list-policy-versions --policy-arn "$arn" "${A[@]}" \
    --query 'sort_by(Versions[?!IsDefaultVersion], &CreateDate)[].VersionId' --output text | tr '\t' '\n' | grep . || true)
if ((${#older[@]} >= 4)); then
    aws iam delete-policy-version --policy-arn "$arn" --version-id "${older[0]}" "${A[@]}"
fi
version="$(aws iam create-policy-version --policy-arn "$arn" --policy-document "$document" --set-as-default "${A[@]}" \
    --query PolicyVersion.VersionId --output text)"
echo "mtav-boundary is $version, as boundary.json says"
