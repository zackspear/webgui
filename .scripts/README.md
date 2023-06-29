# Development scripts
## `deploy-dev.sh`

Easily deploy your local repo's changes to an Unraid server on your network.

```
Usage: .scripts/deploy-dev.sh [SSH_SERVER_NAME] [-exclude-connect] [-exclude-dirs DIRS]

Deploys the source directory to the specified SSH server using rsync.

Positional Arguments:
  SSH_SERVER_NAME     The SSH server name to deploy to.

Options:
  -exclude-connect    Exclude the directory 'emhttp/plugins/dynamix.my.servers'
                      and 'emhttp/plugins/dynamix/include/UpdateDNS.php'
  -exclude-dirs DIRS  Additional directories to exclude (comma-separated)
```