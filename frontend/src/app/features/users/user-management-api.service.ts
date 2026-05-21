import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../core/api/api-url';
import { EnterpriseUserCreatePayload, EnterpriseUserListResponse, EnterpriseUserSummary } from './user-management.models';

@Injectable({ providedIn: 'root' })
export class UserManagementApiService {
  private readonly http = inject(HttpClient);

  readonly items = signal<EnterpriseUserSummary[]>([]);
  readonly loading = signal(false);
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(0);
  readonly feedback = signal('');

  async load(page = this.page()): Promise<void> {
    this.loading.set(true);
    try {
      const response = await firstValueFrom(
        this.http.get<EnterpriseUserListResponse>(apiUrl('/api/v1/users'), {
          params: {
            page,
            perPage: this.perPage(),
          },
        }),
      );

      this.items.set(response.items);
      this.page.set(response.page);
      this.perPage.set(response.perPage);
      this.total.set(response.total);
    } finally {
      this.loading.set(false);
    }
  }

  async create(payload: EnterpriseUserCreatePayload): Promise<void> {
    await firstValueFrom(this.http.post(apiUrl('/api/v1/users'), payload));
    this.feedback.set('Utente provisionato correttamente.');
    await this.load(1);
  }

  async updateStatus(userId: string, status: string): Promise<void> {
    await firstValueFrom(this.http.patch(apiUrl(`/api/v1/users/${userId}/status`), { status }));
    this.feedback.set('Stato utente aggiornato.');
    await this.load(this.page());
  }

  async unlock(userId: string): Promise<void> {
    await firstValueFrom(this.http.post(apiUrl(`/api/v1/users/${userId}/unlock`), {}));
    this.feedback.set('Account sbloccato.');
    await this.load(this.page());
  }

  clearFeedback(): void {
    this.feedback.set('');
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.total() / this.perPage()));
  }
}
