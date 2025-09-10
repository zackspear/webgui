#!/bin/bash

# Generate PR plugin file for Unraid
# Usage: ./generate-pr-plugin.sh <version> <pr_number> <commit_sha> <tarball_name>

VERSION=$1
PR_NUMBER=$2
COMMIT_SHA=$3
TARBALL_NAME=$4

if [ -z "$VERSION" ] || [ -z "$PR_NUMBER" ] || [ -z "$COMMIT_SHA" ] || [ -z "$TARBALL_NAME" ]; then
    echo "Usage: $0 <version> <pr_number> <commit_sha> <tarball_name>"
    exit 1
fi

PLUGIN_NAME="webgui-pr-${VERSION}.plg"
TARBALL_SHA256=$(sha256sum "$TARBALL_NAME" | awk '{print $1}')

echo "Generating plugin: $PLUGIN_NAME"
echo "Tarball SHA256: $TARBALL_SHA256"

cat > "$PLUGIN_NAME" << 'EOF'
<?xml version='1.0' standalone='yes'?>
<!DOCTYPE PLUGIN [
  <!ENTITY name "webgui-pr">
  <!ENTITY version "VERSION_PLACEHOLDER">
  <!ENTITY author "unraid">
  <!ENTITY pluginURL "https://github.com/unraid/webgui">
  <!ENTITY tarball "TARBALL_PLACEHOLDER">
  <!ENTITY sha256 "SHA256_PLACEHOLDER">
  <!ENTITY pr "PR_PLACEHOLDER">
  <!ENTITY commit "COMMIT_PLACEHOLDER">
]>

<PLUGIN name="&name;-&version;" 
        author="&author;" 
        version="&version;" 
        pluginURL="&pluginURL;" 
        min="6.12.0" 
        icon="wrench"
        support="&pluginURL;/pull/&pr;">

<CHANGES>
##&version;
- Test build for PR #&pr; (commit &commit;)
- This plugin installs modified files from the PR for testing
- Original files are backed up and restored upon removal
</CHANGES>

<!-- Create backup directory -->
<FILE Run="/bin/bash" Method="install">
<INLINE>
<![CDATA[
echo "===================================="
echo "WebGUI PR Test Plugin Installation"
echo "===================================="
echo "Version: &version;"
echo "PR: #&pr;"
echo "Commit: &commit;"
echo ""

# Create directories
mkdir -p /boot/config/plugins/&name;
mkdir -p /boot/config/plugins/&name;/backups

echo "Created plugin directories"
]]>
</INLINE>
</FILE>

<!-- Download tarball -->
<FILE Name="/boot/config/plugins/&name;/&tarball;">
<LOCAL>/boot/config/plugins/&tarball;</LOCAL>
<SHA256>&sha256;</SHA256>
</FILE>

<!-- Backup and install files -->
<FILE Run="/bin/bash" Method="install">
<INLINE>
<![CDATA[
BACKUP_DIR="/boot/config/plugins/&name;/backups"
TARBALL="/boot/config/plugins/&name;/&tarball;"
MANIFEST="/boot/config/plugins/&name;/installed_files.txt"

echo "Starting file deployment..."

# Clear manifest
> "$MANIFEST"

# Extract and get file list
cd /
tar -tzf "$TARBALL" | while read -r file; do
    # Skip directories
    if [[ "$file" == */ ]]; then
        continue
    fi
    
    # Convert tar path to actual system path
    SYSTEM_FILE="/${file}"
    
    # Check if file exists and backup
    if [ -f "$SYSTEM_FILE" ]; then
        BACKUP_FILE="$BACKUP_DIR/$(echo "$file" | tr '/' '_')"
        echo "Backing up: $SYSTEM_FILE"
        cp -p "$SYSTEM_FILE" "$BACKUP_FILE"
        echo "$SYSTEM_FILE|$BACKUP_FILE" >> "$MANIFEST"
    else
        echo "$SYSTEM_FILE|NEW" >> "$MANIFEST"
    fi
done

# Extract the tarball
echo ""
echo "Installing modified files..."
tar -xzf "$TARBALL" -C /

echo ""
echo "✅ Installation complete!"
echo ""
echo "The following files have been deployed:"
cat "$MANIFEST" | cut -d'|' -f1 | while read -r file; do
    echo "  - $file"
done

echo ""
echo "⚠️  This is a TEST plugin for PR #&pr;"
echo "⚠️  Remove this plugin before applying production updates"
]]>
</INLINE>
</FILE>

<!-- Removal script -->
<FILE Run="/bin/bash" Method="remove">
<INLINE>
<![CDATA[
echo "===================================="
echo "WebGUI PR Test Plugin Removal"
echo "===================================="
echo ""

BACKUP_DIR="/boot/config/plugins/&name;/backups"
MANIFEST="/boot/config/plugins/&name;/installed_files.txt"

if [ -f "$MANIFEST" ]; then
    echo "Restoring original files..."
    
    while IFS='|' read -r system_file backup_file; do
        if [ "$backup_file" == "NEW" ]; then
            # This was a new file, remove it
            if [ -f "$system_file" ]; then
                echo "Removing new file: $system_file"
                rm -f "$system_file"
            fi
        else
            # Restore from backup
            if [ -f "$backup_file" ]; then
                echo "Restoring: $system_file"
                mv -f "$backup_file" "$system_file"
            fi
        fi
    done < "$MANIFEST"
    
    echo ""
    echo "✅ Original files restored"
else
    echo "⚠️  No manifest found, cannot restore files"
fi

# Clean up
echo "Cleaning up plugin files..."
rm -rf "/boot/config/plugins/&name;"
rm -f "/boot/config/plugins/&tarball;"

echo ""
echo "✅ Plugin removed successfully"
]]>
</INLINE>
</FILE>

</PLUGIN>
EOF

# Replace placeholders
sed -i "s/VERSION_PLACEHOLDER/${VERSION}/g" "$PLUGIN_NAME"
sed -i "s/TARBALL_PLACEHOLDER/${TARBALL_NAME}/g" "$PLUGIN_NAME"
sed -i "s/SHA256_PLACEHOLDER/${TARBALL_SHA256}/g" "$PLUGIN_NAME"
sed -i "s/PR_PLACEHOLDER/${PR_NUMBER}/g" "$PLUGIN_NAME"
sed -i "s/COMMIT_PLACEHOLDER/${COMMIT_SHA}/g" "$PLUGIN_NAME"

echo "Plugin generated: $PLUGIN_NAME"