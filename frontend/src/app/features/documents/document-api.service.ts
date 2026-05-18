import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../core/api/api-url';
import {
  ChunkingProfile,
  ChunkingProfileListResponse,
  DocumentListItem,
  DocumentListResponse,
  DocumentRetryResponse,
  PreparationRunDetail,
  PreparationRunResponse,
} from './document.models';

@Injectable({ providedIn: 'root' })
export class DocumentApiService {
  private readonly http = inject(HttpClient);

  readonly documents = signal<DocumentListItem[]>([]);
  readonly chunkingProfiles = signal<ChunkingProfile[]>([]);
  readonly loading = signal(false);
  readonly error = signal('');

  async loadDocuments(): Promise<void> {
    this.loading.set(true);
    this.error.set('');

    try {
      const response = await firstValueFrom(
        this.http.get<DocumentListResponse>(apiUrl('/api/v1/documents')),
      );

      this.documents.set(response.items);
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

  private extractError(error: unknown, fallback: string): string {
    if (error instanceof HttpErrorResponse) {
      return error.error?.error?.message ?? fallback;
    }

    return fallback;
  }
}
