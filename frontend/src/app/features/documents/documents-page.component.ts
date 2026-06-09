import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { DocumentListComponent } from './document-list.component';
import { DocumentPreparationFormComponent } from './document-preparation-form.component';
import { DocumentUploadComponent } from './document-upload.component';

@Component({
  selector: 'app-documents-page',
  standalone: true,
  imports: [CommonModule, DocumentUploadComponent, DocumentPreparationFormComponent, DocumentListComponent],
  template: `
    <section class="documents-page">
      <app-document-upload />
      <app-document-preparation-form />
      <app-document-list />
    </section>
  `,
  styles: [`
    .documents-page {
      display: grid;
      gap: 20px;
      align-items: start;
    }
  `],
})
export class DocumentsPageComponent {}
