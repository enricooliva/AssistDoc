import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../core/api/api-url';
import {
  DeleteEnterpriseUserResponse,
  EnterpriseUserCreatePayload,
  EnterpriseUserListResponse,
  EnterpriseUserMutationResponse,
  EnterpriseUserSummary,
  EnterpriseUserUpdatePayload,
} from './user-management.models';

@Injectable({ providedIn: 'root' })
export class UserManagementApiService {
  private readonly http = inject(HttpClient);

  readonly items = signal<EnterpriseUserSummary[]>([]);
  readonly loading = signal(false);
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(0);
  readonly feedback = signal('');
  readonly query = signal('');

  async load(page = this.page(), query = this.query()): Promise<void> {
    this.loading.set(true);
    try {
      const response = await firstValueFrom(
        this.http.get<EnterpriseUserListResponse>(apiUrl('/api/v1/users'), {
          params: {
            page,
            perPage: this.perPage(),
            ...(query.trim() ? { query: query.trim() } : {}),
          },
        }),
      );

      if (response.items.length === 0 && response.total > 0 && page > 1) {
        await this.load(page - 1, query);
        return;
      }

      this.items.set(response.items);
      this.page.set(response.page);
      this.perPage.set(response.perPage);
      this.total.set(response.total);
      this.query.set(query);
    } finally {
      this.loading.set(false);
    }
  }

  async search(query: string): Promise<void> {
    await this.load(1, query);
  }

  async create(payload: EnterpriseUserCreatePayload): Promise<void> {
    try {
      await firstValueFrom(this.http.post(apiUrl('/api/v1/users'), payload));
      this.feedback.set('Utente provisionato correttamente.');
      await this.load(1, this.query());
    } catch (error) {
      throw new Error(this.extractErrorMessage(error, 'Provisioning utente non riuscito.'));
    }
  }

  async update(userId: string, payload: EnterpriseUserUpdatePayload): Promise<EnterpriseUserSummary> {
    try {
      const response = await firstValueFrom(
        this.http.patch<EnterpriseUserMutationResponse>(apiUrl(`/api/v1/users/${userId}`), payload),
      );

      this.feedback.set('Utente aggiornato correttamente.');
      await this.load(this.page(), this.query());

      return response.user;
    } catch (error) {
      throw new Error(this.extractErrorMessage(error, 'Aggiornamento utente non riuscito.'));
    }
  }

  async updateStatus(userId: string, status: string): Promise<void> {
    try {
      await firstValueFrom(this.http.patch(apiUrl(`/api/v1/users/${userId}/status`), { status }));
      this.feedback.set('Stato utente aggiornato.');
      await this.load(this.page(), this.query());
    } catch (error) {
      throw new Error(this.extractErrorMessage(error, 'Aggiornamento stato non riuscito.'));
    }
  }

  async unlock(userId: string): Promise<void> {
    try {
      await firstValueFrom(this.http.post(apiUrl(`/api/v1/users/${userId}/unlock`), {}));
      this.feedback.set('Account sbloccato.');
      await this.load(this.page(), this.query());
    } catch (error) {
      throw new Error(this.extractErrorMessage(error, 'Sblocco account non riuscito.'));
    }
  }

  async delete(userId: string): Promise<DeleteEnterpriseUserResponse> {
    try {
      const response = await firstValueFrom(
        this.http.delete<DeleteEnterpriseUserResponse>(apiUrl(`/api/v1/users/${userId}`)),
      );
      this.feedback.set('Utente eliminato con soft delete.');
      await this.load(this.page(), this.query());
      return response;
    } catch (error) {
      throw new Error(this.extractErrorMessage(error, 'Eliminazione utente non riuscita.'));
    }
  }

  clearFeedback(): void {
    this.feedback.set('');
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.total() / this.perPage()));
  }

  private extractErrorMessage(error: unknown, fallback: string): string {
    if (!(error instanceof HttpErrorResponse)) {
      return fallback;
    }

    const fieldErrors = error.error?.error?.fieldErrors as Record<string, string[] | string> | undefined;

    if (fieldErrors) {
      const messages = Object.values(fieldErrors)
        .flatMap((value) => Array.isArray(value) ? value : [value])
        .filter((value): value is string => typeof value === 'string' && value.trim().length > 0);

      if (messages.length > 0) {
        return messages.join(' ');
      }
    }

    return error.error?.error?.message ?? fallback;
  }
}
