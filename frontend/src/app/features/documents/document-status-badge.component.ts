import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { DocumentStatus } from './document.models';

@Component({
  selector: 'app-document-status-badge',
  standalone: true,
  imports: [CommonModule],
  template: `<span class="badge" [ngClass]="'badge--' + status">{{ statusLabel[status] ?? status }}</span>`,
  styles: [`
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 0.82rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .badge--accepted,
    .badge--queued { background: #e8f2ff; color: #0b5ed7; }
    .badge--processing { background: #fff4db; color: #8d5d00; }
    .badge--ready { background: #e9f8ec; color: #18794e; }
    .badge--failed { background: #fff1f2; color: #b42318; }
  `],
})
export class DocumentStatusBadgeComponent {
  @Input({ required: true }) status: DocumentStatus = 'queued';

  readonly statusLabel: Record<DocumentStatus, string> = {
    accepted: 'Accettato',
    queued: 'In coda',
    processing: 'In lavorazione',
    ready: 'Pronto',
    failed: 'Fallito',
  };
}
