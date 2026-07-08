import { CommonModule } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgbModal, NgbModalModule } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { TenantAdminApiService } from './tenant-admin-api.service';
import { TenantMembershipSummary, TenantProvisionPayload, TenantUserProvisionPayload } from './tenant-admin.models';
import { TenantEditDialogComponent } from './tenant-edit-dialog.component';

@Component({
  selector: 'app-tenant-admin-page',
  standalone: true,
  imports: [CommonModule, FormsModule, NgbModalModule, TenantEditDialogComponent],
  template: `
    <section class="tenant-admin-page">
      <header class="tenant-admin-page__header">
        <div>
          <h2>Tenant</h2>
          <p>Creazione tenant, provisioning dell'admin iniziale e aggiunta utenti.</p>
        </div>
        <div class="tenant-admin-page__context">
          Tenant corrente: <strong>{{ currentTenantName() }}</strong>
        </div>
      </header>

      <div class="tenant-admin-page__grid">
        <form class="tenant-admin-card" (ngSubmit)="createTenant()">
          <h3>Nuovo tenant</h3>
          <label>
            Nome tenant
            <input [(ngModel)]="tenantDraft.tenant_name" name="tenant_name" placeholder="Società Demo" required />
          </label>
          <label>
            Slug tenant
            <input [(ngModel)]="tenantDraft.tenant_slug" name="tenant_slug" placeholder="societa-demo" required />
          </label>
          <label>
            Nome admin iniziale
            <input [(ngModel)]="tenantDraft.admin_full_name" name="admin_full_name" placeholder="Mario Rossi" required />
          </label>
          <label>
            Email admin iniziale
            <input [(ngModel)]="tenantDraft.admin_email" name="admin_email" placeholder="mario.rossi@example.it" required />
          </label>
          <label>
            Ruolo admin
            <input name="admin_role" [value]="tenantDraft.admin_role" readonly />
          </label>
          <label>
            Stato admin
            <select [(ngModel)]="tenantDraft.admin_status" name="admin_status">
              <option value="active">active</option>
              <option value="provisioned">provisioned</option>
              <option value="suspended">suspended</option>
              <option value="locked">locked</option>
              <option value="deactivated">deactivated</option>
            </select>
          </label>
          <label>
            Policy MFA
            <select [(ngModel)]="tenantDraft.admin_mfa_policy" name="admin_mfa_policy">
              <option value="optional">optional</option>
              <option value="required">required</option>
              <option value="inherited">inherited</option>
            </select>
          </label>
          <label *ngIf="tenantAccess.includes('password')">
            Password iniziale
            <input [(ngModel)]="tenantDraft.admin_password" name="admin_password" placeholder="Minimo 12 caratteri" />
          </label>
          <div class="tenant-admin-page__methods">
            <label><input type="checkbox" [checked]="tenantAccess.includes('company_account')" (change)="toggleTenantAccess('company_account', $any($event.target).checked)" /> Account aziendale</label>
            <label><input type="checkbox" [checked]="tenantAccess.includes('password')" (change)="toggleTenantAccess('password', $any($event.target).checked)" /> Email e password</label>
          </div>
          <p class="tenant-admin-page__feedback" *ngIf="message()">{{ message() }}</p>
          <p class="tenant-admin-page__error" *ngIf="error()">{{ error() }}</p>
          <button class="btn btn-primary" type="submit" [disabled]="saving() || tenantAccess.length === 0">
            {{ saving() ? 'Creo tenant...' : 'Crea tenant' }}
          </button>
        </form>

        <form class="tenant-admin-card" (ngSubmit)="addUser()">
          <h3>Aggiungi utente</h3>
          <label>
            Tenant
            <select [(ngModel)]="userDraft.tenant_id" name="tenant_id" (change)="selectTenant(userDraft.tenant_id)" required>
              <option value="">Seleziona tenant</option>
              <option *ngFor="let tenant of api.items()" [value]="tenant.id">{{ tenant.name }} ({{ tenant.slug }})</option>
            </select>
          </label>
          <label>
            Nome completo
            <input [(ngModel)]="userDraft.full_name" name="full_name" placeholder="Luigi Bianchi" required />
          </label>
          <label>
            Email
            <input [(ngModel)]="userDraft.email" name="email" placeholder="luigi.bianchi@example.it" required />
          </label>
          <label>
            Ruolo
            <select [(ngModel)]="userDraft.role" name="role">
              <option value="viewer">viewer</option>
              <option value="operator">operator</option>
              <option value="tenant-admin">tenant-admin</option>
            </select>
          </label>
          <label>
            Stato iniziale
            <select [(ngModel)]="userDraft.status" name="status">
              <option value="active">active</option>
              <option value="provisioned">provisioned</option>
              <option value="suspended">suspended</option>
              <option value="locked">locked</option>
              <option value="deactivated">deactivated</option>
            </select>
          </label>
          <label>
            Policy MFA
            <select [(ngModel)]="userDraft.mfa_policy" name="mfa_policy">
              <option value="optional">optional</option>
              <option value="required">required</option>
              <option value="inherited">inherited</option>
            </select>
          </label>
          <label *ngIf="userAccess.includes('password')">
            Password iniziale
            <input [(ngModel)]="userDraft.password" name="password" placeholder="Minimo 12 caratteri" />
          </label>
          <div class="tenant-admin-page__methods">
            <label><input type="checkbox" [checked]="userAccess.includes('company_account')" (change)="toggleUserAccess('company_account', $any($event.target).checked)" /> Account aziendale</label>
            <label><input type="checkbox" [checked]="userAccess.includes('password')" (change)="toggleUserAccess('password', $any($event.target).checked)" /> Email e password</label>
          </div>
          <button class="btn btn-primary" type="submit" [disabled]="saving() || !userDraft.tenant_id || userAccess.length === 0">
            {{ saving() ? 'Aggiungo utente...' : 'Aggiungi utente' }}
          </button>
        </form>
      </div>

      <section class="tenant-admin-card">
        <div class="tenant-admin-page__list-header">
          <div>
            <h3>Tenants</h3>
            <p *ngIf="api.loading()">Caricamento in corso...</p>
          </div>
          <div class="tenant-admin-page__pagination">
            <button class="btn btn-outline-secondary btn-sm" type="button" (click)="changePage(-1)" [disabled]="api.page() <= 1 || api.loading()">Precedenti</button>
            <span>Pag. {{ api.page() }}/{{ api.totalPages }}</span>
            <button class="btn btn-outline-secondary btn-sm" type="button" (click)="changePage(1)" [disabled]="api.page() >= api.totalPages || api.loading()">Successive</button>
          </div>
        </div>

        <article *ngFor="let tenant of api.items()" class="tenant-admin-page__tenant" [class.tenant-admin-page__tenant--selected]="tenant.id === api.selectedTenantId()">
          <button type="button" class="tenant-admin-page__tenant-button" (click)="selectTenant(tenant.id)">
            <strong>{{ tenant.name }}</strong>
            <span>{{ tenant.slug }} · {{ tenant.status }}</span>
            <small>Utenti: {{ tenant.member_count }} · Admin: {{ tenant.admin_count }} · Ultimo provisioning: {{ tenant.last_provisioned_at || 'n/d' }}</small>
          </button>
        </article>
      </section>

      <section class="tenant-admin-card" *ngIf="api.selectedTenant() as selected">
        <div class="tenant-admin-page__list-header">
          <div>
            <h3>Dettaglio tenant</h3>
            <p>{{ selected.tenant.name }} · {{ selected.tenant.status }}</p>
          </div>
          <button class="btn btn-outline-primary btn-sm" type="button" (click)="editTenant(selected.tenant)">
            Modifica tenant
          </button>
        </div>
        <div class="tenant-admin-page__members">
          <article *ngFor="let member of selected.members" class="tenant-admin-page__member">
            <strong>{{ member.full_name }}</strong>
            <span>{{ member.email }} · {{ member.role }} · {{ member.status }}</span>
          </article>
          <p class="tenant-admin-page__empty" *ngIf="!selected.members.length">Nessun membro registrato per questo tenant.</p>
        </div>
      </section>
    </section>
  `,
  styles: [`
    .tenant-admin-page { display: grid; gap: 16px; }
    .tenant-admin-page__header { display: flex; justify-content: space-between; align-items: start; gap: 12px; }
    .tenant-admin-page__header h2, .tenant-admin-page__header p, .tenant-admin-card h3, .tenant-admin-card p, .tenant-admin-page__list-header h3 { margin: 0; }
    .tenant-admin-page__context { color: #5d6b79; }
    .tenant-admin-page__grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
    .tenant-admin-card { display: grid; gap: 12px; padding: 16px; border: 1px solid #d7dfe8; border-radius: 12px; background: #fff; }
    .tenant-admin-card label { display: grid; gap: 6px; color: #23313f; }
    .tenant-admin-card input, .tenant-admin-card select { border: 1px solid #d7dfe8; border-radius: 8px; padding: 10px 12px; }
    .tenant-admin-page__methods { display: flex; gap: 16px; flex-wrap: wrap; }
    .tenant-admin-page__feedback { margin: 0; color: #2456d6; }
    .tenant-admin-page__error, .tenant-admin-page__empty { margin: 0; color: #b42318; }
    .tenant-admin-page__list-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .tenant-admin-page__pagination { display: flex; align-items: center; gap: 12px; }
    .tenant-admin-page__tenant { border-top: 1px solid #eef2f7; }
    .tenant-admin-page__tenant-button { display: grid; width: 100%; text-align: left; background: transparent; border: 0; padding: 12px 0; color: #1c2430; }
    .tenant-admin-page__tenant-button span, .tenant-admin-page__tenant-button small { color: #5d6b79; }
    .tenant-admin-page__tenant--selected .tenant-admin-page__tenant-button strong { color: #0f172a; }
    .tenant-admin-page__members { display: grid; gap: 8px; }
    .tenant-admin-page__member { display: grid; gap: 2px; padding: 10px 12px; border: 1px solid #e4eaf0; border-radius: 10px; }
    .tenant-admin-page__member span { color: #5d6b79; }
  `],
})
export class TenantAdminPageComponent {
  private readonly authService = inject(AuthService);
  private readonly modal = inject(NgbModal);
  readonly api = inject(TenantAdminApiService);
  readonly saving = signal(false);
  readonly error = signal('');
  readonly message = computed(() => this.api.feedback());
  readonly currentTenantName = computed(() => this.authService.session()?.tenant.name ?? 'Tenant corrente');

