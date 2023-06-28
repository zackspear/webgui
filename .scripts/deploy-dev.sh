#!/bin/bash

# Path to store the last used server name
state_file="$HOME/.webgui_deploy_state"

# Function to display script usage and help information
show_help() {
  echo "Usage: $0 [SSH_SERVER_NAME]"
  echo ""
  echo "Deploys the source directory to the specified SSH server using rsync."
  echo ""
  echo "Positional Argument:"
  echo "  SSH_SERVER_NAME     The SSH server name to deploy to."
  echo ""
}

# Check if the help option is provided
if [[ $1 == "--help" || $1 == "-h" ]]; then
  show_help
  exit 0
fi

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

# Execute the rsync command to upload the source directory excluding directories starting with a period
rsync_command="rsync -amvz --relative --no-implied-dirs --progress --stats --exclude '/.*' --exclude '*/.*' \"$source_directory/\" \"root@${server_name}.local:$destination_directory/\""

# Print the rsync command
echo "Executing the following command:"
echo "$rsync_command"

# Execute the rsync command
eval "$rsync_command"
exit_code=$?

# Exit with the rsync command's exit code
exit $exit_code
