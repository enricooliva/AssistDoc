# Feature Specification: Text Content Ingestion

**Feature Branch**: `013-text-content-ingestion`  
**Created**: 2026-06-09  
**Status**: Draft  
**Input**: User description: "Gli utenti devono oltre a poter fornire contenuti alla piattaforma sia tramite caricamento di documenti, ad esempio in formato PDF, anche tramite inserimento diretto di testo copiato e incollato in un apposito campo. La piattaforma deve archiviare i contenuti in modo sicuro, estrarne il testo, suddividerlo in porzioni ricercabili e generare gli embedding necessari per consentire il recupero semantico delle informazioni."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Caricare un documento ricercabile (Priority: P1)

Un utente autorizzato carica un documento, come un PDF, e la piattaforma lo acquisisce, ne estrae il testo utile e lo rende disponibile per la ricerca semantica.

**Why this priority**: Il caricamento documentale e la successiva indicizzazione sono il percorso principale già atteso dagli utenti e restano il canale più comune per alimentare la base informativa.

**Independent Test**: Può essere testata caricando un documento valido e verificando che il contenuto venga archiviato, processato e restituito come contenuto ricercabile senza dipendere dall'inserimento manuale di testo.

**Acceptance Scenarios**:

1. **Given** un utente autorizzato con accesso al modulo documenti, **When** carica un PDF contenente testo estraibile, **Then** la piattaforma registra il contenuto, avvia l'elaborazione e lo rende disponibile per il recupero semantico al termine del processo.
2. **Given** un documento caricato correttamente, **When** l'elaborazione termina con successo, **Then** il contenuto viene suddiviso in porzioni ricercabili e associato ai metadati necessari per essere rintracciato e citato.

---

### User Story 2 - Inserire testo copiato e incollato (Priority: P1)

Un utente autorizzato incolla del testo direttamente in un campo dedicato, senza dover creare un file, e la piattaforma tratta quel contenuto con lo stesso livello di sicurezza e ricercabilità previsto per i documenti caricati.

**Why this priority**: L'inserimento diretto di testo è il nuovo valore introdotto dalla feature ed elimina il vincolo di dover preparare un file per ogni contenuto da acquisire.

**Independent Test**: Può essere testata inserendo testo in un nuovo contenitore di contenuto e verificando che venga salvato, segmentato e reso ricercabile senza usare il percorso di upload file.

**Acceptance Scenarios**:

1. **Given** un utente autorizzato con accesso alla raccolta contenuti, **When** incolla testo valido nel campo dedicato e conferma l'inserimento, **Then** la piattaforma archivia il contenuto come nuova fonte informativa ed esegue la stessa pipeline di preparazione prevista per i documenti.
2. **Given** un contenuto testuale inserito manualmente, **When** l'elaborazione termina con successo, **Then** il contenuto è disponibile nella ricerca semantica con lo stesso livello di recuperabilità di un contenuto derivato da file.

---

### User Story 3 - Monitorare esiti e problemi di acquisizione (Priority: P2)

Un utente autorizzato vede se un contenuto acquisito è pronto, fallito o richiede un nuovo tentativo, così può correggere problemi di input senza incertezza sullo stato del materiale fornito.

**Why this priority**: Dopo l'acquisizione, la trasparenza sullo stato riduce errori operativi e consente di gestire subito contenuti vuoti, illeggibili o non indicizzabili.

**Independent Test**: Può essere testata inviando contenuti validi e non validi e verificando che gli stati, i motivi di errore e gli eventuali retry siano coerenti anche senza eseguire ricerche.

**Acceptance Scenarios**:

1. **Given** un contenuto il cui testo non può essere estratto o risulta vuoto, **When** la piattaforma completa il tentativo di elaborazione, **Then** il contenuto viene marcato come fallito con un motivo comprensibile per l'utente.
2. **Given** un contenuto fallito, **When** un utente autorizzato avvia un nuovo tentativo dopo aver corretto l'input, **Then** la piattaforma ripete la preparazione e aggiorna lo stato in base al nuovo esito.

### Edge Cases

