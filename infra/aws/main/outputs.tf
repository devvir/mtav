# What the rest of MTAV reads from here: the pipeline (which role to take, which server runs an environment), the
# warden, and whoever moves an environment.

output "servers" {
  description = "Each server: its instance, fixed address, origin name and environments."
  value = { for server, it in local.running : server => {
    instance     = aws_instance.server[server].id
    address      = aws_eip.server[server].public_ip
    origin       = local.origin[server]
    environments = keys(it.environments)
  } }
}

output "backups" {
  description = "The bucket the nightly backups go to."
  value       = aws_s3_bucket.backups.bucket
}
