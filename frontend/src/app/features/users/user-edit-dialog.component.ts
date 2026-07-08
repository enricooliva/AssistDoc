import { CommonModule } from '@angular/common';
import { Component, Input, computed, inject, signal } from '@angular/core';
import { ReactiveFormsModule, FormControl, FormGroup, Validators } from '@angular/forms';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { EnterpriseUserSummary, EnterpriseUserUpdatePayload } from './user-management.models';
import { UserManagementApiService } from './user-management-api.service';

@Component({
  selector: 'app-user-edit-dialog',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="modal-header">
      <h2 class="modal-title">Modifica utente</h2>
      <button type="button" class="btn-close" aria-label="Chiudi" (click)="activeModal.dismiss('cancel')"></button>
    </div>

    <form class="modal-body d-grid gap-3" [formGroup]="form" (ngSubmit)="submit()">
      <p class="text-muted mb-0">
        Aggiorna solo i campi amministrativamente modificabili. I valori di blocco e cancellazione restano in sola lettura.
      </p>

      <div class="row g-3">
        <label class="col-12 col-md-6 d-grid gap-1">
          Nome completo
          <input class="form-control" formControlName="full_name" />
        </label>

        <label class="col-12 col-md-6 d-grid gap-1">
          Email
          <input class="form-control" formControlName="email" type="email" />
        </label>

        <label class="col-12 col-md-6 d-grid gap-1">
          Tenant ID
          <input class="form-control" formControlName="tenant_id" [readonly]="!canEditTenant()" />
        </label>

        <label class="col-12 col-md-6 d-grid gap-1">
          Ruolo
          <select class="form-select" formControlName="role">
            <option *ngFor="let option of roleOptions()" [value]="option">{{ option }}</option>
          </select>
        </label>

        <label class="col-12 col-md-6 d-grid gap-1">
          Stato
          <select class="form-select" formControlName="status">
            <option value="provisioned">provisioned</option>
            <option value="active">active</option>
            <option value="suspended">suspended</option>
            <option value="locked">locked</option>
            <option value="deactivated">deactivated</option>
            <option value="reset_pending">reset_pending</option>
          </select>
        </label>

        <label class="col-12 col-md-6 d-grid gap-1">
          Policy MFA
          <select class="form-select" formControlName="mfa_policy">
            <option value="required">required</option>
            <option value="optional">optional</option>
            <option value="inherited">inherited</option>
          </select>
        </label>
      </div>

      <div>
        <div class="fw-semibold mb-2">Metodi di accesso</div>
        <div class="d-flex gap-3 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" formControlName="company_account" />
            <span class="form-check-label">Account aziendale</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" formControlName="password_login" />
            <span class="form-check-label">Email e password</span>
          </label>
        </div>
      </div>

      <label class="d-grid gap-1">
        Nuova password
        <input class="form-control" formControlName="password" type="password" placeholder="Lascia vuoto per mantenere la password attuale" />
      </label>

      <div class="rounded border p-3 bg-light" *ngIf="user?.lockout || user?.deleted_at || user?.deleted_by">
        <div class="fw-semibold mb-2">Valori protetti</div>
        <div class="small text-secondary" *ngIf="user?.lockout">
          Blocco attivo: {{ user?.lockout?.status }} - {{ user?.lockout?.reason }} - {{ user?.lockout?.locked_until || 'data non disponibile' }}
        </div>
        <div class="small text-secondary" *ngIf="user?.deleted_at">
          Eliminato il {{ user?.deleted_at }}
        </div>
        <div class="small text-secondary" *ngIf="user?.deleted_by">
          Eliminato da {{ user?.deleted_by?.full_name }}
        </div>
      </div>

      <div class="alert alert-danger mb-0" *ngIf="error()">{{ error() }}</div>
    </form>

    <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" (click)="activeModal.dismiss('cancel')">Annulla</button>
      <button type="button" class="btn btn-primary" [disabled]="submitting()" (click)="submit()">
        {{ submitting() ? 'Salvataggio...' : 'Salva modifiche' }}
      </button>
    </div>
  `,
})
export class UserEditDialogComponent {
  private readonly authService = inject(AuthService);
  private readonly api = inject(UserManagementApiService);
  readonly activeModal = inject(NgbActiveModal);
  readonly submitting = signal(false);
  readonly error = signal('');
  readonly roleOptions = computed(() =>
    this.canEditTenant()
      ? ['super-admin', 'tenant-admin', 'operator', 'viewer']
      : ['tenant-admin', 'operator', 'viewer'],
  );

  private _user?: EnterpriseUserSummary;

  @Input({ required: true })
  set user(value: EnterpriseUserSummary) {
    this._user = value;
    this.populateForm(value);
  }

  get user(): EnterpriseUserSummary | undefined {
    return this._user;
  }

  readonly form = new FormGroup({
    full_name: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.maxLength(120)] }),
    email: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.email] }),
    tenant_id: new FormControl('', { nonNullable: true, validators: [Validators.required] }),
    role: new FormControl<'super-admin' | 'tenant-admin' | 'operator' | 'viewer'>('viewer', { nonNullable: true, validators: [Validators.required] }),
    status: new FormControl<'provisioned' | 'active' | 'suspended' | 'locked' | 'deactivated' | 'reset_pending'>('active', { nonNullable: true, validators: [Validators.required] }),
    mfa_policy: new FormControl<'required' | 'optional' | 'inherited'>('optional', { nonNullable: true, validators: [Validators.required] }),
    company_account: new FormControl(true, { nonNullable: true }),
    password_login: new FormControl(false, { nonNullable: true }),
    password: new FormControl('', { nonNullable: true, validators: [Validators.minLength(12)] }),
  });

  canEditTenant(): boolean {
    return this.authService.session()?.user.role === 'super-admin';
  }

  async submit(): Promise<void> {
    this.error.set('');
    this.form.markAllAsTouched();

    if (!this.user) {
      this.error.set('Dati utente non disponibili.');
      return;
    }

    if (this.form.invalid) {
      this.error.set('Compila tutti i campi obbligatori prima di salvare.');
      return;
    }

    const value = this.form.getRawValue();
    const accessMethods = [
      ...(value.company_account ? ['company_account' as const] : []),
      ...(value.password_login ? ['password' as const] : []),
    ];

    if (accessMethods.length === 0) {
      this.error.set('Seleziona almeno un metodo di accesso.');
      return;
    }

    if (value.password && !value.password_login) {
      this.error.set('La nuova password richiede anche il metodo di accesso email e password.');
      return;
    }

    this.submitting.set(true);

    try {
      const payload: EnterpriseUserUpdatePayload = {
        full_name: value.full_name.trim(),
        email: value.email.trim(),
        tenant_id: value.tenant_id.trim(),
        role: value.role,
        status: value.status,
        access_methods: accessMethods,
        mfa_policy: value.mfa_policy,
        ...(value.password ? { password: value.password } : {}),
      };

      const updated = await this.api.update(this.user.id, payload);
      this.activeModal.close(updated);
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Aggiornamento utente non riuscito.');
    } finally {
      this.submitting.set(false);
    }
  }

  private populateForm(user: EnterpriseUserSummary): void {
    this.form.reset({
      full_name: user.full_name,
      email: user.email,
      tenant_id: user.tenant.id,
      role: user.role,
      status: user.status,
      mfa_policy: user.mfa_policy,
      company_account: user.access_methods.includes('company_account'),
      password_login: user.access_methods.includes('password'),
      password: '',
    });

    if (!this.canEditTenant()) {
      this.form.controls.tenant_id.disable({ emitEvent: false });
    } else {
      this.form.controls.tenant_id.enable({ emitEvent: false });
    }
  }
}
