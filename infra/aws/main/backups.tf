# Backups, two kinds:
#
# - every night, each environment's database and uploaded files, through its gate, to a bucket the servers can write
#   to but never read from or delete from (servers.tf), where every copy is kept five weeks whatever comes after it:
#   one written again under the same name is a new version beside it, and only an identity allowed to bypass the lock
#   (no server is) can remove or shorten a version before its five weeks are up;
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

# Every version kept: what is written over is still there.
resource "aws_s3_bucket_versioning" "backups" {
  bucket = aws_s3_bucket.backups.id
  versioning_configuration {
    status = "Enabled"
  }
}

# Each version locked for five weeks from its writing. Governance: an identity allowed s3:BypassGovernanceRetention (the
# project's own, `mtav` and its administrator; never a server) can still remove one before then, a person's decision;
# compliance would let no one, not even the account. Once on, versioning can never be suspended.
resource "aws_s3_bucket_object_lock_configuration" "backups" {
  bucket = aws_s3_bucket.backups.id
  rule {
    default_retention {
      mode = "GOVERNANCE"
      days = 35
    }
  }
  depends_on = [aws_s3_bucket_versioning.backups]
}

# Five weeks, then gone: a copy becomes a previous version after 35 days, and a previous version (that one, or one
# written over sooner) goes a day later, once its lock allows it; what is left of the copy then (its delete marker)
# goes with it, as S3 does where current versions expire. An upload a server never finished does not linger.
resource "aws_s3_bucket_lifecycle_configuration" "backups" {
  bucket = aws_s3_bucket.backups.id
  rule {
    id     = "five-weeks"
    status = "Enabled"
    filter {}
    expiration { days = 35 }
    noncurrent_version_expiration { noncurrent_days = 1 }
    abort_incomplete_multipart_upload { days_after_initiation = 1 }
  }
  depends_on = [aws_s3_bucket_versioning.backups]
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
