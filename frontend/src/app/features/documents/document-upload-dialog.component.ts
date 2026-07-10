import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { DocumentListItem } from './document.models';
import { DocumentUploadComponent } from './document-upload.component';

@Component({
  selector: 'app-document-upload-dialog',
  standalone: true,
  imports: [CommonModule, DocumentUploadComponent],
  template: `
    <div class="modal-header">
      <div>
        <h2 class="modal-title">Aggiungi fonte</h2>
        <p class="text-muted mb-0">Carica un file o incolla del testo mantenendo il flusso di indicizzazione esistente.</p>
      </div>
      <button type="button" class="btn-close" aria-label="Chiudi" (click)="activeModal.dismiss('cancel')"></button>
    </div>

    <div class="modal-body">
      <app-document-upload surface="plain" (completed)="close($event)" />
    </div>
  `,
})
export class DocumentUploadDialogComponent {
  readonly activeModal = inject(NgbActiveModal);

  close(document: DocumentListItem): void {
    this.activeModal.close(document);
  }
}
