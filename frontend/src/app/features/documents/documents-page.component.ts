import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { DocumentListComponent } from './document-list.component';
import { DocumentUploadComponent } from './document-upload.component';

@Component({
  selector: 'app-documents-page',
  standalone: true,
  imports: [CommonModule, DocumentUploadComponent, DocumentListComponent],
  template: `
    <section class="documents-page">
      <app-document-upload />
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
