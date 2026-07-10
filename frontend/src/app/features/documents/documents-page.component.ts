import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { DocumentListComponent } from './document-list.component';

@Component({
  selector: 'app-documents-page',
  standalone: true,
  imports: [CommonModule, DocumentListComponent],
  template: `
    <section class="documents-page">
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
