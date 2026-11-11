# How GitHub deploys, and what a deploy runs on a server. Each deploy job runs in its GitHub environment (dev, staging,
# production, sorteo), and each environment has an AWS identity of its own (mtav-deploy-<environment>), whose key is
# that GitHub environment's secret (infra/aws/deploy-keys.sh makes them: this project's rules allow no GitHub sign-in
# to AWS, so a key it is). The identity may do one thing on one server: run that environment's gate
# (deploy/host/gate, which accepts only deploy, rollback and status, a release only by its image's digest), through a
# Systems Manager document whose request must match the gate's own words. It may also ask its server what it runs
# (status; production and sorteo, staging's server too), and read what its own commands and status answered, nothing
# else. Where a command's output goes it cannot narrow (any of the servers' log groups), but never to S3: no bucket is
# a resource it may name.
#
# The gate, and every step a deploy runs as root, are the servers' own, installed from here (mtav-host below): never
# taken from a release, whose image brings only its containers.

locals {
  # Each environment, and the server it runs on.
  environments = merge([for server, it in local.running : { for env in keys(it.environments) : env => server }]...)

  # The server's deploy steps (mtav-host): each file, by the name install.sh finds it under.
  host_files = {
    "install.sh"         = "${path.module}/server/install.sh"
    "backups.sh"         = "${path.module}/server/backups.sh"
    "gate"               = "${path.module}/../../../deploy/host/gate"
    "deploy"             = "${path.module}/../../../deploy/host/deploy"
    "compose.yml"        = "${path.module}/../../../deploy/compose.yml"
    "slot.yml"           = "${path.module}/../../../deploy/slot.yml"
    "live.conf.template" = "${path.module}/../../../deploy/switch/live.conf.template"
  }
  # The servers each identity may ask what they run: its own, and staging's for those that deploy what it runs.
  asks = { for env, server in local.environments : env => distinct(concat([server],
    contains(["production", "sorteo"], env) && contains(keys(local.environments), "staging") ? [local.environments["staging"]] : []
  )) }

  host_version = sha256(join(" ", [for name, file in local.host_files : "${name}=${filesha256(file)}"]))
}

# The deploy steps, carried whole (each file compressed), for every server to install (server/install.sh).
resource "aws_ssm_document" "host" {
  name            = "mtav-host"
  document_type   = "Command"
  document_format = "JSON"
  content = jsonencode({
    schemaVersion = "2.2"
    description   = "MTAV's deploy steps on this server: the gate, the deploy and its compose files, the nightly backups"
    mainSteps = [{
      action = "aws:runShellScript"
      name   = "install"
      inputs = {
        timeoutSeconds = "1800"
        runCommand = concat(
          ["set -eu", "from=\"$(mktemp -d)\"", "trap 'rm -rf \"$from\"' EXIT"],
          [for name, file in local.host_files : "echo '${base64gzip(file(file))}' | base64 -d | gunzip > \"$from/${name}\""],
          ["bash \"$from/install.sh\" '${local.host_version}'"],
        )
      }
    }]
  })
}

# Every running server installs them as soon as it first comes online, and again whenever `tofu apply` changes them
# (a new version of the document runs this again, by itself): apply them while nothing deploys (install.sh waits two
# minutes for each environment being deployed, then leaves its records as they are). By hand: `aws ssm
# start-associations-once --association-ids <id>` (tofu output host_steps).
resource "aws_ssm_association" "host" {
  name             = aws_ssm_document.host.name
  association_name = "mtav-host"
  document_version = aws_ssm_document.host.latest_version
  targets {
    key    = "InstanceIds"
    values = [for one in aws_instance.server : one.id]
  }
  # The first time, apply waits for it, and says so if it failed: longer than install.sh can wait (two minutes for each
  # of a server's environments), shorter than the document's own limit
  wait_for_success_timeout_seconds = 900
}

output "host_steps" {
  description = "The deploy steps the servers install: their version (in /usr/local/lib/mtav/version on each), and the association that installs them."
  value       = { version = local.host_version, association = aws_ssm_association.host.association_id }
}

# What each environment's gate answers (a deploy, every line), for that environment's identity alone; what any server
# says it runs goes to /mtav/commands (servers.tf), which every identity reads. Kept a month, made for every
# environment, its server running or not.
resource "aws_cloudwatch_log_group" "deploys" {
  for_each          = toset(flatten([for it in values(local.servers) : keys(it.environments)]))
  name              = "/mtav/commands/${each.key}"
  retention_in_days = 30
}

# The gate, for one environment: what the pipeline may ask, worded as the gate expects it.
resource "aws_ssm_document" "gate" {
  for_each        = local.environments
  name            = "mtav-gate-${each.key}"
  document_type   = "Command"
  document_format = "JSON"
  content = jsonencode({
    schemaVersion = "2.2"
    description   = "MTAV's gate, for ${each.key}: deploy a release, roll back, or say what runs"
    parameters = {
      request = {
        type           = "String"
        description    = "deploy ${each.key} <commit> <digest> [fresh], rollback ${each.key}, or status"
        allowedPattern = "^(deploy ${each.key} [0-9a-f]{40} sha256:[0-9a-f]{64}( fresh)?|rollback ${each.key}|status)$"
      }
    }
    mainSteps = [{
      action = "aws:runShellScript"
      name   = "gate"
      inputs = {
        timeoutSeconds = "3600"
        runCommand     = ["MTAV_REQUEST='{{ request }}' /usr/local/sbin/mtav-gate ${each.key}"]
      }
    }]
  })
}

# What each server runs, for anyone deploying (the production deploy reads staging's release).
resource "aws_ssm_document" "status" {
  name            = "mtav-status"
  document_type   = "Command"
  document_format = "JSON"
  content = jsonencode({
    schemaVersion = "2.2"
    description   = "What each of MTAV's environments on this server runs"
    mainSteps = [{
      action = "aws:runShellScript"
      name   = "status"
      inputs = { runCommand = ["MTAV_REQUEST=status /usr/local/sbin/mtav-gate"] }
    }]
  })
}

# What each environment's deploy identity may do (deploy-keys.sh gives it to mtav-deploy-<environment>). What a command
# printed it reads from its own log group, never from Systems Manager: AWS lets no one read a command's output there
# without reading every command's (the warden's among them).
output "deploy_policies" {
  description = "Each environment's deploy identity's policy: its gate on its server, what every server runs, and what those answered."
  value = { for env, server in local.environments : env => jsonencode({
    Version = "2012-10-17"
    Statement = [
      { Sid = "ItsGate", Effect = "Allow", Action = "ssm:SendCommand",
      Resource = [aws_ssm_document.gate[env].arn, aws_instance.server[server].arn] },
      # Its own server's, and, deploying the release staging runs (production and sorteo), staging's server's. Only
      # these servers at all: a document allowed here is allowed on each of them, AWS cannot pair the two
      { Sid = "WhatRuns", Effect = "Allow", Action = "ssm:SendCommand",
      Resource = concat([aws_ssm_document.status.arn], [for one in local.asks[env] : aws_instance.server[one].arn]) },
      # Neither can be narrowed (AWS): whether a command ended, and where the servers are, say nothing it printed
      { Sid = "WhetherDone", Effect = "Allow", Action = ["ssm:ListCommands", "ec2:DescribeInstances"], Resource = "*" },
      { Sid = "Answers", Effect = "Allow", Action = "logs:FilterLogEvents",
        Resource = flatten([for group in [aws_cloudwatch_log_group.deploys[env], aws_cloudwatch_log_group.commands] :
      [group.arn, "${group.arn}:*"]]) },
    ]
  }) }
}
