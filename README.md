# AssistDoc

AssistDoc is a multi-tenant private document assistant for companies.

This repository contains:

- `backend/`: Laravel-style API application
- `frontend/`: Angular SPA
- `infra/`: container and deployment scaffolding
- `specs/`: feature specs, plans, tasks, and supporting design artifacts

Current implementation status:

- monorepo scaffolding created
- auth, tenant context, audit, and chat MVP skeleton in progress
- document ingestion and audit UI follow-up work remains in task phases after the MVP slice

Authentication slice status:

- `POST /api/v1/auth/login` authenticates local development users and returns a tenant-scoped bearer token
- `GET /api/v1/auth/me` restores the authenticated user context for the SPA
- `POST /api/v1/auth/logout` invalidates the current bearer token
- tenant context is derived server-side from the authenticated user, not from client input

## RAG profile by machine

The application selects the active RAG profile from the backend `.env` through `RAG_PROFILE_PRESET`.

- `RAG_PROFILE_PRESET=default` selects the standard profile for a normal machine
- `RAG_PROFILE_PRESET=low_spec` selects the lighter profile for slower machines

The selected preset maps to the seeded retrieval profiles in the backend:

- `default` -> `qwen` with `qwen3` and `qwen3-embedding`
- `low_spec` -> `qwen-pc-lenti` with `qwen3:1.7b` and `qwen3-embedding:0.6b`

After changing the preset, clear the Laravel config cache and rerun the seeders if the database must be aligned with the selected profile.

docker compose --env-file .env.production -f docker-compose-prod.yml exec ollama ollama pull qwen3
docker compose --env-file .env.production -f docker-compose-prod.yml exec ollama ollama pull qwen3-embedding

## Frontend creazione aggiornamneto
sudo rm -rf frontend/dist
docker compose --env-file .env.production -f docker-compose-prod.yml run --rm frontend-build
docker compose --env-file .env.production -f docker-compose-prod.yml up -d --force-recreate nginx

## Gestione
docker compose --env-file .env.production -f docker-compose-prod.yml exec backend php artisan config:clear
docker compose --env-file .env.production -f docker-compose-prod.yml up -d --force-recreate backend nginx
docker compose --env-file .env.production -f docker-compose-prod.yml exec backend php artisan migrate:fresh --seed

## Ngnix riavvia
docker compose --env-file .env.production -f docker-compose-prod.yml up -d --build --force-recreate backend nginx

## Test dimensione embedding
 docker compose --env-file .env.production -f docker-compose-prod.yml exec backend php -r '
$r=json_decode(shell_exec("curl -s http://ollama:11434/api/embed -H \"Content-Type: application/json\" -d '\''{\"model\":\"qwen3-embedding\",\"input\":\"test\
"}'\''"), true);
echo count($r["embeddings"][0]).PHP_EOL;
'
## Cambiato una linea di codice php Ricostruisco:
docker compose --env-file .env.production -f docker-compose-prod.yml up -d --build --force-recreate backend worker scheduler nginx

## Controllo log laravel 
docker compose --env-file .env.production -f docker-compose-prod.yml exec backend tail -n 100 storage/logs/laravel.log

## Cancellare la collection 
docker compose --env-file .env.production -f docker-compose-prod.yml exec backend   curl -X DELETE http://qdrant:6333/collections/assistdoc_segments_4096

## Lista delle collection
docker compose --env-file .env.production -f docker-compose-prod.yml exec backend curl http://qdrant:6333/collections