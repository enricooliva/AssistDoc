import { CommonModule } from '@angular/common';
import { Component, Input, OnChanges, SimpleChanges } from '@angular/core';
import { ChatCitation } from './chat.models';

@Component({
  selector: 'app-citation-panel',
  standalone: true,
  imports: [CommonModule],
  template: `
    <section class="citations" *ngIf="citations.length; else empty">
      <h3>Citazioni</h3>
      <article *ngFor="let citation of citations; let index = index" class="citations__item">
        <strong>{{ citation.documentName }}</strong>
        <span>{{ citation.sourceLabel }}</span>
        <p [title]="citation.quoteText">{{ displayText(citation.quoteText, index) }}</p>
        <button
          *ngIf="isTruncatable(citation.quoteText)"
          class="citations__action"
          type="button"
          (click)="toggleExpanded(index)"
        >
          {{ isExpanded(index) ? 'Show less' : 'View full quote' }}
        </button>
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
    .citations__action {
      justify-self: start;
      border: 0;
      background: transparent;
      color: #2456d6;
      padding: 0;
      font-size: 0.95rem;
      text-decoration: underline;
    }
    .citations--empty p { margin: 0; color: #5d6b79; }
  `],
})
export class CitationPanelComponent implements OnChanges {
  private static readonly QUOTE_TEXT_MAX_LENGTH = 160;
  private readonly expandedIndexes = new Set<number>();

  @Input({ required: true }) citations: ChatCitation[] = [];

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['citations']) {
      this.expandedIndexes.clear();
    }
  }

  displayText(value: string, index: number): string {
    return this.isExpanded(index) ? value.trim() : this.truncateText(value);
  }

  isExpanded(index: number): boolean {
    return this.expandedIndexes.has(index);
  }

  isTruncatable(value: string): boolean {
    return value.trim().length > CitationPanelComponent.QUOTE_TEXT_MAX_LENGTH;
  }

  toggleExpanded(index: number): void {
    if (this.expandedIndexes.has(index)) {
      this.expandedIndexes.delete(index);
      return;
    }

    this.expandedIndexes.add(index);
  }

  truncateText(value: string): string {
    const trimmed = value.trim();

    if (!this.isTruncatable(trimmed)) {
      return trimmed;
    }

    return `${trimmed.slice(0, CitationPanelComponent.QUOTE_TEXT_MAX_LENGTH - 3).trimEnd()}...`;
  }
}
