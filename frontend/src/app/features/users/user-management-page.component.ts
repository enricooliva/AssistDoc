import { CommonModule } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgbModal, NgbModalModule } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { UserEditDialogComponent } from './user-edit-dialog.component';
import { EnterpriseUserCreatePayload, EnterpriseUserSummary } from './user-management.models';
import { UserManagementApiService } from './user-management-api.service';

@Component({
  selector: 'app-user-management-page',
  standalone: true,
  imports: [CommonModule, FormsModule, NgbModalModule, UserEditDialogComponent],
  template: `
    <section class="users-page">
      <form class="users-page__form" #form="ngForm" (ngSubmit)="createUser()">
        <div class="users-page__heading">
          <div>
            <h2>Utenti enterprise</h2>
            <p>Provisioning, stato ciclo di vita e sblocco account per il tenant corrente.</p>
          </div>
          <div class="users-page__tenant">
            Tenant: <strong>{{ tenantName() }}</strong>
          </div>
        </div>

        <div class="users-page__grid">
          <label>
            Nome completo
            <input [(ngModel)]="draft.full_name" name="full_name" placeholder="Mario Rossi" required />
          </label>

          <label>
            Email
            <input [(ngModel)]="draft.email" name="email" placeholder="mario.rossi@example.it" required />
          </label>

          <label>
            Ruolo
            <select [(ngModel)]="draft.role" name="role">
              <option value="viewer">viewer</option>
              <option value="operator">operator</option>
              <option value="tenant-admin">tenant-admin</option>
            </select>
          </label>

          <label>
            Stato iniziale
            <select [(ngModel)]="draft.status" name="status">
              <option value="active">active</option>
              <option value="provisioned">provisioned</option>
              <option value="suspended">suspended</option>
              <option value="deactivated">deactivated</option>
            </select>
          </label>

          <label>
            Policy MFA
            <select [(ngModel)]="draft.mfa_policy" name="mfa_policy">
              <option value="optional">optional</option>
              <option value="required">required</option>
              <option value="inherited">inherited</option>
            </select>
          </label>

          <label *ngIf="usePassword">
            Password iniziale
            <input [(ngModel)]="draft.password" name="password" placeholder="Minimo 12 caratteri" />
          </label>
        </div>

        <div class="users-page__methods">
          <label><input type="checkbox" [(ngModel)]="useCompanyAccount" name="useCompanyAccount" /> Account aziendale</label>
          <label><input type="checkbox" [(ngModel)]="usePassword" name="usePassword" /> Email e password</label>
        </div>

        <p class="users-page__feedback" *ngIf="message()">{{ message() }}</p>
        <p class="users-page__error" *ngIf="error()">{{ error() }}</p>

        <button class="btn btn-primary" type="submit" [disabled]="submitting() || (!useCompanyAccount && !usePassword)">
          {{ submitting() ? 'Provisioning...' : 'Crea utente' }}
        </button>
      </form>

      <section class="users-page__list">
        <div class="users-page__list-header">
          <div>
            <h3>Elenco utenti</h3>
            <p *ngIf="api.loading()">Caricamento in corso...</p>
          </div>

          <form class="users-page__search" (ngSubmit)="searchUsers()">
            <input
              [(ngModel)]="searchQuery"
              name="searchQuery"
              type="search"
              placeholder="Cerca per nome o email"
            />
            <button class="btn btn-outline-primary btn-sm" type="submit" [disabled]="api.loading()">Cerca</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" (click)="clearSearch()" [disabled]="api.loading() || !hasActiveSearch()">Azzera</button>
          </form>
        </div>

        <article *ngFor="let user of api.items()" class="users-page__item">
          <div>
            <strong>{{ user.full_name }}</strong>
            <p>{{ user.email }} · {{ user.role }} · {{ user.status }}</p>
            <small>Accessi: {{ user.access_methods.join(', ') || 'nessuno' }} · MFA: {{ user.mfa_policy }}</small>
            <small *ngIf="user.lockout">Bloccato fino a {{ user.lockout.locked_until || 'data non disponibile' }}</small>
          </div>
          <div class="users-page__actions">
            <button *ngIf="canEditUser(user)" class="btn btn-outline-primary btn-sm" type="button" (click)="editUser(user)">Modifica</button>
            <button class="btn btn-outline-primary btn-sm" type="button" (click)="setStatus(user.id, 'active')">Attiva</button>
            <button class="btn btn-outline-warning btn-sm" type="button" (click)="setStatus(user.id, 'suspended')">Sospendi</button>
            <button class="btn btn-outline-danger btn-sm" type="button" (click)="setStatus(user.id, 'deactivated')">Disattiva</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" (click)="unlock(user.id)">Sblocca</button>
            <button class="btn btn-outline-danger btn-sm" type="button" (click)="deleteUser(user.id, user.full_name)">Elimina</button>
          </div>
        </article>

        <p class="users-page__empty" *ngIf="!api.loading() && !api.items().length && hasActiveSearch()">Nessun utente corrisponde alla ricerca corrente.</p>
        <p class="users-page__empty" *ngIf="!api.loading() && !api.items().length && !hasActiveSearch()">Nessun utente provisionato per questo tenant.</p>

        <div class="users-page__pagination">
          <button class="btn btn-outline-secondary btn-sm" type="button" (click)="changePage(-1)" [disabled]="api.page() <= 1 || api.loading()">Precedenti</button>
          <span>Pag. {{ api.page() }}/{{ totalPages() }}</span>
          <button class="btn btn-outline-secondary btn-sm" type="button" (click)="changePage(1)" [disabled]="api.page() >= totalPages() || api.loading()">Successive</button>
        </div>
      </section>
    </section>
  `,
  styles: [`
    .users-page { display: grid; gap: 16px; }
    .users-page__form { display: grid; gap: 12px; background: #fff; padding: 16px; border: 1px solid #d7dfe8; border-radius: 8px; }
    .users-page__heading { display: flex; justify-content: space-between; align-items: start; gap: 12px; }
    .users-page__heading h2, .users-page__heading p, .users-page__list-header h3, .users-page__list-header p { margin: 0; }
    .users-page__tenant { color: #5d6b79; }
    .users-page__grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .users-page__grid label, .users-page__methods label { display: grid; gap: 6px; color: #23313f; }
    .users-page__grid input, .users-page__grid select { border: 1px solid #d7dfe8; border-radius: 8px; padding: 10px 12px; }
    .users-page__methods { display: flex; gap: 16px; flex-wrap: wrap; }
    .users-page__feedback { margin: 0; color: #2456d6; }
    .users-page__error, .users-page__empty { margin: 0; color: #b42318; }
    .users-page__list { display: grid; gap: 12px; }
    .users-page__list-header { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
    .users-page__search { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .users-page__search input { border: 1px solid #d7dfe8; border-radius: 8px; padding: 8px 10px; min-width: 260px; }
    .users-page__item { display: flex; justify-content: space-between; align-items: center; gap: 12px; background: #fff; padding: 16px; border: 1px solid #d7dfe8; border-radius: 8px; }
    .users-page__item p { margin: 4px 0; color: #5d6b79; }
    .users-page__item small { display: block; color: #5d6b79; }
    .users-page__actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .users-page__pagination { display: flex; justify-content: flex-end; align-items: center; gap: 12px; }
  `],
})
export class UserManagementPageComponent {
  private readonly authService = inject(AuthService);
  private readonly modal = inject(NgbModal);
  readonly api = inject(UserManagementApiService);
  readonly submitting = signal(false);
  readonly error = signal('');
  readonly message = computed(() => this.api.feedback());
  readonly totalPages = computed(() => Math.max(1, Math.ceil(this.api.total() / this.api.perPage())));
  readonly tenantId = computed(() => this.authService.session()?.tenant.id ?? '');
  readonly tenantName = computed(() => this.authService.session()?.tenant.name ?? 'Tenant corrente');

