#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_DIR"

COMPOSE="docker compose --env-file .env.production -f docker-compose-prod.yml"

echo "Pulling latest code..."
git pull

echo "Removing old frontend build..."
sudo rm -rf frontend/dist

echo "Building frontend..."
$COMPOSE run --rm frontend-build

echo "Restarting nginx..."
$COMPOSE up -d --force-recreate nginx

echo "Frontend update complete."