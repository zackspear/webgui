#!/bin/bash

# Path to store the last used server name
state_file="$HOME/.webgui_deploy_state"

# Function to display script usage and help information
show_help() {
  echo "Usage: $0 [SSH_SERVER_NAME] [-exclude-connect] [-exclude PATHS]"
  echo ""
  echo "Deploys the source directory to the specified SSH server using rsync."
  echo ""
  echo "Positional Arguments:"
  echo "  SSH_SERVER_NAME     The SSH server name to deploy to."
  echo ""
  echo "Options:"
  echo "  -exclude-connect    Exclude the directory 'emhttp/plugins/dynamix.my.servers'"
  echo "  -exclude PATHS      Paths to exclude (comma-separated)"
  echo ""
}

# Check if the help option is provided
if [[ $1 == "--help" || $1 == "-h" ]]; then
  show_help
  exit 0
fi

# Parse command-line options
exclude_connect="no"
exclude_paths=""
while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    -exclude-connect)
      exclude_connect="yes"
      shift
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

# Read the last used server name from the state file
if [[ -f "$state_file" ]]; then
  last_server_name=$(cat "$state_file")
else
  last_server_name=""
fi

# Read the server name from the command-line argument or use the last used server name as the default
server_name="${1:-$last_server_name}"

# Check if the server name is provided
if [[ -z "$server_name" ]]; then
  echo "Please provide the SSH server name."
  echo "Use the --help option for more information."
  exit 1
fi

# Save the current server name to the state file
echo "$server_name" > "$state_file"

# Source directory path (current directory)
source_directory="."

# Destination directory path
destination_directory="/usr/local"

# Exclude directory option
exclude_option=""
if [[ "$exclude_connect" == "yes" ]]; then
  exclude_option="--exclude '/emhttp/plugins/dynamix.my.servers' --exclude '/emhttp/plugins/dynamix/include/UpdateDNS.php'"
fi

# Additional paths to exclude
if [[ -n "$exclude_paths" ]]; then
  IFS=',' read -ra paths <<< "$exclude_paths"
  for path in "${paths[@]}"; do
    exclude_option+=" --exclude '/$path'"
  done
fi

# Rsync command
rsync_command="rsync -amvz --relative --no-implied-dirs --progress --stats --exclude '/.*' --exclude '*/.*' $exclude_option \"$source_directory/\" \"root@${server_name}.local:$destination_directory/\""

# Print the rsync command
echo "Executing the following command:"
echo "$rsync_command"

# Execute the rsync command
eval "$rsync_command"
exit_code=$?

# Exit with the rsync command's exit code
exit $exit_code
