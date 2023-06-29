#!/bin/bash

# Path to store the last used server host
state_file="$HOME/.webgui_deploy_state"
exclude_state_file="$HOME/.webgui_deploy_exclude_state"

# Function to display script usage and help information
show_help() {
  echo "Usage: $0 [-host SSH_SERVER_HOST] [-exclude PATHS] [--clear-exclude-state] [--ignore-exclude-state] [--no-save-exclude-state]"
  echo ""
  echo "Deploys the source directory to the specified SSH server using rsync."
  echo ""
  echo "Options:"
  echo "  -host SSH_SERVER_HOST       The SSH server host to deploy to."
  echo "  -exclude PATHS              Paths to exclude (comma-separated)"
  echo "  --clear-exclude-state       Clear the exclude state file"
  echo "  --ignore-exclude-state      Ignore the saved exclude state"
  echo "  --no-save-exclude-state     Do not save the exclude state"
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
clear_exclude_state="no"
ignore_exclude_state="no"
save_exclude_state="yes"

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
    --clear-exclude-state)
      clear_exclude_state="yes"
      shift
      ;;
    --ignore-exclude-state)
      ignore_exclude_state="yes"
      shift
      ;;
    --no-save-exclude-state)
      save_exclude_state="no"
      shift
      ;;
    *)
      show_help
      exit 1
      ;;
  esac
done

# Check if both -exclude and --clear-exclude-state are provided
if [[ -n "$exclude_paths" && "$clear_exclude_state" == "yes" ]]; then
  echo "Error: Cannot use -exclude and --clear-exclude-state options together."
  exit 1
fi

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

# Check if the exclude state file should be cleared
if [[ "$clear_exclude_state" == "yes" ]]; then
  rm -f "$exclude_state_file"
fi

# Save the current exclude option to the state file
if [[ -n "$exclude_paths" && "$save_exclude_state" == "yes" ]]; then
  echo "$exclude_paths" > "$exclude_state_file"
fi

# Read the exclude option from the state file
if [[ -f "$exclude_state_file" && "$ignore_exclude_state" != "yes" ]]; then
  saved_exclude_option=$(cat "$exclude_state_file")
  if [[ -n "$saved_exclude_option" ]]; then
    exclude_paths="$saved_exclude_option"
  fi
fi

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
additional_excludes=(
  "./sbin/emcmd"
  "./sbin/plugin"
  "./sbin/language"
  "./sbin/newperms"
  "./sbin/inet"
  "./sbin/samba"
  "./sbin/diagnostics"
  "./emhttp/boot"
  "./emhttp/plugins/dynamix/images/case-model.png"
  "./emhttp/state"
  "./emhttp/mnt"
  "./emhttp/log"
  "./emhttp/webGui"
)

# Find all symbolic links in the source directory
symbolic_links=$(find "$source_directory" -type l)

# Loop through symbolic links and add non-existing values to additional_excludes
for link in $symbolic_links; do
  target=$(readlink -f "$link")
  if [[ ! " ${additional_excludes[@]} " =~ " ${target} " ]]; then
    additional_excludes+=("$target")
  fi
done

# Additional paths to exclude (if provided)
if [[ -n "$exclude_paths" ]]; then
  IFS=',' read -ra paths <<< "$exclude_paths"
  for path in "${paths[@]}"; do
    exclude_option+=" --exclude '$path'"
  done
fi

# Rsync command
rsync_command="rsync -amvz --no-links --relative --no-implied-dirs --progress --stats --exclude '/.*' --exclude '*/.*' $exclude_option \"$source_directory/\" \"root@$server_host:$destination_directory/\""

# Print the rsync command
echo "Executing the following command:"
echo "$rsync_command"

# Execute the rsync command
eval "$rsync_command"
exit_code=$?

# Exit with the rsync command's exit code
exit $exit_code
