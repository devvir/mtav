# Who may reach each server, and where it may go. Nothing reaches a server from the internet but CloudFront, on HTTPS
# (443), and the front web server there also asks for the secret header only our distributions add (edge.tf): someone
# else's distribution passes the first wall, never the second. No SSH, anywhere: people and deploys reach a server
# through Systems Manager. A server goes out only to the web (HTTPS, and HTTP for the package mirrors), to mail servers,
# and to the network's own DNS, clock and metadata, which are not walled.

locals {
  outward = {
    https = { port = 443, about = "The web: AWS, the GitHub registry, Claude, Lets Encrypt, package mirrors" }
    http  = { port = 80, about = "Package mirrors that answer over HTTP" }
    smtps = { port = 465, about = "Mail, sent over TLS" }
    smtp  = { port = 587, about = "Mail, sent with STARTTLS" }
  }
  outbound = merge([for server in keys(local.running) : {
    for name, rule in local.outward : "${server}-${name}" => merge(rule, { server = server })
  }]...)
}

resource "aws_security_group" "server" {
  for_each    = local.running
  name        = "mtav-${each.key}"
  description = "Who may reach the ${each.key} server"
  vpc_id      = aws_vpc.net.id
  tags        = { Name = "mtav-${each.key}" }
}

# CloudFront's own addresses towards origins, as AWS keeps them listed.
data "aws_ec2_managed_prefix_list" "cloudfront" {
  name = "com.amazonaws.global.cloudfront.origin-facing"
}

resource "aws_vpc_security_group_ingress_rule" "cloudfront" {
  for_each          = local.running
  security_group_id = aws_security_group.server[each.key].id
  description       = "CloudFront, over HTTPS"
  prefix_list_id    = data.aws_ec2_managed_prefix_list.cloudfront.id
  ip_protocol       = "tcp"
  from_port         = 443
  to_port           = 443
}

resource "aws_vpc_security_group_egress_rule" "out" {
  for_each          = local.outbound
  security_group_id = aws_security_group.server[each.value.server].id
  description       = each.value.about
  ip_protocol       = "tcp"
  from_port         = each.value.port
  to_port           = each.value.port
  cidr_ipv4         = "0.0.0.0/0"
}
