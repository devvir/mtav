# Where OpenTofu keeps the record of what exists (its "state"): a bucket of its own, private, versioned so a state
# overwritten by mistake can be taken back, encrypted, spoken to only over TLS. No server or function may read it,
# whatever it is given (mtav-boundary denies it): a state holds secrets in clear.

resource "aws_s3_bucket" "state" {
  bucket = "mtav-tofu-state-${data.aws_caller_identity.here.account_id}"
}

resource "aws_s3_bucket_versioning" "state" {
  bucket = aws_s3_bucket.state.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "state" {
  bucket = aws_s3_bucket.state.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "state" {
  bucket                  = aws_s3_bucket.state.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_policy" "state" {
  bucket = aws_s3_bucket.state.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "OnlyOverTls", Effect = "Deny", Principal = "*", Action = "s3:*",
      Resource  = [aws_s3_bucket.state.arn, "${aws_s3_bucket.state.arn}/*"],
      Condition = { Bool = { "aws:SecureTransport" = "false" } }
    }]
  })
}

output "state_bucket" {
  description = "The bucket every part keeps its state in."
  value       = aws_s3_bucket.state.bucket
}
