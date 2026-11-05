# Backups, two kinds:
#
# - every night, each environment's database and uploaded files, through its gate, to a bucket the servers can write
#   to but never read from or delete from (servers.tf): kept five weeks;
# - every day, a snapshot of each server's whole disk, by AWS's own scheduler: kept a week. Restoring one brings back
#   the server as it was, Docker, settings and all.

resource "aws_s3_bucket" "backups" {
  bucket = "mtav-backups-${local.account}"
}

resource "aws_s3_bucket_public_access_block" "backups" {
  bucket                  = aws_s3_bucket.backups.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_server_side_encryption_configuration" "backups" {
  bucket = aws_s3_bucket.backups.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "backups" {
  bucket = aws_s3_bucket.backups.id
  rule {
    id     = "five-weeks"
    status = "Enabled"
    filter {}
    expiration { days = 35 }
  }
}

resource "aws_s3_bucket_policy" "backups" {
  bucket = aws_s3_bucket.backups.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "OnlyOverTls", Effect = "Deny", Principal = "*", Action = "s3:*",
      Resource  = [aws_s3_bucket.backups.arn, "${aws_s3_bucket.backups.arn}/*"],
      Condition = { Bool = { "aws:SecureTransport" = "false" } }
    }]
  })
}

# -- the disks' daily snapshots ---------------------------------------------------------------------------------------

resource "aws_iam_role" "snapshots" {
  name                 = "mtav-snapshots"
  permissions_boundary = "arn:aws:iam::${local.account}:policy/mtav-boundary"
  assume_role_policy = jsonencode({
    Version   = "2012-10-17"
    Statement = [{ Effect = "Allow", Principal = { Service = "dlm.amazonaws.com" }, Action = "sts:AssumeRole" }]
  })
}

resource "aws_iam_role_policy_attachment" "snapshots" {
  role       = aws_iam_role.snapshots.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSDataLifecycleManagerServiceRole"
}

resource "aws_dlm_lifecycle_policy" "snapshots" {
  description        = "Daily snapshots of each server disk kept a week"
  execution_role_arn = aws_iam_role.snapshots.arn
  state              = "ENABLED"

  policy_details {
    resource_types = ["VOLUME"]
    target_tags    = { snapshots = "daily" }

    schedule {
      name      = "daily"
      copy_tags = true
      create_rule {
        interval      = 24
        interval_unit = "HOURS"
        times         = ["07:00"] # after the nightly backups (06:00 UTC, 03:00 in Uruguay)
      }
      retain_rule {
        count = 7
      }
    }
  }
}
