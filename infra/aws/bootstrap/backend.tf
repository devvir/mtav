# This part's state, in the bucket it made, beside the others.

terraform {
  backend "s3" {
    bucket       = "mtav-tofu-state-690260957652"
    key          = "bootstrap.tfstate"
    region       = "us-east-2"
    profile      = "mtav"
    use_lockfile = true
  }
}
