# The servers: AWS's small ARM instances (t4g.small, 2 GB), Ubuntu 24.04. Each boots once through server/setup.sh
# (Docker, the front web server, its certificate, the gate deploys go through, nightly backups) and is from then on
# reached only through Systems Manager: no SSH key, no SSH port.

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

  # What the first boot needs to know, then the script itself (server/setup.sh), unchanged: the gate travels compressed.
  user_data = join("\n", [
    "#!/bin/bash",
    "SERVER='${each.key}'",
    "NAMES='${join(" ", concat(local.names[each.key], [local.origin[each.key]]))}'",
    "ENVIRONMENTS='${jsonencode(each.value.environments)}'",
    "APEX='${local.apex.server == each.key ? "${local.apex.name} ${local.apex.to}" : ""}'",
    "BACKUPS='${aws_s3_bucket.backups.bucket}'",
    "DATA='${aws_ebs_volume.data[each.key].id}'",
    "GATE='${base64gzip(file("${path.module}/../../../deploy/host/gate"))}'",
    file("${path.module}/server/setup.sh"),
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

# A fixed public address each: the way out (and CloudFront's way in, by the origin name in dns.tf), the same across a
# stop and start.
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
      { Effect = "Allow", Action = ["logs:CreateLogStream", "logs:PutLogEvents", "logs:DescribeLogStreams"],
      Resource = "${aws_cloudwatch_log_group.commands.arn}:*" },
      { Effect = "Allow", Action = "route53:ChangeResourceRecordSets", Resource = "arn:aws:route53:::hostedzone/${local.zone}",
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

# What the servers' commands write (a deploy, every line), as the pipeline and the warden follow it; kept a month.
resource "aws_cloudwatch_log_group" "commands" {
  name              = "/mtav/commands"
  retention_in_days = 30
}
