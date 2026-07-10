import { Component } from '@angular/core';
import { FieldType, FieldTypeConfig } from '@ngx-formly/core';

@Component({
  selector: 'formly-cards-viewer',
  standalone: false,
  template: `
  <div class="mb-3">
    <div class="row">
      <div class="col-12" *ngIf="!getValue() || getValue().length === 0" class="text-muted">
        Nessun risultato
      </div>
    </div>

    <div class="row">
      <div class="col-md-12 mb-3" *ngFor="let item of getValue(); let i = index">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="card-title">{{ to.titleField ? item[to.titleField || 'filename'] : to.label }}</h5>
              <span class="badge bg-primary">Score: {{ item.score | number:'1.2-2' }}</span>
            </div>

            <!-- Meta info dynamically from columns -->
            <p class="card-subtitle mb-2 text-muted">
              <ng-container *ngFor="let col of field.props.columns">
                <!-- Skip the main text/snippet column -->
                <ng-container *ngIf="col.prop !== 'text_snippet' && col.prop !== 'filename'">
                  <strong>{{ col.name }}:</strong> {{ item[col.prop] }} 
                  <span *ngIf="!isLastColumn(col)">| </span>
                </ng-container>
              </ng-container>
            </p>

            <hr>

            <!-- Collapsible snippet -->
            <p title="{{item.text_snippet}}" class="card-text" [innerHTML]="getSnippet(item)"></p>
            <button class="btn btn-link p-0" type="button" (click)="toggleExpand(item.id)">
              {{ isExpanded(item.id) ? 'Mostra meno' : 'Mostra tutto' }}
            </button>

            <hr>

            <!-- Action buttons -->
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-primary" type="button" (click)="openPdf(item)">
                Apri PDF
              </button>
              <button class="btn btn-sm btn-outline-secondary" type="button" (click)="openContract(item)">
                Vai al contratto
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  `,
  styles: [`
    mark { background-color: yellow; }
    .card-text { white-space: pre-wrap; font-family: monospace; }
    .btn-link { text-decoration: none; }
  `]
})
export class CardsViewerTypeComponent extends FieldType<FieldTypeConfig> {

  expandedIds: Set<number> = new Set<number>();

  getValue() {
    return this.model[this.field.key as string] || [];
  }

  toggleExpand(id: number) {
    if (this.expandedIds.has(id)) this.expandedIds.delete(id);
    else this.expandedIds.add(id);
  }

  isExpanded(id: number): boolean {
    return this.expandedIds.has(id);
  }


  // getSnippet(item: any): string {
  //   if (this.isExpanded(item.id)) return item.text_snippet;
  //   // Show first 3 lines as collapsed snippet
  //   return item.text_snippet.split('\n').slice(0, 3).join('\n') + '...';
  // }

  getSnippet(item: any, charLimit: number = 200): string {
    const textField = this.to.textField || 'text_snippet';
    const text = item[textField] || '';
    if (this.isExpanded(item.id)) return text;
  
    const snippet = item.text_snippet.substring(0, charLimit);
    return item.text_snippet.length > charLimit ? snippet + '...' : snippet;

    const snip = text.split('\n').slice(0, 3).join('\n');
    return snip.length < text.length ? snip + '...' : snip;

    if (this.isExpanded(item.id)) return item.text_snippet;
    if (!item.text_snippet) return '';

    // Limit to charLimit characters
    //const snippet = item.text_snippet.substring(0, charLimit);
  
    // Add ellipsis if text was truncated
    return item.text_snippet.length > charLimit ? snippet + '...' : snippet;
  }

  isLastColumn(col: any): boolean {
    const columns = this.field.props.columns.filter(c => c.prop !== 'text_snippet' && c.prop !== 'filename');
    return columns[columns.length - 1] === col;
  }


  openPdf(doc: any) {
    if (this.props.openPdf) {
      this.props.openPdf(doc); // ✅ chiama la funzione passata dal parent
    }
  }
  
  openContract(doc: any) {
    if (this.to.openContract) {
      this.to.openContract(doc);
    }
  }
  
}
