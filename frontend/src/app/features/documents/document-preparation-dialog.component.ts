import { CommonModule } from '@angular/common';
import { Component, Input, inject } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { PreparationRunResponse } from './document.models';
import { DocumentPreparationFormComponent } from './document-preparation-form.component';

@Component({
  selector: 'app-document-preparation-dialog',
  standalone: true,
  imports: [CommonModule, DocumentPreparationFormComponent],
  template: `
    <div class="modal-header">
      <div>
        <h2 class="modal-title">Nuova preparazione RAG</h2>
        <p class="text-muted mb-0">Avvia una nuova preparazione per il documento selezionato usando un profilo di segmentazione.</p>
      </div>
      <button type="button" class="btn-close" aria-label="Chiudi" (click)="activeModal.dismiss('cancel')"></button>
    </div>

    <div class="modal-body">
      <app-document-preparation-form
        surface="plain"
        [presetDocumentId]="documentId"
        (completed)="close($event)"
      />
    </div>
  `,
})
export class DocumentPreparationDialogComponent {
  readonly activeModal = inject(NgbActiveModal);

  @Input({ required: true }) documentId = '';

  close(run: PreparationRunResponse): void {
    this.activeModal.close(run);
  }
}
