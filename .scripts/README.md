# Development scripts
## `deploy-dev.sh`

Easily deploy your local repo's changes to an Unraid server on your network.

```
Usage: .scripts/deploy-dev.sh [SSH_SERVER_NAME]

Deploys the source directory to the specified SSH server using rsync.

Positional Argument:
  SSH_SERVER_NAME     The SSH server name to deploy to. Required on first usage. Optional on subsequent use for the same server.
```