  tenantAccess: Array<'company_account' | 'password'> = ['company_account', 'password'];
  userAccess: Array<'company_account' | 'password'> = ['company_account', 'password'];
  tenantDraft: TenantProvisionPayload = {
    tenant_name: '',
    tenant_slug: '',
    admin_full_name: '',
    admin_email: '',
    admin_role: 'tenant-admin',
    admin_access_methods: [...this.tenantAccess],
    admin_status: 'active',
    admin_mfa_policy: 'optional',
    admin_password: '',
  };
  userDraft: TenantUserProvisionPayload = {
    tenant_id: '',
    full_name: '',
    email: '',
    role: 'viewer',
    access_methods: [...this.userAccess],
    status: 'active',
    mfa_policy: 'optional',
    password: '',
  };

  constructor() {
    void this.api.load();
  }

  async createTenant(): Promise<void> {
    this.error.set('');
    this.api.clearFeedback();
    this.saving.set(true);

    try {
      const response = await this.api.createTenant({
        ...this.tenantDraft,
        admin_access_methods: [...this.tenantAccess],
        admin_password: this.tenantDraft.admin_password || undefined,
      });

      this.userDraft.tenant_id = response.tenant.id;
      this.resetTenantDraft();
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Creazione tenant non riuscita.');
    } finally {
      this.saving.set(false);
    }
  }

