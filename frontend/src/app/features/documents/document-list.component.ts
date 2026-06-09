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
          <p>Consulta i documenti del tenant con tag, uploader e paginazione, senza includere i record eliminati.</p>
        </div>
        <button class="btn btn-outline-secondary" type="button" (click)="refresh()" [disabled]="api.loading()">
          Aggiorna
        </button>
      </div>

      <p *ngIf="api.loading()" class="documents-card__info">Caricamento elenco documenti in corso...</p>
      <p *ngIf="api.error()" class="documents-card__error">{{ api.error() }}</p>
      <p *ngIf="!api.loading() && documents().length === 0" class="documents-card__info">
        Nessun documento disponibile per questo tenant.
      </p>

      <div *ngIf="documents().length > 0" class="document-list">
        <article *ngFor="let document of documents()" class="document-row">
          <div class="document-row__meta">
            <div class="document-row__title">
              <strong>{{ document.filename }}</strong>
              <app-document-status-badge [status]="document.status" />
            </div>

            <div class="document-row__details">
              <span>Fonte: {{ sourceLabel(document.sourceType) }}</span>
              <span>Caricato da {{ document.uploadedBy.fullName }}</span>
              <span>{{ document.uploadedAt | date:'short' }}</span>
              <span>Tag: {{ renderTags(document.tags) }}</span>
              <span *ngIf="document.deletedAt">Eliminato il {{ document.deletedAt | date:'short' }}</span>
            </div>

            <p *ngIf="document.failureReason" class="document-row__failure">
              {{ document.failureReason }}
            </p>

            <div *ngIf="pendingDeleteId() === document.id" class="document-row__confirm">
              <p>La rimozione sarà una soft delete: il documento scomparirà dall'elenco standard ma resterà tracciabile.</p>
              <div class="document-row__actions">
                <button
                  class="btn btn-sm btn-danger"
                  type="button"
                  (click)="confirmDelete(document.id)"
                  [disabled]="api.deletingDocumentId() === document.id"
                >
                  Conferma eliminazione
                </button>
                <button class="btn btn-sm btn-outline-secondary" type="button" (click)="cancelDelete()">
                  Annulla
                </button>
              </div>
            </div>
          </div>

          <div class="document-row__actions">
            <button
              *ngIf="canRetry() && document.status === 'failed'"
              class="btn btn-sm btn-outline-primary"
              type="button"
              (click)="retry(document.id)"
              [disabled]="api.loading() || api.deletingDocumentId() !== null"
            >
              Riprova
            </button>
            <button
              *ngIf="canDelete() && pendingDeleteId() !== document.id"
              class="btn btn-sm btn-outline-danger"
              type="button"
              (click)="requestDelete(document)"
              [disabled]="api.deletingDocumentId() !== null"
            >
              Elimina
            </button>
          </div>
        </article>
      </div>

      <div class="documents-card__pagination" *ngIf="documents().length > 0">
        <button class="btn btn-outline-secondary btn-sm" type="button" (click)="previousPage()" [disabled]="!hasPreviousPage() || api.loading()">
          Precedente
        </button>
        <span>Pagina {{ api.page() }} di {{ totalPages() }}</span>
        <button class="btn btn-outline-secondary btn-sm" type="button" (click)="nextPage()" [disabled]="!hasNextPage() || api.loading()">
          Successiva
        </button>
      </div>
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

    .document-list {
      display: grid;
      gap: 12px;
    }

    .document-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      padding: 16px 0 0;
      border-top: 1px solid #edf2f7;
    }

    .document-row:first-of-type {
      border-top: 0;
      padding-top: 0;
    }

    .document-row__meta {
      display: grid;
      gap: 8px;
      flex: 1;
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

    .document-row__source {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      background: #edf2ff;
      color: #2f3a8f;
      padding: 4px 10px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .document-row__confirm {
      border: 1px solid #f3c7c7;
      background: #fff7f7;
      border-radius: 12px;
      padding: 12px 14px;
      display: grid;
      gap: 10px;
    }

    .document-row__confirm p {
      margin: 0;
      color: #8a1f1f;
    }

    .document-row__actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      justify-content: flex-end;
    }

    .documents-card__pagination {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      border-top: 1px solid #edf2f7;
      padding-top: 14px;
      color: #52606d;
    }
  `],
})
export class DocumentListComponent implements OnInit {
  private readonly authService = inject(AuthService);
  readonly api = inject(DocumentApiService);
  readonly pendingDeleteId = signal<string | null>(null);
  readonly documents = computed(() => this.api.documents());

  ngOnInit(): void {
    void this.refresh();
  }

  canDelete(): boolean {
    return this.authService.hasAnyRole(['super-admin', 'operator']);
  }

  canRetry(): boolean {
    return this.authService.hasAnyRole(['super-admin', 'operator']);
  }

  async refresh(): Promise<void> {
    const targetPage = this.api.page();
    await this.api.loadDocuments(targetPage, this.api.perPage());
  }

  async nextPage(): Promise<void> {
    if (!this.hasNextPage()) {
      return;
    }

    await this.api.loadDocuments(this.api.page() + 1, this.api.perPage());
  }

  async previousPage(): Promise<void> {
    if (!this.hasPreviousPage()) {
      return;
    }

    await this.api.loadDocuments(this.api.page() - 1, this.api.perPage());
  }

  requestDelete(document: DocumentListItem): void {
    this.pendingDeleteId.set(document.id);
  }

  cancelDelete(): void {
    this.pendingDeleteId.set(null);
  }

  async confirmDelete(documentId: string): Promise<void> {
    try {
      await this.api.deleteDocument(documentId);
      this.pendingDeleteId.set(null);

      if (this.api.documents().length === 0 && this.api.page() > 1) {
        await this.api.loadDocuments(this.api.page() - 1, this.api.perPage());
        return;
      }

      await this.api.loadDocuments(this.api.page(), this.api.perPage());
    } catch {
      this.pendingDeleteId.set(null);
    }
  }

  async retry(documentId: string): Promise<void> {
    try {
      await this.api.retryDocument(documentId);
      this.pendingDeleteId.set(null);
    } catch {
      this.pendingDeleteId.set(null);
    }
  }

  sourceLabel(sourceType: DocumentListItem['sourceType']): string {
    return sourceType === 'text' ? 'Testo diretto' : 'File caricato';
  }

  hasPreviousPage(): boolean {
    return this.api.page() > 1;
  }

  hasNextPage(): boolean {
    return this.api.page() * this.api.perPage() < this.api.total();
  }

  totalPages(): number {
    return Math.max(1, Math.ceil(this.api.total() / this.api.perPage()));
  }

  renderTags(tags: string[]): string {
    return tags.length > 0 ? tags.join(', ') : 'Nessun tag';
  }
}
