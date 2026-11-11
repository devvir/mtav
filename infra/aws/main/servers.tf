# The servers: AWS's small ARM instances (t4g.small, 2 GB), Ubuntu 24.04. Each boots once through server/setup.sh
# (Docker, the front web server, its certificate, nightly backups), installs its deploy steps (the gate deploys go
# through: mtav-host, deploy.tf) once it is online and again whenever they change, and is from then on reached only
# through Systems Manager: no SSH key, no SSH port.

# The newest Ubuntu 24.04 for ARM, as Canonical publishes it; read at each plan, but a running server is never
# replaced for a newer one (ignore_changes below): security updates install themselves on it.
data "aws_ssm_parameter" "ubuntu" {
  name = "/aws/service/canonical/ubuntu/server/24.04/stable/current/arm64/hvm/ebs-gp3/ami-id"
}

resource "aws_instance" "server" {
  for_each = local.running

  ami                    = data.aws_ssm_parameter.ubuntu.value
  instance_type          = each.value.type
  subnet_id              = aws_subnet.servers.id
  vpc_security_group_ids = [aws_security_group.server[each.key].id]
  iam_instance_profile   = aws_iam_instance_profile.server[each.key].name

  # What never changes for it (its name, its data disk), then the script itself (server/setup.sh), unpacked and run;
  # it travels compressed (a first boot's data is at most 16 KB). What may change (its environments, its names) it
  # reads from its settings (below); its deploy steps come from mtav-host (deploy.tf).
  user_data = join("\n", [
    "#!/bin/bash",
    "SERVER='${each.key}'",
    "DATA='${aws_ebs_volume.data[each.key].id}'",
    "echo '${base64gzip(file("${path.module}/server/setup.sh"))}' | base64 -d | gunzip > /root/mtav-setup.sh",
    "source /root/mtav-setup.sh",
  ])

  root_block_device {
    volume_type = "gp3"
    volume_size = each.value.disk
    encrypted   = true
    tags        = { Name = "mtav-${each.key}", snapshots = "daily" }
  }

  # The instance's own metadata only with a token (IMDSv2): a request forged through an app cannot read the role's keys.
  metadata_options {
    http_tokens                 = "required"
    http_put_response_hop_limit = 1 # the server's own commands only: no container uses AWS
  }

  tags = {
    Name         = "mtav-${each.key}"
    server       = each.key
    environments = join(",", keys(each.value.environments))
  }

  lifecycle {
    ignore_changes = [ami, user_data]
  }
}

# Each server's data disk, apart from it (main.tf): made whether the server runs or not, and never destroyed by a plan.
# Production's is made too, empty, ready for when it is switched on.
resource "aws_ebs_volume" "data" {
  for_each          = local.servers
  availability_zone = aws_subnet.servers.availability_zone
  size              = each.value.data
  type              = "gp3"
  encrypted         = true
  tags              = { Name = "mtav-${each.key}-data", snapshots = "daily" }

  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_volume_attachment" "data" {
  for_each    = local.running
  device_name = "/dev/sdf"
  volume_id   = aws_ebs_volume.data[each.key].id
  instance_id = aws_instance.server[each.key].id
}

# A server's settings: its environments (ports and names), every name it answers for, the bare domain if it holds it,
# the names it serves the static page for, whether CloudFront is in front of it, and where its backups go. Read at its first boot, and again by `mtav-reconfigure` on it whenever they change (an
# environment moved between servers): its front web server and its certificate follow.
resource "aws_ssm_parameter" "server_config" {
  for_each = local.running
  name     = "/mtav/${each.key}/config"
  type     = "String"
  value = join("\n", [
    "NAMES='${join(" ", concat(local.names[each.key], [local.origin[each.key]]))}'",
    "ENVIRONMENTS='${jsonencode(each.value.environments)}'",
    "APEX='${local.apex.server == each.key ? "${local.apex.name} ${local.apex.to}" : ""}'",
    "PARKED='${local.page_server == each.key ? join(" ", local.parked) : ""}'",
    "CLOUDFRONT='${local.cloudfront ? "yes" : ""}'",
    "BACKUPS='${aws_s3_bucket.backups.bucket}'",
  ])
}

