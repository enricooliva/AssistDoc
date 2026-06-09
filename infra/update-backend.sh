#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_DIR"

COMPOSE="docker compose --env-file .env.production -f docker-compose-prod.yml"

echo "Pulling latest code..."
git pull

echo "Building and restarting backend services..."
$COMPOSE up -d --build backend worker scheduler

echo "Running database migrations..."
$COMPOSE exec backend php artisan migrate --force

echo "Clearing Laravel cache..."
$COMPOSE exec backend php artisan optimize:clear

echo "Rebuilding Laravel production cache..."
#$COMPOSE exec backend php artisan config:cache
#$COMPOSE exec backend php artisan route:cache
#$COMPOSE exec backend php artisan view:cache

echo "Restarting Laravel queue workers..."
$COMPOSE exec backend php artisan queue:restart

echo "Backend update complete."