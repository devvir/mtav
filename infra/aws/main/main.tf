# MTAV on AWS, whole: two servers (production and sorteo on one, staging and dev on the other), CloudFront in front of
# each, the mtav.uy zone, backups, and the way GitHub deploys. Everything is in Ohio (us-east-2), the project's Region,
# but the HTTPS certificate CloudFront serves, which AWS keeps in Virginia (us-east-1) for every distribution.
#
#   tofu init      # once, and whenever a provider or the backend changes
#   tofu plan      # what would change, said before anything does
#   tofu apply     # does it (asks first, showing the plan again)

provider "aws" {
  region  = "us-east-2"
  profile = "mtav"

  # Every resource made here says where it comes from, so the console tells ours apart.
  default_tags {
    tags = {
      app        = "mtav"
      managed-by = "opentofu"
      part       = "main"
    }
  }
}

# CloudFront's certificate, in the one Region it reads certificates from.
provider "aws" {
  alias   = "virginia"
  region  = "us-east-1"
  profile = "mtav"

  default_tags {
    tags = {
      app        = "mtav"
      managed-by = "opentofu"
      part       = "main"
    }
  }
}

# The account these files act on: read from the credentials, never written down.
data "aws_caller_identity" "here" {}

locals {
  account = data.aws_caller_identity.here.account_id
  zone    = "Z08799512NUF8677Y26UO" # mtav.uy, made before this configuration and adopted by it (dns.tf)

  # The servers, and the environments each runs: the ports its switch listens on (as in each environment's .env), the
  # names people reach it by, and whether those names lead to AWS yet (false: still to the server MTAV ran on before).
  #
  # Each server's data (its environments' databases, uploaded files and settings) lives on a disk of its own (`data`,
  # in GB), apart from the server: it stays when the server goes and is attached again when it comes back.
  #
  # A server is switched off by `running = false`: it, its address, its distribution and its deploy roles go, and its
  # names show the static page (static/index.html) instead; only its data disk stays (0.08 US$ a GB a month). Switched
  # on again, everything here is made again around that disk, and its environments are as they were.
  servers = {
    production = {
      running = false # MTAV is not launched yet: www and the bare domain show that it is on its way
      type    = "t4g.small"
      disk    = 20
      data    = 4
      environments = {
        production = { web = 8002, ws = 8082, names = ["www.mtav.uy"], on_aws = true }
        sorteo     = { web = 8003, ws = 8083, names = ["sorteo.mtav.uy"], on_aws = false }
      }
    }
    preview = {
      running = true
      type    = "t4g.small"
      disk    = 20
      data    = 4
      environments = {
        staging = { web = 8000, ws = 8080, names = ["stg.mtav.uy"], on_aws = false }
        dev     = { web = 8001, ws = 8081, names = ["dev.mtav.uy"], on_aws = false }
      }
    }
  }

  # The bare domain is no environment's: it sends people to www, as the server that holds www does.
  apex = { name = "mtav.uy", server = "production", to = "www.mtav.uy", on_aws = true }

  # The server MTAV ran on before AWS, where a name not on AWS yet still leads.
  before = "179.27.99.138"

  # Every name a server answers for: its environments', the bare domain on the one that sends it to www, and its own
  # origin name (CloudFront reaches the server by it).
  names = { for server, it in local.servers : server => concat(
    flatten([for env in values(it.environments) : env.names]),
    local.apex.server == server ? [local.apex.name] : [],
  ) }
  origin = { for server in keys(local.servers) : server => "origin-${server}.mtav.uy" }

  # The servers that are on, which everything made per server follows.
  running = { for server, it in local.servers : server => it if it.running }
}
