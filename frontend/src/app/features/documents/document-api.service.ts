import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../core/api/api-url';
import {
  ChunkingProfile,
  DocumentDeleteResponse,
  ChunkingProfileListResponse,
  DocumentListItem,
  DocumentListResponse,
  DocumentRetryResponse,
  DocumentTextCreatePayload,
  PreparationRunDetail,
  PreparationRunResponse,
} from './document.models';

@Injectable({ providedIn: 'root' })
export class DocumentApiService {
  private readonly http = inject(HttpClient);

  readonly documents = signal<DocumentListItem[]>([]);
  readonly page = signal(1);
  readonly perPage = signal(25);
  readonly total = signal(0);
  readonly chunkingProfiles = signal<ChunkingProfile[]>([]);
  readonly loading = signal(false);
  readonly deletingDocumentId = signal<string | null>(null);
  readonly error = signal('');

  async loadDocuments(page = this.page(), perPage = this.perPage()): Promise<void> {
    this.loading.set(true);
    this.error.set('');

    try {
      const response = await firstValueFrom(
        this.http.get<DocumentListResponse>(apiUrl('/api/v1/documents'), {
          params: new HttpParams()
            .set('page', String(page))
            .set('perPage', String(perPage)),
        }),
      );

      this.documents.set(response.items);
      this.page.set(response.page);
      this.perPage.set(response.perPage);
      this.total.set(response.total);
    } catch (error) {
      this.error.set(this.extractError(error, 'Impossibile caricare i documenti.'));
      throw error;
    } finally {
      this.loading.set(false);
    }
  }

  async uploadDocument(file: File, tags: string[] = []): Promise<DocumentListItem> {
    this.error.set('');

    const formData = new FormData();
    formData.append('file', file, file.name);
    tags.forEach((tag) => formData.append('tags[]', tag));

    try {
      const document = await firstValueFrom(
        this.http.post<DocumentListItem>(apiUrl('/api/v1/documents'), formData),
      );

      this.documents.update((items) => [document, ...items.filter((item) => item.id !== document.id)]);

      return document;
    } catch (error) {
      const message = this.extractError(error, 'Caricamento non riuscito.');
      this.error.set(message);
      throw new Error(message);
    }
  }

  async createTextDocument(payload: DocumentTextCreatePayload): Promise<DocumentListItem> {
    this.error.set('');

    try {
      const document = await firstValueFrom(
        this.http.post<DocumentListItem>(apiUrl('/api/v1/documents/text'), payload),
      );

      this.documents.update((items) => [document, ...items.filter((item) => item.id !== document.id)]);

      return document;
    } catch (error) {
      const message = this.extractError(error, 'Salvataggio del testo non riuscito.');
      this.error.set(message);
      throw new Error(message);
    }
  }

  async retryDocument(documentId: string): Promise<DocumentRetryResponse> {
    this.error.set('');

    try {
      const response = await firstValueFrom(
        this.http.post<DocumentRetryResponse>(apiUrl(`/api/v1/documents/${documentId}/retry`), {}),
      );

      await this.loadDocuments();
      return response;
    } catch (error) {
      const message = this.extractError(error, 'Ritento dell\'indicizzazione non riuscito.');
      this.error.set(message);
      throw new Error(message);
    }
  }

  async loadChunkingProfiles(): Promise<ChunkingProfile[]> {
    const response = await firstValueFrom(
      this.http.get<ChunkingProfileListResponse>(apiUrl('/api/v1/rag/chunking-profiles')),
    );
    this.chunkingProfiles.set(response.data);

    return response.data;
  }

  async startPreparationRun(
    documentId: string,
    chunkingProfileId: string,
  ): Promise<PreparationRunResponse> {
    const response = await firstValueFrom(
      this.http.post<PreparationRunResponse>(apiUrl(`/api/v1/documents/${documentId}/preparation-runs`), {
        chunkingProfileId,
      }),
    );
    await this.loadDocuments();

    return response;
  }

  async loadPreparationRun(documentId: string, runId: string): Promise<PreparationRunDetail> {
    return await firstValueFrom(
      this.http.get<PreparationRunDetail>(apiUrl(`/api/v1/documents/${documentId}/preparation-runs/${runId}`)),
    );
  }

  async deleteDocument(documentId: string): Promise<DocumentDeleteResponse> {
    this.deletingDocumentId.set(documentId);
    this.error.set('');

    try {
      const response = await firstValueFrom(
        this.http.delete<DocumentDeleteResponse>(apiUrl(`/api/v1/documents/${documentId}`)),
      );

      this.documents.update((items) => items.filter((item) => item.id !== documentId));
      this.total.update((current) => Math.max(0, current - 1));

      return response;
    } catch (error) {
      const message = this.extractError(error, 'Eliminazione non riuscita.');
      this.error.set(message);
      throw new Error(message);
    } finally {
      this.deletingDocumentId.set(null);
    }
  }

  private extractError(error: unknown, fallback: string): string {
    if (error instanceof HttpErrorResponse) {
      return error.error?.error?.message ?? fallback;
    }

    return fallback;
  }
}
