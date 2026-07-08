import { CommonModule } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { ReactiveFormsModule, FormGroup } from '@angular/forms';
import { FormlyFieldConfig, FormlyModule } from '@ngx-formly/core';
import { FormlyBootstrapModule } from '@ngx-formly/bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { DocumentApiService } from './document-api.service';

@Component({
  selector: 'app-document-upload',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormlyModule, FormlyBootstrapModule],
  template: `
    <section class="upload-card">
      <div class="upload-card__header">
        <div>
          <h2>Carica documento</h2>
          <p>Carica un file riservato del tenant per avviare automaticamente l'indicizzazione semantica con i profili small, medium e large.</p>
        </div>
        <span class="upload-card__chip">Indicizzazione protetta</span>
      </div>

      <div *ngIf="!canUpload()" class="upload-card__notice">
        Solo gli utenti con ruolo tenant-admin, operatore o super-admin possono caricare documenti.
      </div>

      <form *ngIf="canUpload()" class="upload-form" [formGroup]="form" (ngSubmit)="submit()">
        <formly-form [form]="form" [fields]="fields" [model]="model()" />

        <div class="upload-form__actions">
          <button class="btn btn-primary" type="submit" [disabled]="submitting()">
            {{ submitting() ? 'Caricamento in corso...' : 'Carica documento' }}
          </button>
          <button class="btn btn-outline-secondary" type="button" (click)="reset()" [disabled]="submitting()">
            Reimposta
          </button>
        </div>
      </form>

      <p *ngIf="status()?.kind === 'success'" class="upload-card__feedback">{{ status()?.message }}</p>
      <p *ngIf="status()?.kind === 'error'" class="upload-card__error">{{ status()?.message }}</p>
    </section>
  `,
  styles: [`
    .upload-card {
      background: linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
      border: 1px solid #d9e2ec;
      border-radius: 16px;
      padding: 20px;
      display: grid;
      gap: 16px;
      box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
    }

    .upload-card__header {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: flex-start;
    }

    .upload-card__header h2 {
      margin: 0 0 4px;
      font-size: 1.35rem;
    }

    .upload-card__header p {
      margin: 0;
      color: #52606d;
      max-width: 50ch;
    }

    .upload-card__chip {
      border-radius: 999px;
      background: #e8f2ff;
      color: #0b5ed7;
      padding: 8px 12px;
      font-size: 0.85rem;
      white-space: nowrap;
    }

    .upload-card__notice,
    .upload-card__feedback,
    .upload-card__error {
      margin: 0;
      border-radius: 12px;
      padding: 12px 14px;
      font-size: 0.95rem;
    }

    .upload-card__notice {
      background: #fff7e6;
      color: #8d5d00;
    }

    .upload-card__feedback {
      background: #ebfbee;
      color: #1e7e34;
    }

    .upload-card__error {
      background: #fff1f2;
      color: #b42318;
    }

    .upload-form {
      display: grid;
      gap: 16px;
    }

    .upload-form__actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
  `],
})
export class DocumentUploadComponent {
  private readonly authService = inject(AuthService);
  readonly api = inject(DocumentApiService);

  readonly form = new FormGroup({});
  readonly submitting = signal(false);
  readonly status = signal<{ kind: 'success' | 'error'; message: string } | null>(null);
  readonly model = signal<{ file: string | null; tags: string }>({ file: null, tags: '' });
  readonly canUpload = computed(() => this.authService.hasAnyRole(['super-admin', 'tenant-admin', 'operator']));
  private selectedFile: File | null = null;

  readonly fields: FormlyFieldConfig[] = [
    {
      key: 'file',
      type: 'document-file',
      props: {
        label: 'Documento',
        placeholder: 'Seleziona un documento',
        description: 'Formati supportati: TXT, Markdown, PDF. Dimensione massima: 5 MB.',
        required: true,
        accept: '.txt,.md,.pdf,text/plain,text/markdown,application/pdf',
        onSelected: (selFile: File | null) => {
          this.selectedFile = selFile;
          this.status.set(null);
        },
      },
    },
    {
      key: 'tags',
      type: 'input',
      props: {
        label: 'Tag',
        placeholder: 'es. capitolato, 2026, privacy',
        description: 'Inserisci tag separati da virgola. Saranno salvati sul documento e inviati anche nel payload Qdrant.',
      },
    },
  ];

  async submit(): Promise<void> {
    this.status.set(null);
    this.form.markAllAsTouched();
    
    if (!this.selectedFile) {
      this.status.set({ kind: 'error', message: 'Seleziona un documento prima di procedere.' });
      return;
    }

    this.submitting.set(true);
    try {
      const document = await this.api.uploadDocument(this.selectedFile, this.parseTags(this.model().tags));
      this.status.set({ kind: 'success', message: `Documento "${document.filename}" caricato correttamente.` });
      this.reset(false);
    } catch (error) {
      this.status.set({
        kind: 'error',
        message: error instanceof Error ? error.message : 'Caricamento non riuscito.',
      });
    } finally {
      this.submitting.set(false);
    }
  }

  reset(clearStatus = true): void {
    this.form.reset();
    this.selectedFile = null;
    this.model.set({ file: null, tags: '' });

    if (clearStatus) {
      this.status.set(null);
    }
  }

  private parseTags(raw: string): string[] {
    const unique = new Map<string, string>();

    raw.split(',')
      .map((tag) => tag.trim())
      .filter((tag) => tag !== '')
      .forEach((tag) => unique.set(tag.toLowerCase(), tag));

    return Array.from(unique.values());
  }
}
