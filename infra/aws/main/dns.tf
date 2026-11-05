# mtav.uy's zone: made by hand the day its records were copied from Antel's DNS (each checked to answer the same), and
# adopted here as it was. The domain stays registered at NIC.uy, which hands the zone to these nameservers.
#
# Each site name leads where its environment runs: to the server MTAV ran on before (local.before), or, once moved
# (on_aws in main.tf), to its CloudFront distribution. Each server's origin name leads to its fixed address; only
# CloudFront uses it. The mail records are Zoho's (mail in and out) and ZeptoMail's (the app's mail), unchanged.

import {
  to = aws_route53_zone.mtav
  id = local.zone
}

resource "aws_route53_zone" "mtav" {
  name    = "mtav.uy"
  comment = "MTAV"
}

locals {
  # A TXT string holds at most 255 characters: a longer value is written in pieces, read back as one.
  txt = { for name, value in {
    zoho_dkim      = "v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAq3o2heaXV/Xlr8+URjuAP9yubRakCEa9xX0eyA/gTgKDWbyk9MtWau2C+Ud6JsFnyH9iXlcOcR4UWgT/SGk//jP6j+cPw2ayHV30Te+9BqNrLwTE4RhWqk0uxXz3K7m/uX8ys1A3t6qysx8Vf/jowhO3iYUBiMivI5jSy3RmCPJqWzHTjsg6B4TzVf4uCNZQH6ZQJ0bPrrtQwbHeq5QeMJKEXBlssw7c9+QkinOqIuB6muGd+8mJlCuGhDMesjUiWx91qGkQYZ0JrJcpSCi1VRon8sLygKqDL2O6JpUw7liIFa7Mr+Wc56Y2otWuwX/fAhji9hIyC5w6qsQ5vrbjNQIDAQAB"
    zeptomail_dkim = "k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCUBLrf/nP811YWtxQb3iudaAr4ef1CSvx5Ng4EHO1hlSgJbgsLf8Wfn98rRGcoLu81vDIp23LqMnjHZMHZWeJ2akebLgEf+eNjLOdTN4cInyOnWzLDvewfZGe96E1BFVVK2VOfRsNHD8NruiQL25DyIj4vGENESS3q1BdR+BMukQIDAQAB"
  } : name => join("\" \"", [for i in range(0, length(value), 255) : substr(value, i, 255)]) }

  # Every site name, where it leads, and the server holding it.
  sites = merge(
    { for pair in flatten([for server, it in local.servers : [for env in values(it.environments) : [for name in env.names : {
      name = name, server = server, on_aws = env.on_aws
    }]]]) : pair.name => pair },
    { (local.apex.name) = { name = local.apex.name, server = local.apex.server, on_aws = local.apex.on_aws } },
  )
}

# -- the sites --------------------------------------------------------------------------------------------------------

import {
  for_each = { for name, site in local.sites : name => site if !site.on_aws }
  to       = aws_route53_record.site_before[each.key]
  id       = "${local.zone}_${each.key}_A"
}

import {
  for_each = { for name, site in local.sites : name => site if site.on_aws }
  to       = aws_route53_record.site[each.key]
  id       = "${local.zone}_${each.key}_A"
}

# A site not moved yet: still the server before, with a short life, so moving it takes minutes.
resource "aws_route53_record" "site_before" {
  for_each = { for name, site in local.sites : name => site if !site.on_aws }
  zone_id  = aws_route53_zone.mtav.zone_id
  name     = each.key
  type     = "A"
  ttl      = 300
  records  = [local.before]
}

# A site moved: its server's distribution, or the static page's while its server is off (an alias, which also works
# for the bare domain).
resource "aws_route53_record" "site" {
  for_each = { for name, site in local.sites : name => site if site.on_aws }
  zone_id  = aws_route53_zone.mtav.zone_id
  name     = each.key
  type     = "A"
  alias {
    name = (local.servers[each.value.server].running
    ? aws_cloudfront_distribution.server[each.value.server].domain_name : aws_cloudfront_distribution.static.domain_name)
    zone_id = (local.servers[each.value.server].running
    ? aws_cloudfront_distribution.server[each.value.server].hosted_zone_id : aws_cloudfront_distribution.static.hosted_zone_id)
    evaluate_target_health = false
  }
}

# Each server's own name, for CloudFront to reach it by.
resource "aws_route53_record" "origin" {
  for_each = local.running
  zone_id  = aws_route53_zone.mtav.zone_id
  name     = local.origin[each.key]
  type     = "A"
  ttl      = 300
  records  = [aws_eip.server[each.key].public_ip]
}

# -- mail -------------------------------------------------------------------------------------------------------------

import {
  to = aws_route53_record.mail_in
  id = "${local.zone}_mtav.uy_MX"
}
resource "aws_route53_record" "mail_in" {
  zone_id = aws_route53_zone.mtav.zone_id
  name    = "mtav.uy"
  type    = "MX"
  ttl     = 86400
  records = ["10 mx.zoho.eu.", "20 mx2.zoho.eu.", "50 mx3.zoho.eu."]
}

import {
  to = aws_route53_record.apex_txt
  id = "${local.zone}_mtav.uy_TXT"
}
# Zoho's proof the domain is ours, and who may send mail as it (SPF).
resource "aws_route53_record" "apex_txt" {
  zone_id = aws_route53_zone.mtav.zone_id
  name    = "mtav.uy"
  type    = "TXT"
  ttl     = 1800
  records = ["zoho-verification=zb74838597.zmverify.zoho.eu", "v=spf1 include:zohomail.eu ~all"]
}

import {
  to = aws_route53_record.dmarc
  id = "${local.zone}__dmarc.mtav.uy_TXT"
}
resource "aws_route53_record" "dmarc" {
  zone_id = aws_route53_zone.mtav.zone_id
  name    = "_dmarc.mtav.uy"
  type    = "TXT"
  ttl     = 86400
  records = ["v=DMARC1; p=none; rua=mailto:diego@mtav.uy"]
}

import {
  to = aws_route53_record.zoho_dkim
  id = "${local.zone}_zoho._domainkey.mtav.uy_TXT"
}
# Zoho's signature on mail sent from its mailboxes.
resource "aws_route53_record" "zoho_dkim" {
  zone_id = aws_route53_zone.mtav.zone_id
  name    = "zoho._domainkey.mtav.uy"
  type    = "TXT"
  ttl     = 86400
  records = [local.txt.zoho_dkim]
}

import {
  to = aws_route53_record.zeptomail_dkim
  id = "${local.zone}_1184349._domainkey.mtav.uy_TXT"
}
# ZeptoMail's signature on the app's mail.
resource "aws_route53_record" "zeptomail_dkim" {
  zone_id = aws_route53_zone.mtav.zone_id
  name    = "1184349._domainkey.mtav.uy"
  type    = "TXT"
  ttl     = 86400
  records = [local.txt.zeptomail_dkim]
}

import {
  to = aws_route53_record.zeptomail_bounces
  id = "${local.zone}_bounce-zem.mtav.uy_CNAME"
}
# Where ZeptoMail receives what bounces.
resource "aws_route53_record" "zeptomail_bounces" {
  zone_id = aws_route53_zone.mtav.zone_id
  name    = "bounce-zem.mtav.uy"
  type    = "CNAME"
  ttl     = 86400
  records = ["cluster89.zeptomail.eu."]
}
