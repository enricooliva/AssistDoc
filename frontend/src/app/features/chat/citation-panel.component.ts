import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { Citation } from './chat-api.service';

@Component({
  selector: 'app-citation-panel',
  standalone: true,
  imports: [CommonModule],
  template: `
    <section class="citations" *ngIf="citations.length; else empty">
      <h3>Citazioni</h3>
      <article *ngFor="let citation of citations" class="citations__item">
        <strong>{{ citation.documentName }}</strong>
        <span>{{ citation.sourceLabel }}</span>
        <p>{{ citation.quoteText }}</p>
      </article>
    </section>
    <ng-template #empty>
      <section class="citations citations--empty">
        <h3>Citazioni</h3>
        <p>Nessuna citazione per questo messaggio.</p>
      </section>
    </ng-template>
  `,
  styles: [`
    .citations { background: #fff; border: 1px solid #d7dfe8; border-radius: 8px; padding: 16px; }
    .citations__item { display: grid; gap: 4px; padding: 12px 0; border-top: 1px solid #edf1f5; }
    .citations__item:first-of-type { border-top: 0; padding-top: 0; }
    .citations__item span { color: #5d6b79; font-size: 14px; }
    .citations__item p { margin: 0; color: #23313f; }
    .citations--empty p { margin: 0; color: #5d6b79; }
  `],
})
export class CitationPanelComponent {
  @Input({ required: true }) citations: Citation[] = [];
}

