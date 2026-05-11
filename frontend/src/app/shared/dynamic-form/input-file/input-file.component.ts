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
        class="visually-hidden"
        [attr.accept]="props.accept || null"
        (change)="onFileChanged($event)"
      />

      <div class="document-file__group">
        <input
          type="text"
          class="form-control"
          [value]="fileName()"
          [placeholder]="props.placeholder || 'Seleziona un documento'"
          [readonly]="true"
          [class.is-invalid]="showError"
          (click)="openDialogSelectFile()"
          [formlyAttributes]="field"
        />
        <button class="btn btn-outline-primary" type="button" (click)="openDialogSelectFile()" [disabled]="props.disabled">
          Sfoglia
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
