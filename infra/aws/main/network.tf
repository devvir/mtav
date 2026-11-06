# One network of MTAV's own (a VPC), with one subnet whose servers each get a public address: the way out, to pull
# releases, send mail and call Claude. The way in is walls.tf's: only CloudFront, or, without it, people on HTTPS. (A server with no public address would need a NAT gateway to get out, about 32 US$ a
# month.)
#
# The S3 endpoint sends the servers' backups to S3 inside AWS's network, free.

resource "aws_vpc" "net" {
  cidr_block           = "10.40.0.0/16"
  enable_dns_support   = true
  enable_dns_hostnames = true
  tags                 = { Name = "mtav" }
}

resource "aws_internet_gateway" "net" {
  vpc_id = aws_vpc.net.id
  tags   = { Name = "mtav" }
}

resource "aws_subnet" "servers" {
  vpc_id            = aws_vpc.net.id
  cidr_block        = "10.40.1.0/24"
  availability_zone = "us-east-2a"
  tags              = { Name = "mtav-servers" }
}

resource "aws_route_table" "out" {
  vpc_id = aws_vpc.net.id
  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.net.id
  }
  tags = { Name = "mtav" }
}

resource "aws_route_table_association" "servers" {
  subnet_id      = aws_subnet.servers.id
  route_table_id = aws_route_table.out.id
}

resource "aws_vpc_endpoint" "s3" {
  vpc_id            = aws_vpc.net.id
  service_name      = "com.amazonaws.us-east-2.s3"
  vpc_endpoint_type = "Gateway"
  route_table_ids   = [aws_route_table.out.id]
  tags              = { Name = "mtav-s3" }
}
