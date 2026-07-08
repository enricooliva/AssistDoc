# AssistDoc

## Enterprise Knowledge Assistant based on Generative AI and RAG

AssistDoc è un progetto sperimentale di assistente intelligente per l'interrogazione di documentazione aziendale attraverso tecniche di **Retrieval-Augmented Generation (RAG)**.

L'obiettivo è esplorare come **Large Language Models (LLM)**, **ricerca semantica** e sistemi di recupero della conoscenza possano migliorare l'accesso alle informazioni contenute in documenti strutturati e non strutturati.

Il progetto studia un approccio in cui gli utenti possono interagire con la conoscenza organizzativa attraverso il linguaggio naturale, combinando tecniche di ricerca documentale con capacità generative basate su modelli linguistici.

## Scenario applicativo

Nelle organizzazioni complesse una parte significativa della conoscenza è distribuita in numerose fonti documentali:

- regolamenti e normative;
- procedure operative;
- documentazione tecnica;
- manuali;
- documenti amministrativi;
- basi di conoscenza interne.

La difficoltà principale non è solo conservare queste informazioni, ma renderle facilmente accessibili e interrogabili.

AssistDoc esplora un modello in cui l'utente può porre domande in linguaggio naturale e ottenere risposte contestualizzate sulla base delle informazioni contenute nella documentazione disponibile.

## Obiettivi del progetto

AssistDoc nasce per sperimentare:

- l'integrazione di Large Language Models nei sistemi informativi enterprise;
- l'utilizzo di architetture Retrieval-Augmented Generation (RAG);
- nuove modalità di accesso alla conoscenza organizzativa;
- tecniche di ricerca semantica applicate a documenti aziendali;
- architetture software sicure e scalabili per applicazioni basate su AI.

## Architettura

AssistDoc segue un'architettura applicativa enterprise full-stack. 

Il sistema comprende:

* acquisizione e preparazione documenti;
* indicizzazione semantica;
* recupero dei contenuti rilevanti;
* generazione della risposta tramite LLM;
* interazione conversazionale con la conoscenza aziendale.

## Tecnologie esplorate

* Large Language Models (LLM)
* Retrieval-Augmented Generation (RAG)
* Semantic Search
* Vector Embeddings
* Prompt Engineering

## Possibili applicazioni

* assistenti per documentazione interna;
* supporto alla consultazione di procedure;
* knowledge management aziendale;
* sistemi informativi enterprise.

# Struttura del repository

- `backend/`: Applicazione API Laravel
- `frontend/`: Single Page Application Angular
- `infra/`: Configurazione infrastruttura e deployment
- `specs/`: Specifiche funzionali, piani e documentazione progettuale

## Stato del progetto

- struttura monorepository;
- separazione backend/frontend;
- base applicativa Laravel;
- autenticazione applicativa;
- gestione del contesto tenant;
- fondazione API;
- struttura iniziale per interazione conversazionale.

## API di autenticazione

- `POST /api/v1/auth/login` Autentica un utente locale e restituisce un token con contesto tenant associato
- `GET /api/v1/auth/me` Ripristina il contesto autenticato utilizzato dall'applicazione frontend
- `POST /api/v1/auth/logout` Invalida il token corrente


# RAG profile by machine

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