  useCompanyAccount = true;
  usePassword = false;
  searchQuery = '';
  draft: Omit<EnterpriseUserCreatePayload, 'tenant_id' | 'access_methods'> = {
    full_name: '',
    email: '',
    role: 'viewer' as const,
    status: 'active' as const,
    mfa_policy: 'optional' as const,
    password: '',
  };

  constructor() {
    void this.api.load();
  }

  hasActiveSearch(): boolean {
    return this.searchQuery.trim().length > 0;
  }

  canEditUser(user: EnterpriseUserSummary): boolean {
    return this.authService.session()?.user.role === 'super-admin' || user.role !== 'super-admin';
  }

  async searchUsers(): Promise<void> {
    this.error.set('');

    try {
      await this.api.search(this.searchQuery);
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Ricerca utenti non riuscita.');
    }
  }

  async clearSearch(): Promise<void> {
    this.searchQuery = '';
    await this.searchUsers();
  }

  async createUser(): Promise<void> {
    this.error.set('');
    this.api.clearFeedback();

    if (!this.tenantId()) {
      this.error.set('Tenant non disponibile nella sessione corrente.');
      return;
    }

    this.submitting.set(true);

    try {
      await this.api.create({
        ...this.draft,
        tenant_id: this.tenantId(),
        access_methods: [
          ...(this.useCompanyAccount ? ['company_account' as const] : []),
          ...(this.usePassword ? ['password' as const] : []),
        ],
        password: this.draft.password || undefined,
      });

      this.draft = {
        full_name: '',
        email: '',
        role: 'viewer',
        status: 'active',
        mfa_policy: 'optional',
        password: '',
      };
      this.useCompanyAccount = true;
      this.usePassword = false;
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Provisioning utente non riuscito.');
    } finally {
      this.submitting.set(false);
    }
  }

  async setStatus(userId: string, status: string): Promise<void> {
    this.error.set('');

    try {
      await this.api.updateStatus(userId, status);
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Aggiornamento stato non riuscito.');
    }
  }

  async unlock(userId: string): Promise<void> {
    this.error.set('');

    try {
      await this.api.unlock(userId);
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Sblocco account non riuscito.');
    }
  }

  async deleteUser(userId: string, fullName: string): Promise<void> {
    this.error.set('');

    if (typeof window !== 'undefined') {
      const confirmed = window.confirm(
        `Confermi la soft delete dell'utente ${fullName}? L'utente verrà rimosso dall'elenco attivo ma resterà tracciabile nello storico.`,
      );

      if (!confirmed) {
        return;
      }
    }

    try {
      await this.api.delete(userId);
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Eliminazione utente non riuscita.');
    }
  }

  async editUser(user: EnterpriseUserSummary): Promise<void> {
    this.error.set('');

    const modalRef = this.modal.open(UserEditDialogComponent, {
      size: 'lg',
      centered: true,
      backdrop: 'static',
    });

    modalRef.componentInstance.user = user;

    try {
      await modalRef.result;
    } catch {
      return;
    }
  }

  async changePage(offset: number): Promise<void> {
    const nextPage = this.api.page() + offset;

    if (nextPage < 1 || nextPage > this.totalPages()) {
      return;
    }

    await this.api.load(nextPage, this.api.query());
  }
}
