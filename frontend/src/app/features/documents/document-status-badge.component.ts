import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-document-status-badge',
  standalone: true,
  imports: [CommonModule],
  template: `<span class="badge" [class.badge--indexed]="status === 'indexed'">{{ status }}</span>`,
  styles: [`
    .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #eef1f5; text-transform: capitalize; }
    .badge--indexed { background: #d7f4dd; color: #146c2e; }
  `],
})
export class DocumentStatusBadgeComponent {
  @Input({ required: true }) status = 'queued';
}

