import { CommonModule } from '@angular/common';
import { Component, Input, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { TenantMembershipSummary, TenantUpdatePayload } from './tenant-admin.models';
import { TenantAdminApiService } from './tenant-admin-api.service';

@Component({
  selector: 'app-tenant-edit-dialog',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="modal-header">
      <h2 class="modal-title">Modifica tenant</h2>
      <button type="button" class="btn-close" aria-label="Chiudi" (click)="activeModal.dismiss('cancel')"></button>
    </div>

    <form class="modal-body d-grid gap-3" [formGroup]="form" (ngSubmit)="submit()">
      <p class="text-muted mb-0">
        Aggiorna i dati del tenant mantenendo invariata la struttura esistente.
      </p>

      <div class="row g-3">
        <label class="col-12 d-grid gap-1">
          Nome tenant
          <input class="form-control" formControlName="tenant_name" />
        </label>

        <label class="col-12 col-md-8 d-grid gap-1">
          Slug tenant
          <input class="form-control" formControlName="tenant_slug" />
        </label>

        <label class="col-12 col-md-4 d-grid gap-1">
          Stato
          <select class="form-select" formControlName="status">
            <option value="active">active</option>
            <option value="inactive">inactive</option>
          </select>
        </label>
      </div>

      <div class="small text-secondary">
        ID tenant: {{ tenant?.id }} · Member count: {{ tenant?.member_count }} · Admin count: {{ tenant?.admin_count }}
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
export class TenantEditDialogComponent {
  private readonly api = inject(TenantAdminApiService);
  readonly activeModal = inject(NgbActiveModal);
  readonly submitting = signal(false);
  readonly error = signal('');

  private _tenant?: TenantMembershipSummary;

  @Input({ required: true })
  set tenant(value: TenantMembershipSummary) {
    this._tenant = value;
    this.populateForm(value);
  }

  get tenant(): TenantMembershipSummary | undefined {
    return this._tenant;
  }

  readonly form = new FormGroup({
    tenant_name: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.maxLength(120)] }),
    tenant_slug: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.maxLength(120)] }),
    status: new FormControl<'active' | 'inactive'>('active', { nonNullable: true, validators: [Validators.required] }),
  });

  async submit(): Promise<void> {
    this.error.set('');
    this.form.markAllAsTouched();

    if (!this.tenant) {
      this.error.set('Dati tenant non disponibili.');
      return;
    }

    if (this.form.invalid) {
      this.error.set('Compila tutti i campi obbligatori prima di salvare.');
      return;
    }

    this.submitting.set(true);

    try {
      const value = this.form.getRawValue();
      const payload: TenantUpdatePayload = {
        tenant_name: value.tenant_name.trim(),
        tenant_slug: value.tenant_slug.trim(),
        status: value.status,
      };

      const updated = await this.api.updateTenant(this.tenant.id, payload);
      this.activeModal.close(updated);
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Aggiornamento tenant non riuscito.');
    } finally {
      this.submitting.set(false);
    }
  }

  private populateForm(tenant: TenantMembershipSummary): void {
    this.form.reset({
      tenant_name: tenant.name,
      tenant_slug: tenant.slug,
      status: tenant.status,
    });
  }
}
