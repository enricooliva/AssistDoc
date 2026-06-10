import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, OnInit, Output, computed, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { PreparationRunResponse } from './document.models';
import { DocumentApiService } from './document-api.service';

@Component({
  selector: 'app-document-preparation-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <section [class.prep-card]="isCardSurface()" [class.prep-panel]="!isCardSurface()">
      <div *ngIf="isCardSurface()" class="prep-card__header">
        <div>
          <h2>Preparazione RAG</h2>
          <p>Seleziona il documento e il profilo di segmentazione per avviare una nuova preparazione. Il profilo retrieval configurato viene applicato automaticamente.</p>
        </div>
      </div>

      <form class="prep-form" [formGroup]="form" (ngSubmit)="submit()">
        <label *ngIf="!hasPresetDocumentId()">
          Documento
          <select formControlName="documentId">
            <option value="">Seleziona un documento</option>
            <option *ngFor="let document of api.documents()" [value]="document.id">
              {{ document.filename }} · {{ sourceLabel(document.sourceType) }}
            </option>
          </select>
        </label>

        <div *ngIf="selectedDocumentName()" class="prep-card__document">
          Documento selezionato: <strong>{{ selectedDocumentName() }}</strong>
        </div>

        <label>
          Profilo segmentazione
          <select formControlName="chunkingProfileId">
            <option value="">Seleziona un profilo</option>
            <option *ngFor="let profile of api.chunkingProfiles()" [value]="profile.id">{{ profile.name }}</option>
          </select>
        </label>

        <button class="btn btn-outline-primary" type="submit" [disabled]="submitting() || form.invalid">
          {{ submitting() ? 'Preparazione in corso...' : 'Avvia preparazione' }}
        </button>
      </form>

      <p *ngIf="feedback()" class="prep-card__feedback">{{ feedback() }}</p>
    </section>
  `,
  styles: [`
    .prep-card {
      background: #fff;
      border: 1px solid #d9e2ec;
      border-radius: 16px;
      padding: 20px;
      display: grid;
      gap: 12px;
    }

    .prep-form {
      display: grid;
      gap: 12px;
    }

    .prep-form label {
      display: grid;
      gap: 6px;
      font-weight: 600;
    }

    .prep-form select {
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 10px 12px;
    }

    .prep-card__feedback {
      margin: 0;
      color: #1e7e34;
    }

    .prep-card__document {
      color: #52606d;
      font-size: 0.95rem;
    }

    .prep-panel {
      display: grid;
      gap: 12px;
    }
  `],
})
export class DocumentPreparationFormComponent implements OnInit {
  readonly api = inject(DocumentApiService);

  @Input() surface: 'card' | 'plain' = 'card';
  @Input() presetDocumentId: string | null = null;
  @Output() readonly completed = new EventEmitter<PreparationRunResponse>();

  readonly submitting = signal(false);
  readonly feedback = signal('');
  readonly form = new FormGroup({
    documentId: new FormControl('', { nonNullable: true, validators: [Validators.required] }),
    chunkingProfileId: new FormControl('', { nonNullable: true, validators: [Validators.required] }),
  });
  readonly hasProfiles = computed(() => this.api.chunkingProfiles().length > 0);
  readonly isCardSurface = computed(() => this.surface === 'card');
  readonly hasPresetDocumentId = computed(() => this.presetDocumentId !== null && this.presetDocumentId !== '');
  readonly selectedDocumentName = computed(() => {
    const documentId = this.form.controls.documentId.value;
    return this.api.documents().find((document) => document.id === documentId)?.filename ?? '';
  });

  async ngOnInit(): Promise<void> {
    await Promise.all([this.api.loadDocuments(), this.api.loadChunkingProfiles()]);

    if (this.hasPresetDocumentId()) {
      this.form.controls.documentId.setValue(this.presetDocumentId ?? '');
    }
  }

  async submit(): Promise<void> {
    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);
    this.feedback.set('');

    try {
      const value = this.form.getRawValue();
      const run = await this.api.startPreparationRun(
        value.documentId,
        value.chunkingProfileId,
      );
      this.feedback.set(`Preparazione avviata con stato finale: ${run.status}.`);
      this.completed.emit(run);
    } finally {
      this.submitting.set(false);
    }
  }

  sourceLabel(sourceType: 'file' | 'text'): string {
    return sourceType === 'text' ? 'Testo diretto' : 'File';
  }
}
