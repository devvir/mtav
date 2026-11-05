# How GitHub deploys. Each deploy job runs in its GitHub environment (dev, staging, production, sorteo), and each
# environment has an AWS identity of its own (mtav-deploy-<environment>), whose key is that GitHub environment's secret
# (infra/aws/deploy-keys.sh makes them: this project's rules allow no GitHub sign-in to AWS, so a key it is). The
# identity may do one thing on one server: run that environment's gate (the same deploy/host/gate as ever, which
# accepts only deploy, rollback and status, and only MTAV's releases), through a Systems Manager document whose
# request must match the gate's own words. Every one may also ask any server what it runs (status).

locals {
  # Each environment, and the server it runs on.
  environments = merge([for server, it in local.running : { for env in keys(it.environments) : env => server }]...)
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
        description    = "deploy ${each.key} <commit> [fresh], rollback ${each.key}, or status"
        allowedPattern = "^(deploy ${each.key} [0-9a-f]{40}( fresh)?|rollback ${each.key}|status)$"
      }
    }
    mainSteps = [{
      action = "aws:runShellScript"
      name   = "gate"
      inputs = {
        timeoutSeconds = "3600"
        runCommand     = ["SSH_ORIGINAL_COMMAND='{{ request }}' /usr/local/sbin/mtav-gate ${each.key}"]
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
      inputs = { runCommand = ["SSH_ORIGINAL_COMMAND=status /usr/local/sbin/mtav-gate"] }
    }]
  })
}

# What each environment's deploy identity may do (deploy-keys.sh gives it to mtav-deploy-<environment>).
output "deploy_policies" {
  description = "Each environment's deploy identity's policy: its gate on its server, and what every server runs."
  value = { for env, server in local.environments : env => jsonencode({
    Version = "2012-10-17"
    Statement = [
      { Sid = "ItsGate", Effect = "Allow", Action = "ssm:SendCommand",
      Resource = [aws_ssm_document.gate[env].arn, aws_instance.server[server].arn] },
      { Sid = "WhatRuns", Effect = "Allow", Action = "ssm:SendCommand",
      Resource = concat([aws_ssm_document.status.arn], [for one in aws_instance.server : one.arn]) },
      { Sid = "Answers", Effect = "Allow", Action = ["ssm:GetCommandInvocation", "ssm:ListCommandInvocations", "ec2:DescribeInstances"], Resource = "*" },
    ]
  }) }
}
