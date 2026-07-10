import { CommonModule, DatePipe } from '@angular/common';
import { Component, Input } from '@angular/core';
import { AuditEventItem } from './audit.models';

@Component({
  selector: 'app-audit-table',
  standalone: true,
  imports: [CommonModule, DatePipe],
  template: `
    <section class="card">
      <article *ngFor="let item of items" class="row">
        <div>
          <strong>{{ item.eventType }}</strong>
          <div>{{ item.actor }}</div>
        </div>
        <div>{{ item.outcome }}</div>
        <div>{{ item.occurredAt | date:'short' }}</div>
      </article>
    </section>
  `,
  styles: [`
    .card { background: #fff; border: 1px solid #d7dfe8; border-radius: 8px; padding: 16px; }
    .row { display: grid; grid-template-columns: 1fr 120px 160px; gap: 16px; padding: 12px 0; border-top: 1px solid #edf1f5; }
    .row:first-of-type { border-top: 0; }
  `],
})
export class AuditTableComponent {
  @Input() items: AuditEventItem[] = [];
}

