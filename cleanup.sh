#!/bin/bash

# Symfony Backend Cleanup Script (Docker-First Approach)
# Stops Docker containers and removes all project files

set -e  # Exit on any error

echo "🗑️  Starting cleanup process..."
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Confirmation
read -p "Are you sure you want to remove all Docker containers, volumes, and project files? (yes/no) " -n 3 -r
echo
if [[ ! $REPLY =~ ^yes$ ]]; then
    echo -e "${YELLOW}Cleanup cancelled.${NC}"
    exit 0
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Step 1: Stop and remove Docker containers
echo -e "${YELLOW}[1/3] Stopping and removing Docker containers...${NC}"
if [ -f docker-compose.yml ] || [ -f compose.yaml ]; then
    docker-compose down -v --remove-orphans 2>/dev/null || true
fi

# Step 2: Remove Docker images related to this project
echo -e "${YELLOW}[2/3] Removing Docker images...${NC}"
docker image prune -f --filter "label=project=candidate-scoring" 2>/dev/null || true

# Step 3: Remove project files (keep .git, setup.sh, cleanup.sh, and .gitignore)
echo -e "${YELLOW}[3/3] Removing project files...${NC}"

# Keep git repository and scripts
find . -maxdepth 1 -type f -not -name "setup.sh" -not -name "cleanup.sh" -not -name ".gitignore" -delete
find . -maxdepth 1 -type d -not -name "." -not -name ".git" -exec rm -rf {} + 2>/dev/null || true

echo ""
echo -e "${GREEN}✅ Cleanup completed successfully!${NC}"
echo ""
echo "All Docker containers and project files have been removed."
echo "Your .git repository has been preserved."
echo "To reinstall and setup again, run: ./setup.sh"
