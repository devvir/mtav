#!/usr/bin/env bash
# Each GitHub environment's deploy identity (mtav-deploy-<environment>), and its key as that environment's secrets
# (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY). Its policy is main's output deploy_policies: run its own environment's
# gate, ask any server what it runs, and read what those answered, nothing else. The key goes from AWS straight into GitHub: never shown,
# never written down. Running it again rotates every key (the old ones are deleted once the new ones are in GitHub).
#
#   infra/aws/deploy-keys.sh              # every environment of every running server
#   infra/aws/deploy-keys.sh dev staging  # only these
#   infra/aws/deploy-keys.sh --policies   # only each identity's policy, its key kept (after a change to deploy.tf)
#
# Run as the project's administrator (the mtav-admin profile, signed in with `aws login --profile mtav-admin`), with
# `gh` signed in to GitHub with access to devvir/mtav.
set -euo pipefail
export PATH="$HOME/.local/bin:$PATH"
here="$(cd "$(dirname "$0")" && pwd)"
repo=devvir/mtav
A=(--profile mtav-admin)

keys=yes
[[ "${1:-}" == --policies ]] && { keys=''; shift; }

policies="$(cd "$here/main" && tofu output -json deploy_policies)"
environments=("$@")
[[ ${#environments[@]} -gt 0 ]] || mapfile -t environments < <(jq -r 'keys[]' <<< "$policies")

for env in "${environments[@]}"; do
    policy="$(jq -r --arg e "$env" '.[$e] // empty' <<< "$policies")"
    [[ -n "$policy" ]] || { echo "$env: no running server holds it; skipped"; continue; }
    user="mtav-deploy-$env"

    aws iam get-user --user-name "$user" "${A[@]}" > /dev/null 2>&1 \
        || aws iam create-user --user-name "$user" --tags Key=app,Value=mtav "${A[@]}" > /dev/null
    aws iam put-user-policy --user-name "$user" --policy-name deploy --policy-document "$policy" "${A[@]}"
    [[ -n "$keys" ]] || { echo "$env: $user's policy is the one deploy.tf says"; continue; }

    old="$(aws iam list-access-keys --user-name "$user" "${A[@]}" --query 'AccessKeyMetadata[].AccessKeyId' --output text)"
    aws iam create-access-key --user-name "$user" "${A[@]}" --query 'AccessKey.[AccessKeyId,SecretAccessKey]' --output text \
        | { read -r id secret
            printf '%s' "$id" | gh secret set AWS_ACCESS_KEY_ID --env "$env" --repo "$repo"
            printf '%s' "$secret" | gh secret set AWS_SECRET_ACCESS_KEY --env "$env" --repo "$repo"; }
    for key in $old; do aws iam delete-access-key --user-name "$user" --access-key-id "$key" "${A[@]}"; done
    echo "$env: $user's key is in GitHub's $env environment"
done
