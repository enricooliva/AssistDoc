import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-search-results',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div *ngFor="let result of results" class="search-result">
      <strong>{{ result.documentName }}</strong>
      <span>{{ result.sourceLabel }}</span>
      <p>{{ result.snippet }}</p>
    </div>
  `,
  styles: [`
    .search-result { border-bottom: 1px solid #edf1f5; padding: 12px 0; }
    .search-result span { display: block; color: #5d6b79; font-size: 14px; }
    .search-result p { margin: 4px 0 0; }
  `],
})
export class SearchResultsComponent {
  @Input() results: Array<{ documentName: string; snippet: string; sourceLabel: string }> = [];
}

