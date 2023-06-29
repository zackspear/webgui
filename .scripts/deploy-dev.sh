#!/bin/bash

# Path to store the last used server host
state_file="$HOME/.webgui_deploy_state"

# Function to display script usage and help information
show_help() {
  echo "Usage: $0 [-host SSH_SERVER_HOST] [-exclude PATHS]"
  echo ""
  echo "Deploys the source directory to the specified SSH server using rsync."
  echo ""
  echo "Options:"
  echo "  -host SSH_SERVER_HOST    The SSH server host to deploy to."
  echo "  -exclude PATHS           Paths to exclude (comma-separated)"
  echo ""
}

# Check if the help option is provided
if [[ $1 == "--help" || $1 == "-h" ]]; then
  show_help
  exit 0
fi

# Default values
server_host=""
exclude_paths=""

# Parse command-line options
while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    -host)
      server_host="$2"
      shift 2
      ;;
    -exclude)
      exclude_paths="$2"
      shift 2
      ;;
    *)
      show_help
      exit 1
      ;;
  esac
done

# Read the last used server host from the state file
if [[ -f "$state_file" ]]; then
  last_server_host=$(cat "$state_file")
else
  last_server_host=""
fi

# Read the server host from the command-line option or use the last used server host as the default
server_host="${server_host:-$last_server_host}"

# Check if the server host is provided
if [[ -z "$server_host" ]]; then
  echo "Please provide the SSH server host using the -host option."
  echo "Use the --help option for more information."
  exit 1
fi

# Save the current server host to the state file
echo "$server_host" > "$state_file"

# Source directory path (current directory)
source_directory="."

# Destination directory path
destination_directory="/usr/local"

# Check if /usr/local/sbin/unraid-api exists on the remote server
exclude_connect="no"
if ssh "root@$server_host" "[ -f /usr/local/sbin/unraid-api ]"; then
  exclude_connect="yes"
fi

# Exclude directory option
exclude_option=""
if [[ "$exclude_connect" == "yes" ]]; then
  exclude_option="--exclude '/emhttp/plugins/dynamix.my.servers' --exclude '/emhttp/plugins/dynamix/include/UpdateDNS.php'"
fi

# Additional paths to exclude
if [[ -n "$exclude_paths" ]]; then
  IFS=',' read -ra paths <<< "$exclude_paths"
  for path in "${paths[@]}"; do
    exclude_option+=" --exclude '$path'"
  done
fi

# Rsync command
rsync_command="rsync -amvz --relative --no-implied-dirs --progress --stats --exclude '/.*' --exclude '*/.*' $exclude_option \"$source_directory/\" \"root@$server_host:$destination_directory/\""

# Print the rsync command
echo "Executing the following command:"
echo "$rsync_command"

# Execute the rsync command
eval "$rsync_command"
exit_code=$?

# Exit with the rsync command's exit code
exit $exit_code
