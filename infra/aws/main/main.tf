# MTAV on AWS, whole: two servers (production on one; staging, dev and sorteo on the other), CloudFront in front of
# each (or not: `cloudfront` below), the mtav.uy zone, backups, and the way GitHub deploys. Everything is in Ohio
# (us-east-2), the project's Region, but the HTTPS certificate CloudFront serves, which AWS keeps in Virginia
# (us-east-1) for every distribution.
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
  # CloudFront in front of the servers (edge.tf), or the servers facing the internet themselves. On: people reach
  # CloudFront, which reaches the servers, which answer nothing else. Off: each name leads straight to its server's
  # fixed address, the server serves HTTPS with its own certificate (as it does for CloudFront), and a running server
  # serves the static page for the names of a server switched off. Off for now: AWS opens CloudFront to an account only
  # once it has seen it used for a while (ask again after the first full billing cycle, from December 2026).
  cloudfront = false

  account = data.aws_caller_identity.here.account_id

  # The servers, and the environments each runs: the ports its switch listens on (as in each environment's .env), and
  # the names people reach it by.
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
        production = { web = 8002, ws = 8082, names = ["www.mtav.uy"] }
      }
    }
    preview = {
      running = true
      type    = "t4g.small"
      disk    = 20
      data    = 4
      environments = {
        staging = { web = 8000, ws = 8080, names = ["stg.mtav.uy"] }
        dev     = { web = 8001, ws = 8081, names = ["dev.mtav.uy"] }
        # The Open Lottery, public, kept running while production is off: small, and it fits here
        sorteo = { web = 8003, ws = 8083, names = ["sorteo.mtav.uy"] }
      }
    }
  }

  # The bare domain is no environment's: it sends people to www, as the server that holds www does.
  apex = { name = "mtav.uy", server = "production", to = "www.mtav.uy" }

  # The servers that are on, which everything made per server follows.
  running = { for server, it in local.servers : server => it if it.running }

  # The names of the servers switched off, and the bare domain if its server is one of them: the static page's.
  parked = sort(concat(
    flatten([for server, it in local.servers : [for env in values(it.environments) : env.names] if !it.running]),
    local.servers[local.apex.server].running ? [] : [local.apex.name],
  ))
  # Who serves the static page without CloudFront: the first server running (none: those names lead nowhere).
  page_server = local.cloudfront ? null : try(sort(keys(local.running))[0], null)

  # Every name a server answers for: its environments', the bare domain on the one that sends it to www, the static
  # page's if it serves it, and its own origin name (CloudFront reaches the server by it).
  names = { for server, it in local.servers : server => concat(
    flatten([for env in values(it.environments) : env.names]),
    local.apex.server == server ? [local.apex.name] : [],
    local.page_server == server ? local.parked : [],
  ) }
  origin = { for server in keys(local.servers) : server => "origin-${server}.mtav.uy" }

  # The server each site name leads to: its own, or, its own off, the one serving the static page.
  site_server = { for name, site in local.sites : name => (
    local.servers[site.server].running ? site.server : local.page_server
  ) }
}
