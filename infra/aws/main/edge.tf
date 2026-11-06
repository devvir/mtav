# CloudFront in front of each server: one distribution per server, under the names it serves, with the certificate
# for all of them. It speaks HTTPS to the server (to its origin name, whose own certificate covers the names too),
# adding the secret header the server's front web server asks for. Pages are never cached (each is someone's); the
# built files (/build/…, named by their content) are cached for a year, close to whoever asks. CloudFront also adds the
# security headers every answer carries.
#
# Only while `cloudfront` is on (main.tf): off, the servers do all this themselves (server/setup.sh, mtav-front). The
# certificate and the static page's bucket stay either way (both free), so switching it on is one apply.

# One certificate for every name CloudFront serves, proven by records in mtav.uy's zone; renewed by AWS itself.
resource "aws_acm_certificate" "edge" {
  provider                  = aws.virginia
  domain_name               = local.apex.name
  subject_alternative_names = sort([for name in keys(local.sites) : name if name != local.apex.name])
  validation_method         = "DNS"
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "edge_validation" {
  for_each = { for option in aws_acm_certificate.edge.domain_validation_options : option.domain_name => option }
  zone_id  = aws_route53_zone.mtav.zone_id
  name     = each.value.resource_record_name
  type     = each.value.resource_record_type
  ttl      = 300
  records  = [each.value.resource_record_value]

  allow_overwrite = true
}

resource "aws_acm_certificate_validation" "edge" {
  provider                = aws.virginia
  certificate_arn         = aws_acm_certificate.edge.arn
  validation_record_fqdns = [for record in aws_route53_record.edge_validation : record.fqdn]
}

locals {
  # AWS's managed policies, the same in every account.
  caching_disabled  = "4135ea2d-6df8-44a3-9df3-4b5a84be39ad"
  caching_optimized = "658327ea-f89d-4fab-a63d-7e88639e58f6"
  all_viewer        = "216adef6-5c7f-47e4-b989-5492eafa07d3" # every header, the name asked for among them
  security_headers  = "67f7725c-6f97-4210-82d7-5512b31e9d03"
}

resource "aws_cloudfront_distribution" "server" {
  for_each = local.cloudfront ? local.running : {}

  enabled         = true
  comment         = "MTAV ${each.key}: ${join(", ", keys(each.value.environments))}"
  aliases         = local.names[each.key]
  http_version    = "http2and3"
  is_ipv6_enabled = true
  price_class     = "PriceClass_All" # edges in South America too, close to the cooperatives

  origin {
    origin_id   = each.key
    domain_name = local.origin[each.key]
    custom_origin_config {
      http_port                = 80
      https_port               = 443
      origin_protocol_policy   = "https-only"
      origin_ssl_protocols     = ["TLSv1.2"]
      origin_read_timeout      = 60
      origin_keepalive_timeout = 30
    }
    custom_header {
      name  = "X-Origin-Verify"
      value = random_password.origin[each.key].result
    }
  }

  # Pages, the realtime connection (/app) and everything else: straight to the server, every header with it.
  default_cache_behavior {
    target_origin_id           = each.key
    viewer_protocol_policy     = "redirect-to-https"
    allowed_methods            = ["GET", "HEAD", "OPTIONS", "PUT", "POST", "PATCH", "DELETE"]
    cached_methods             = ["GET", "HEAD"]
    compress                   = true
    cache_policy_id            = local.caching_disabled
    origin_request_policy_id   = local.all_viewer
    response_headers_policy_id = local.security_headers
  }

  # The built files: named by their content, so kept as long as anyone asks.
  ordered_cache_behavior {
    path_pattern               = "/build/*"
    target_origin_id           = each.key
    viewer_protocol_policy     = "redirect-to-https"
    allowed_methods            = ["GET", "HEAD"]
    cached_methods             = ["GET", "HEAD"]
    compress                   = true
    cache_policy_id            = local.caching_optimized
    origin_request_policy_id   = local.all_viewer
    response_headers_policy_id = local.security_headers
  }

  viewer_certificate {
    acm_certificate_arn      = aws_acm_certificate_validation.edge.certificate_arn
    ssl_support_method       = "sni-only"
    minimum_protocol_version = "TLSv1.2_2021"
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }
}

# -- the static page --------------------------------------------------------------------------------------------------
# What the names of a server switched off show (static/index.html): from a private bucket, through a distribution of
# its own, under the same certificate.

resource "aws_s3_bucket" "static" {
  bucket        = "mtav-static-${local.account}"
  force_destroy = true # all it holds is in this repository
}

resource "aws_s3_bucket_public_access_block" "static" {
  bucket                  = aws_s3_bucket.static.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_object" "static" {
  bucket        = aws_s3_bucket.static.id
  key           = "index.html"
  source        = "${path.module}/static/index.html"
  etag          = filemd5("${path.module}/static/index.html")
  content_type  = "text/html; charset=utf-8"
  cache_control = "public, max-age=300"
}

resource "aws_cloudfront_origin_access_control" "static" {
  name                              = "mtav-static"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_s3_bucket_policy" "static" {
  count  = local.cloudfront ? 1 : 0
  bucket = aws_s3_bucket.static.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "CloudFrontReadsThePage"
      Effect    = "Allow"
      Principal = { Service = "cloudfront.amazonaws.com" }
      Action    = "s3:GetObject"
      Resource  = "${aws_s3_bucket.static.arn}/*"
      Condition = { StringEquals = { "AWS:SourceArn" = aws_cloudfront_distribution.static[0].arn } }
    }]
  })
}

resource "aws_cloudfront_distribution" "static" {
  count               = local.cloudfront ? 1 : 0
  enabled             = true
  comment             = "MTAV: the page for the names of a server switched off"
  aliases             = local.parked
  default_root_object = "index.html"
  http_version        = "http2and3"
  is_ipv6_enabled     = true
  price_class         = "PriceClass_All"

  origin {
    origin_id                = "static"
    domain_name              = aws_s3_bucket.static.bucket_regional_domain_name
    origin_access_control_id = aws_cloudfront_origin_access_control.static.id
  }

  default_cache_behavior {
    target_origin_id           = "static"
    viewer_protocol_policy     = "redirect-to-https"
    allowed_methods            = ["GET", "HEAD"]
    cached_methods             = ["GET", "HEAD"]
    compress                   = true
    cache_policy_id            = local.caching_optimized
    response_headers_policy_id = local.security_headers
  }

  # Whatever address someone has (a link into the app), the same page
  custom_error_response {
    error_code            = 403
    response_code         = 200
    response_page_path    = "/index.html"
    error_caching_min_ttl = 300
  }
  custom_error_response {
    error_code            = 404
    response_code         = 200
    response_page_path    = "/index.html"
    error_caching_min_ttl = 300
  }

  viewer_certificate {
    acm_certificate_arn      = aws_acm_certificate_validation.edge.certificate_arn
    ssl_support_method       = "sni-only"
    minimum_protocol_version = "TLSv1.2_2021"
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }
}
