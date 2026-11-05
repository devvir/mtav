# Which OpenTofu reads these files, and which provider (the plugin that speaks to AWS's API) it uses: exactly this one,
# so a plan made today is made the same way tomorrow. `tofu init` downloads it and records its checksums in
# .terraform.lock.hcl, which is versioned.
terraform {
  required_version = ">= 1.13.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "6.67.0"
    }
  }
}