- Cosa accade se l'utente prova a salvare testo composto solo da spazi, righe vuote o contenuto privo di valore informativo?
- Cosa accade se un PDF caricato contiene solo immagini o testo non estraibile?
- Cosa accade se lo stesso contenuto viene caricato più volte o viene incollato più volte con minime variazioni?
- Come viene gestito un contenuto molto lungo che supera i limiti consentiti per una singola elaborazione?
- Come viene gestita l'eliminazione di un contenuto già indicizzato affinché segmenti e riferimenti ricercabili non rimangano disponibili?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Il sistema MUST consentire agli utenti autorizzati di fornire nuovi contenuti alla piattaforma tramite caricamento di documenti supportati.
- **FR-002**: Il sistema MUST consentire agli utenti autorizzati di fornire nuovi contenuti alla piattaforma tramite inserimento diretto di testo copiato e incollato in un campo dedicato.
- **FR-003**: Il sistema MUST archiviare in modo sicuro ogni contenuto acquisito, mantenendo separazione per tenant e tracciabilità dell'utente che lo ha fornito.
- **FR-004**: Il sistema MUST distinguere la fonte del contenuto acquisito, almeno tra documento caricato e testo inserito manualmente.
- **FR-005**: Il sistema MUST estrarre o normalizzare il testo del contenuto acquisito prima di avviare la preparazione per la ricerca.
- **FR-006**: Il sistema MUST rifiutare o segnalare come non processabili i contenuti che non producono testo utile dopo l'estrazione o la normalizzazione.
- **FR-007**: Il sistema MUST suddividere il testo acquisito in porzioni ricercabili adatte al recupero semantico.
- **FR-008**: Il sistema MUST generare e memorizzare gli embedding necessari per ogni porzione ricercabile prodotta da un contenuto acquisito con successo.
- **FR-009**: Il sistema MUST rendere ricercabili, nelle stesse funzionalita di recupero semantico, sia i contenuti provenienti da file sia quelli inseriti manualmente.
- **FR-010**: Il sistema MUST mostrare lo stato di lavorazione di ogni contenuto acquisito, inclusi almeno gli stati di attesa, elaborazione, pronto e fallito.
- **FR-011**: Il sistema MUST registrare un motivo leggibile quando un contenuto non puo essere reso ricercabile.
- **FR-012**: Il sistema MUST permettere un nuovo tentativo di elaborazione per contenuti falliti senza richiedere necessariamente una nuova creazione del record.
- **FR-013**: Il sistema MUST eliminare segmenti ricercabili, embedding e riferimenti di ricerca collegati quando un contenuto viene rimosso.
- **FR-014**: Il sistema MUST impedire che contenuti di un tenant siano ricercabili o visibili in un tenant differente.

### API & Contract Requirements *(mandatory for API-backed features)*

- Il sistema MUST esporre un endpoint autenticato per il caricamento di un nuovo contenuto da file, con campi per file e metadati di classificazione gia previsti dal dominio.
- Il sistema MUST esporre un endpoint autenticato per la creazione di un nuovo contenuto tramite testo diretto, con campi per testo, titolo o etichetta utente e metadati di classificazione supportati.
- Il sistema MUST restituire per entrambe le modalita di creazione un contratto uniforme che includa almeno identificativo contenuto, tenant, fonte del contenuto, stato corrente, timestamp principali, eventuale motivo di errore e conteggi derivati utili alla UI.
- Il sistema MUST validare in ingresso la presenza di contenuto utile, la lunghezza massima consentita del testo diretto, il formato dei file supportati e i metadati obbligatori.
- Il sistema MUST restituire errori standardizzati per input non valido, accesso negato, contenuto non trovato e fallimento di elaborazione.
- Il sistema MUST mantenere contratti coerenti tra elenco contenuti, dettaglio contenuto, retry ed eliminazione, includendo la fonte del contenuto e lo stato di ricercabilita.
- Questa feature MUST aggiornare il contratto API esistente dei contenuti introducendo il supporto alla nuova modalita di inserimento testuale senza interrompere il percorso gia esistente di upload documentale.

### Workflow & State Requirements *(mandatory when entities have lifecycle state)*

- Gli stati del contenuto MUST includere almeno: `queued`, `processing`, `ready`, `failed`, `deleted`.
- Le transizioni consentite MUST includere almeno:
  - da `queued` a `processing` quando l'elaborazione inizia;
  - da `processing` a `ready` quando testo, segmenti ed embedding sono disponibili;
  - da `processing` a `failed` quando l'elaborazione non produce un contenuto ricercabile;
  - da `failed` a `queued` quando un utente autorizzato richiede un nuovo tentativo;
  - da qualsiasi stato non eliminato a `deleted` quando un utente autorizzato rimuove il contenuto.