# The static page (static/index.html), for the server that serves it without CloudFront; compressed, as a setting
# holds at most 4 KB.
resource "aws_ssm_parameter" "static_page" {
  for_each = local.page_server == null ? toset([]) : toset([local.page_server])
  name     = "/mtav/${each.value}/static-page"
  type     = "String"
  value    = base64gzip(file("${path.module}/static/index.html"))
}

# A fixed public address each: the way out, and the way in (people's, or CloudFront's by the origin name in dns.tf),
# the same across a stop and start.
resource "aws_eip" "server" {
  for_each = local.running
  instance = aws_instance.server[each.key].id
  domain   = "vpc"
  tags     = { Name = "mtav-${each.key}" }
}

# -- what a server may do, as its role ------------------------------------------------------------------------------

resource "aws_iam_role" "server" {
  for_each             = local.running
  name                 = "mtav-${each.key}"
  permissions_boundary = "arn:aws:iam::${local.account}:policy/mtav-boundary"
  assume_role_policy = jsonencode({
    Version   = "2012-10-17"
    Statement = [{ Effect = "Allow", Principal = { Service = "ec2.amazonaws.com" }, Action = "sts:AssumeRole" }]
  })
}

# Being reached through Systems Manager: AWS's own policy for it.
resource "aws_iam_role_policy_attachment" "reached" {
  for_each   = local.running
  role       = aws_iam_role.server[each.key].name
  policy_arn = "arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore"
}

# Its own settings, its backups (written, never read back or deleted: a server taken over cannot wipe them), its logs,
# and the certificate's DNS challenge.
resource "aws_iam_role_policy" "server" {
  for_each = local.running
  name     = each.key
  role     = aws_iam_role.server[each.key].id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      { Effect = "Allow", Action = ["ssm:GetParameter", "ssm:GetParameters", "ssm:GetParametersByPath"],
      Resource = "arn:aws:ssm:us-east-2:${local.account}:parameter/mtav/${each.key}/*" },
      { Effect = "Allow", Action = "kms:Decrypt", Resource = "*",
      Condition = { StringEquals = { "kms:ViaService" = "ssm.us-east-2.amazonaws.com" } } },
      { Effect = "Allow", Action = "s3:PutObject", Resource = "${aws_s3_bucket.backups.arn}/${each.key}/*" },
      # Systems Manager's agent looks the group up before it writes there: without DescribeLogGroups, nothing arrives
      { Effect = "Allow", Action = "logs:DescribeLogGroups", Resource = "*" },
      { Effect = "Allow", Action = ["logs:CreateLogStream", "logs:PutLogEvents", "logs:DescribeLogStreams"],
      Resource = concat(["${aws_cloudwatch_log_group.commands.arn}:*"], [for group in aws_cloudwatch_log_group.deploys : "${group.arn}:*"]) },
      { Effect = "Allow", Action = "route53:ChangeResourceRecordSets", Resource = "${aws_route53_zone.mtav.arn}",
        Condition = {
          "ForAllValues:StringLike"   = { "route53:ChangeResourceRecordSetsNormalizedRecordNames" = ["_acme-challenge.*"] }
          "ForAllValues:StringEquals" = { "route53:ChangeResourceRecordSetsRecordTypes" = ["TXT"] }
      } },
      { Effect = "Allow", Action = ["route53:GetChange", "route53:ListHostedZones", "route53:ListResourceRecordSets"], Resource = "*" },
    ]
  })
}

resource "aws_iam_instance_profile" "server" {
  for_each = local.running
  name     = "mtav-${each.key}"
  role     = aws_iam_role.server[each.key].name
}

# What CloudFront tells a server's front web server with every request, so it knows the request came through our
# distribution; the server reads it at boot and at each renewal of its certificate.
resource "random_password" "origin" {
  for_each = local.running
  length   = 40
  special  = false
}

resource "aws_ssm_parameter" "origin_secret" {
  for_each = local.running
  name     = "/mtav/${each.key}/origin-secret"
  type     = "SecureString"
  value    = random_password.origin[each.key].result
}

# What the servers say they run (status), for every deploy identity to read; each environment's deploys write to a
# group of their own (deploy.tf). Kept a month.
resource "aws_cloudwatch_log_group" "commands" {
  name              = "/mtav/commands"
  retention_in_days = 30
}
