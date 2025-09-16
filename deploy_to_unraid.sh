#!/bin/bash

# Deploy script for unRAID webGUI updates
# Deploys only git-modified files to the target server
# Usage: ./deploy_to_unraid.sh <target_host>

# Show help if requested
if [ "$1" = "-h" ] || [ "$1" = "--help" ]; then
    echo "Usage: $0 <target_host>"
    echo ""
    echo "Deploy git-modified files to unRAID server"
    echo ""
    echo "Arguments:"
    echo "  target_host    SSH target (required)"
    echo ""
    echo "Examples:"
    echo "  $0 root@192.168.1.100   # Deploy to specific IP"
    echo "  $0 root@tower.local     # Deploy to named host"
    echo "  $0 root@unraid.local    # Deploy to unraid.local"
    exit 0
fi

# Get target host from command line (required)
if [ $# -eq 0 ]; then
    echo "❌ Error: Target host required"
    echo "Usage: $0 <target_host>"
    echo "Example: $0 root@192.168.1.100"
    exit 1
fi

TARGET_HOST="$1"
echo "ℹ️  Deploying to: $TARGET_HOST"

TARGET_EMHTTP="/usr/local/emhttp"

echo "🚀 Deploying git-modified files to unRAID..."

# Check for additional files to deploy (passed as arguments)
ADDITIONAL_FILES=""
if [ $# -gt 1 ]; then
    shift  # Remove the target host from arguments
    for FILE in "$@"; do
        if [ -f "$FILE" ]; then
            ADDITIONAL_FILES="$ADDITIONAL_FILES$FILE\n"
        fi
    done
fi

# Get list of modified files from git (excluding deleted files)
GIT_FILES=$(git diff --name-only --diff-filter=ACMR HEAD | grep -E "^emhttp/" || true)

# Get list of untracked files
UNTRACKED_FILES=$(git ls-files --others --exclude-standard | grep -E "^emhttp/" || true)

# Combine all files
FILES=""
[ -n "$GIT_FILES" ] && FILES="$FILES$GIT_FILES\n"
[ -n "$UNTRACKED_FILES" ] && FILES="$FILES$UNTRACKED_FILES\n"
[ -n "$ADDITIONAL_FILES" ] && FILES="$FILES$ADDITIONAL_FILES"

# Remove trailing newline and duplicates
FILES=$(echo -e "$FILES" | grep -v '^$' | sort -u)

if [ -z "$FILES" ]; then
    echo "✅ No files to deploy"
    exit 0
fi

echo "📋 Files to deploy:"
echo "$FILES" | sed 's/^/   - /'
echo ""

# Create backup directory on target
BACKUP_DIR="$TARGET_EMHTTP/backups/$(date +%Y%m%d_%H%M%S)"
echo "📦 Creating backup directory on target..."
ssh "$TARGET_HOST" "mkdir -p '$BACKUP_DIR'"

# Deploy each file
while IFS= read -r FILE; do
    if [ ! -f "$FILE" ]; then
        echo "⚠️  Warning: $FILE not found, skipping..."
        continue
    fi

    FILENAME=$(basename "$FILE")
    DIRNAME=$(dirname "$FILE" | sed 's/emhttp\///')
    TARGET_PATH="$TARGET_EMHTTP/$DIRNAME/$FILENAME"

    echo "📤 Deploying $FILENAME..."

    # Backup existing file if it exists
    ssh "$TARGET_HOST" "[ -f '$TARGET_PATH' ] && cp '$TARGET_PATH' '$BACKUP_DIR/$FILENAME.bak'"

    # Copy the updated file
    if scp "$FILE" "$TARGET_HOST:$TARGET_PATH"; then
        echo "✅ $FILENAME deployed successfully"
    else
        echo "❌ Failed to deploy $FILENAME"
        exit 1
    fi
done <<< "$FILES"

echo ""
echo "✨ Deployment complete to $TARGET_HOST!"
echo "📝 Successfully deployed $(echo "$FILES" | wc -l | xargs) modified file(s)"