  async addUser(): Promise<void> {
    this.error.set('');
    this.api.clearFeedback();
    this.saving.set(true);

    try {
      await this.api.addUser({
        ...this.userDraft,
        access_methods: [...this.userAccess],
        password: this.userDraft.password || undefined,
      });
      this.resetUserDraft(this.userDraft.tenant_id);
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Provisioning utente non riuscito.');
    } finally {
      this.saving.set(false);
    }
  }

  async selectTenant(tenantId: string): Promise<void> {
    this.userDraft.tenant_id = tenantId;
    await this.api.selectTenant(tenantId);
  }

  async changePage(delta: number): Promise<void> {
    await this.api.load(this.api.page() + delta);
  }

  editTenant(tenant: TenantMembershipSummary): void {
    const modalRef = this.modal.open(TenantEditDialogComponent, {
      centered: true,
      size: 'lg',
    });
    modalRef.componentInstance.tenant = tenant;
    void modalRef.result.catch(() => undefined);
  }

  toggleTenantAccess(method: 'company_account' | 'password', enabled: boolean): void {
    this.tenantAccess = this.toggleAccess(this.tenantAccess, method, enabled);
    this.tenantDraft.admin_access_methods = [...this.tenantAccess];
  }

  toggleUserAccess(method: 'company_account' | 'password', enabled: boolean): void {
    this.userAccess = this.toggleAccess(this.userAccess, method, enabled);
    this.userDraft.access_methods = [...this.userAccess];
  }

  private toggleAccess(
    current: Array<'company_account' | 'password'>,
    method: 'company_account' | 'password',
    enabled: boolean,
  ): Array<'company_account' | 'password'> {
    const next = new Set(current);
    if (enabled) {
      next.add(method);
    } else {
      next.delete(method);
    }
    return Array.from(next);
  }

  private resetTenantDraft(): void {
    this.tenantDraft = {
      tenant_name: '',
      tenant_slug: '',
      admin_full_name: '',
      admin_email: '',
      admin_role: 'tenant-admin',
      admin_access_methods: [...this.tenantAccess],
      admin_status: 'active',
      admin_mfa_policy: 'optional',
      admin_password: '',
    };
  }

  private resetUserDraft(tenantId = ''): void {
    this.userDraft = {
      tenant_id: tenantId,
      full_name: '',
      email: '',
      role: 'viewer',
      access_methods: [...this.userAccess],
      status: 'active',
      mfa_policy: 'optional',
      password: '',
    };
  }
}
