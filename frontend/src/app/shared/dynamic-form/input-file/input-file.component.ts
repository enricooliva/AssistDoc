import { CommonModule } from '@angular/common';
import { Component, ElementRef, ViewChild, computed } from '@angular/core';
import { ReactiveFormsModule } from '@angular/forms';
import { FieldType, FieldTypeConfig, FormlyModule } from '@ngx-formly/core';

@Component({
  selector: 'app-input-file',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormlyModule],
  template: `
    <div class="document-file">
      <input
        #fileInput
        type="file"
        hidden="true"
        class="visually-hidden"
        [attr.accept]="props.accept || null"
        (change)="onFileChanged($event)"
      />

      <div class="document-file__group">
        <div
          class="document-file__display"
          [class.document-file__display--empty]="!formControl.value"
          [class.is-invalid]="showError"
          [formlyAttributes]="field"
        >
          <span class="document-file__name">
            {{ fileName() || props.placeholder || 'Seleziona un documento' }}
          </span>
        </div>
        <button class="btn btn-outline-primary" type="button" (click)="openDialogSelectFile()" [disabled]="props.disabled">
          {{ formControl.value ? 'Sostituisci file' : 'Scegli file' }}
        </button>
        <button class="btn btn-outline-secondary" type="button" (click)="reset()" [disabled]="props.disabled || !formControl.value">
          Rimuovi
        </button>
      </div>

      <small *ngIf="props.description" class="form-text text-muted">{{ props.description }}</small>
      <div *ngIf="showError" class="invalid-feedback d-block">
        {{ props.required ? 'Seleziona un documento prima di continuare.' : 'Documento non valido.' }}
      </div>
    </div>
  `,
  styles: [`
    .document-file {
      display: grid;
      gap: 8px;
    }

    .document-file__group {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 8px;
      align-items: center;
    }

    .document-file__display {
      min-height: calc(1.5em + 0.75rem + 2px);
      padding: 0.375rem 0.75rem;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      background: #fff;
      display: flex;
      align-items: center;
    }

    .document-file__display--empty {
      color: #6c757d;
    }

    .document-file__name {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  `],
})
export class InputFileComponent extends FieldType<FieldTypeConfig> {
  @ViewChild('fileInput', { static: true }) public fileInput!: ElementRef<HTMLInputElement>;

  readonly fileName = computed(() => {
    const currentValue = this.formControl.value;
    if (currentValue instanceof File) {
      return currentValue.name;
    }

    return typeof currentValue === 'string' ? currentValue : '';
  });

  onFileChanged(event: Event): void {
    const input = event.target as HTMLInputElement;
    const selectedFile = input.files?.[0] ?? null;

    this.formControl.setValue(selectedFile);
    this.formControl.markAsDirty();
    this.formControl.markAsTouched();
  }

  reset(): void {
    this.formControl.setValue(null);
    this.formControl.markAsDirty();
    this.formControl.markAsTouched();
    this.fileInput.nativeElement.value = '';
  }

  openDialogSelectFile(): void {
    if (!this.props.disabled) {
      this.fileInput.nativeElement.click();
    }
  }
}
