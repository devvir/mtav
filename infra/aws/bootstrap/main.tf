# The bootstrap: the bucket every other part keeps its state in. It is made first, as the `mtav` profile, and its own
# state starts on this machine (it cannot live in a bucket it is about to make), moving into the bucket once it exists
# (backend.tf, then `tofu init -migrate-state`).
#
#   tofu init      # once, and whenever the provider or the backend changes
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
      part       = "bootstrap"
    }
  }
}

# The account these files act on: read from the credentials, never written down.
data "aws_caller_identity" "here" {}
