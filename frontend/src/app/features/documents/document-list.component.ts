import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { DocumentApiService } from './document-api.service';
import { DocumentStatusBadgeComponent } from './document-status-badge.component';

@Component({
  selector: 'app-document-list',
  standalone: true,
  imports: [CommonModule, DocumentStatusBadgeComponent],
  template: `
    <section class="card">
      <h2>Documenti</h2>
      <article *ngFor="let document of api.documents()" class="row">
        <div>
          <strong>{{ document.filename }}</strong>
          <div>{{ document.uploadedAt | date:'short' }}</div>
        </div>
        <app-document-status-badge [status]="document.status" />
      </article>
    </section>
  `,
  styles: [`
    .card { background: #fff; border: 1px solid #d7dfe8; border-radius: 8px; padding: 16px; }
    .row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-top: 1px solid #edf1f5; }
    .row:first-of-type { border-top: 0; }
  `],
})
export class DocumentListComponent {
  readonly api = inject(DocumentApiService);
}

