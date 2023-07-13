# Development scripts
## `deploy-dev.sh`

Easily deploy your local repo's changes to an Unraid server on your network.

```
Usage: .scripts/deploy-dev.sh [-host SSH_SERVER_HOST] [-exclude PATHS]

Deploys the source directory to the specified SSH server using rsync.

Options:
  -host SSH_SERVER_HOST    The SSH server host to deploy to.
  -exclude PATHS           Paths to exclude (comma-separated)
```