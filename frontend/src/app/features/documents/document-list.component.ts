import { CommonModule, DatePipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../core/auth/auth.service';
import { DocumentApiService } from './document-api.service';
import { DocumentListItem } from './document.models';
import { DocumentStatusBadgeComponent } from './document-status-badge.component';

@Component({
  selector: 'app-document-list',
  standalone: true,
  imports: [CommonModule, DatePipe, DocumentStatusBadgeComponent],
  template: `
    <section class="documents-card">
      <div class="documents-card__header">
        <div>
          <h2>Documenti del tenant</h2>
          <p>Monitoraggio dello stato di caricamento, segmentazione e indicizzazione.</p>
        </div>
        <button class="btn btn-outline-secondary" type="button" (click)="refresh()" [disabled]="api.loading()">
          Aggiorna
        </button>
      </div>

      <p *ngIf="api.loading()" class="documents-card__info">Aggiornamento elenco documenti in corso...</p>
      <p *ngIf="api.error()" class="documents-card__error">{{ api.error() }}</p>
      <p *ngIf="!api.loading() && documents().length === 0" class="documents-card__info">
        Nessun documento disponibile per questo tenant.
      </p>

      <article *ngFor="let document of documents()" class="document-row">
        <div class="document-row__meta">
          <div class="document-row__title">
            <strong>{{ document.filename }}</strong>
            <app-document-status-badge [status]="document.status" />
          </div>
          <div class="document-row__details">
            <span>Caricato da {{ document.uploadedBy.fullName }}</span>
            <span>{{ document.uploadedAt | date:'short' }}</span>
            <span *ngIf="document.searchableSegmentsCount !== undefined">
              Segmenti pronti: {{ document.searchableSegmentsCount }}/{{ document.segmentsCount ?? 0 }}
            </span>
            <span *ngIf="document.activeRetrievalModelProfile">Profilo modello: {{ document.activeRetrievalModelProfile.name }}</span>
            <span *ngIf="document.activeChunkingProfile">Segmentazione: {{ document.activeChunkingProfile.name }}</span>
          </div>
          <p *ngIf="document.failureReason" class="document-row__failure">
            {{ document.failureReason }}
          </p>
        </div>

        <button
          *ngIf="canRetry(document)"
          class="btn btn-sm btn-outline-primary"
          type="button"
          (click)="retry(document.id)"
        >
          Riprova
        </button>
      </article>
    </section>
  `,
  styles: [`
    .documents-card {
      background: #fff;
      border: 1px solid #d9e2ec;
      border-radius: 16px;
      padding: 20px;
      display: grid;
      gap: 14px;
    }

    .documents-card__header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
    }

    .documents-card__header h2 {
      margin: 0 0 4px;
      font-size: 1.35rem;
    }

    .documents-card__header p,
    .documents-card__info {
      margin: 0;
      color: #52606d;
    }

    .documents-card__error {
      margin: 0;
      color: #b42318;
    }

    .document-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      padding-top: 16px;
      border-top: 1px solid #edf2f7;
    }

    .document-row:first-of-type {
      border-top: 0;
      padding-top: 0;
    }

    .document-row__meta {
      display: grid;
      gap: 8px;
    }

    .document-row__title {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .document-row__details {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      color: #52606d;
      font-size: 0.92rem;
    }

    .document-row__failure {
      margin: 0;
      color: #b42318;
    }
  `],
})
export class DocumentListComponent implements OnInit {
  private readonly authService = inject(AuthService);
  readonly api = inject(DocumentApiService);
  readonly retrying = signal<string | null>(null);
  readonly documents = computed(() => this.api.documents());

  ngOnInit(): void {
    void this.refresh();
  }

  async refresh(): Promise<void> {
    await this.api.loadDocuments();
  }

  canRetry(document: DocumentListItem): boolean {
    return document.status === 'failed' && this.authService.hasAnyRole(['super-admin', 'operator']);
  }

  async retry(documentId: string): Promise<void> {
    this.retrying.set(documentId);
    try {
      await this.api.retryDocument(documentId);
    } finally {
      this.retrying.set(null);
    }
  }
}
