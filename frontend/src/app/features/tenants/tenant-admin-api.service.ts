import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../core/api/api-url';
import {
  TenantDetailResponse,
  TenantListResponse,
  TenantMembershipSummary,
  TenantProvisionPayload,
  TenantProvisionResponse,
  TenantUserProvisionPayload,
  TenantUserProvisionResponse,
} from './tenant-admin.models';

@Injectable({ providedIn: 'root' })
export class TenantAdminApiService {
  private readonly http = inject(HttpClient);

  readonly items = signal<TenantMembershipSummary[]>([]);
  readonly selectedTenant = signal<TenantDetailResponse | null>(null);
  readonly loading = signal(false);
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(0);
  readonly feedback = signal('');
  readonly selectedTenantId = signal<string | null>(null);

  async load(page = this.page()): Promise<void> {
    this.loading.set(true);
    try {
      const response = await firstValueFrom(
        this.http.get<TenantListResponse>(apiUrl('/api/v1/tenants'), {
          params: {
            page,
            perPage: this.perPage(),
          },
        }),
      );

      if (response.items.length === 0 && response.total > 0 && page > 1) {
        await this.load(page - 1);
        return;
      }

      this.items.set(response.items);
      this.page.set(response.page);
      this.perPage.set(response.perPage);
      this.total.set(response.total);

      if (!this.selectedTenantId() && response.items.length > 0) {
        this.selectedTenantId.set(response.items[0].id);
        await this.loadTenant(response.items[0].id);
      }

      if (this.selectedTenantId()) {
        await this.loadTenant(this.selectedTenantId() as string);
      }
    } finally {
      this.loading.set(false);
    }
  }

  async loadTenant(tenantId: string): Promise<void> {
    if (!tenantId) {
      this.selectedTenant.set(null);
      return;
    }

    const response = await firstValueFrom(
      this.http.get<TenantDetailResponse>(apiUrl(`/api/v1/tenants/${tenantId}`)),
    );

    this.selectedTenant.set(response);
    this.selectedTenantId.set(tenantId);
  }

  async selectTenant(tenantId: string): Promise<void> {
    this.selectedTenantId.set(tenantId);
    await this.loadTenant(tenantId);
  }

  async createTenant(payload: TenantProvisionPayload): Promise<TenantProvisionResponse> {
    try {
      const response = await firstValueFrom(
        this.http.post<TenantProvisionResponse>(apiUrl('/api/v1/tenants'), payload),
      );

      this.feedback.set('Tenant creato con admin iniziale.');
      await this.load(1);
      this.selectedTenantId.set(response.tenant.id);
      await this.loadTenant(response.tenant.id);

      return response;
    } catch (error) {
      throw new Error(this.extractErrorMessage(error, 'Creazione tenant non riuscita.'));
    }
  }

  async addUser(payload: TenantUserProvisionPayload): Promise<TenantUserProvisionResponse> {
    try {
      const response = await firstValueFrom(
        this.http.post<TenantUserProvisionResponse>(apiUrl(`/api/v1/tenants/${payload.tenant_id}/users`), payload),
      );

      this.feedback.set('Utente aggiunto al tenant.');
      await this.load(this.page());
      await this.loadTenant(payload.tenant_id);

      return response;
    } catch (error) {
      throw new Error(this.extractErrorMessage(error, 'Provisioning utente non riuscito.'));
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