- Ogni transizione MUST registrare tenant, utente attore quando disponibile, timestamp, stato precedente, stato successivo e motivazione o contesto dell'azione.
- Quando un contenuto passa a `ready`, il sistema MUST registrare il completamento della preparazione ricercabile.
- Quando un contenuto passa a `deleted`, il sistema MUST rimuovere gli artefatti ricercabili associati e impedire ulteriori risultati di ricerca collegati.

### UI & Localization Requirements *(mandatory for frontend work)*

- Tutto il testo UI introdotto da questa feature MUST essere definito in italiano (`it-IT`).
- La UI MUST offrire un percorso esplicito per scegliere tra caricamento file e inserimento diretto di testo.
- Il form di inserimento testo MUST mostrare validazioni chiare per campo obbligatorio, testo vuoto, superamento dei limiti consentiti e errori di salvataggio.
- Il form di caricamento file MUST continuare a mostrare validazioni chiare per file mancante, formato non supportato ed errori di caricamento.
- La UI MUST mostrare indicatori di caricamento o elaborazione fino alla conclusione della preparazione del contenuto.
- Le schermate elenco o dettaglio contenuti MUST evidenziare la fonte del contenuto, lo stato corrente e l'eventuale motivo di fallimento.
- Se vengono usate tabelle per i contenuti, queste MUST mantenere paginazione, ordinamento e filtri coerenti con il comportamento esistente.

### Key Entities *(include if feature involves data)*

- **Content Item**: rappresenta una singola fonte informativa fornita da un utente; include tenant, autore, origine del contenuto, etichetta descrittiva, stato, motivi di errore e riferimenti temporali.
- **Content Source Type**: rappresenta la modalita con cui il contenuto e stato fornito; distingue almeno tra file caricato e testo inserito direttamente.
- **Searchable Segment**: rappresenta una porzione del testo di un contenuto pronta per il recupero semantico; mantiene il legame con il contenuto origine e con lo stato di ricercabilita.
- **Preparation Run**: rappresenta un tentativo di trasformare un contenuto grezzo in segmenti ricercabili e embedding, con relativo esito e dettagli di fallimento.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Il 95% dei contenuti testuali incollati che rispettano i vincoli di input viene salvato e portato nello stato `ready` senza interventi manuali aggiuntivi.
- **SC-002**: Il 95% dei documenti supportati contenenti testo estraibile viene portato nello stato `ready` con contenuto disponibile alla ricerca semantica.
- **SC-003**: Un utente autorizzato completa l'inserimento di un contenuto testuale breve entro 2 minuti dal primo accesso al form.
- **SC-004**: Per il 100% dei contenuti nello stato `failed`, l'utente visualizza un motivo di errore comprensibile e puo capire se riprovare o correggere l'input.
- **SC-005**: Dopo la rimozione di un contenuto, nessun suo segmento o riferimento compare piu nei risultati di ricerca del tenant.

## Assumptions

- Il nuovo inserimento diretto di testo viene gestito nello stesso ambito funzionale oggi usato per i documenti caricati.
- Le regole di autorizzazione per creare, vedere, ritentare ed eliminare contenuti restano coerenti con quelle gia esistenti nel modulo documenti.
- Il testo inserito manualmente non richiede una conversione da file, ma deve passare attraverso la stessa preparazione ricercabile usata per i documenti.
- La piattaforma puo riusare i profili di preparazione e di recupero semantico gia in uso per i contenuti documentali.
- La gestione di allegati misti nello stesso record di contenuto non fa parte di questa feature; ogni contenuto nasce da una singola fonte primaria.

## Compliance Notes *(mandatory)*

- La feature deve rispettare i vincoli esistenti di isolamento per tenant, tracciabilita delle azioni e rimozione coerente degli artefatti ricercabili.
- L'estensione del flusso esistente dei contenuti e preferita a una nuova area separata, per mantenere la soluzione nel livello minimo di complessita utile.
- Alternativa piu semplice scartata: consentire solo upload file e richiedere agli utenti di trasformare manualmente il testo in documenti prima dell'inserimento; scartata perche non soddisfa il requisito di inserimento diretto del contenuto.
