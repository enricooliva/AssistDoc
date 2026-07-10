import { Component } from '@angular/core';
import { FieldType, FieldTypeConfig } from '@ngx-formly/core';

@Component({
  selector: 'formly-cards-viewer-llm-response',
  standalone: false,
  template: `
  <div class="mb-3">
    <div class="row">
      <div class="col-12 text-muted" *ngIf="!getValue()?.length">
        Nessuna risposta disponibile
      </div>
    </div>

    <div class="row">
      <div class="col-12 mb-3" *ngFor="let item of getValue(); let i = index">
        <div class="card h-100 shadow-sm border-1 bg-white">
          <div class="card-body p-3">

            <!-- Domanda -->              
            <!-- Query as card title 
            <p class="fw-bold mb-1">Domanda:</p>
            <h5 class="card-title">{{ item.query }}</h5> -->
            <!-- Risposta -->
            <p class="mb-1">Risposta:</p>
            <p class="mb-2 p-2 border-start border-3 border-primary card-text" style="white-space: pre-wrap;">
              {{ item.answer }}
            </p>

            <!-- Optional context as mini-cards -->
            <div *ngIf="item.context_data?.length" class="row mt-3">
            <p class="fw-bold mb-1">Fonti:</p>
              <div class="col-md-12 mb-2" *ngFor="let doc of item.context_data">
                <div class="card h-100 shadow-sm border">
                  <div class="card-body p-2">
                    <h6 class="card-title mb-1">{{ doc.filename }}</h6>
                    <p class="card-subtitle mb-1 text-muted">
                      <strong>#:</strong> {{ doc.attachment_id }} |
                      <strong>Utente:</strong> {{ doc.user_name }} |                     
                      <strong>Livello:</strong> {{ doc.level }} |                      
                      <strong>Score:</strong> {{ doc.score | number:'1.2-2' }}
                    </p>
                    <p class="card-text small" style="white-space: pre-wrap;">
                      {{ getSnippet(doc.text_snippet, doc.attachment_id, 150) }}
                    </p>
                    <div class="d-flex gap-1 mt-1">
                      <button *ngIf="doc?.attachment_id" class="btn btn-sm btn-primary" (click)="openPdf(doc)">Apri PDF</button>
                      <button *ngIf="doc?.contratto_id" class="btn btn-sm btn-outline-secondary" (click)="openContract(doc)">Vai al contratto</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Score / Modello opzionale -->
            <div class="mt-2">
              <span *ngIf="item.model" class="badge bg-info me-1">
                Modello: {{ item.model }}
              </span>
              <span *ngIf="item.score !== undefined" class="badge bg-primary">
                Confidenza: {{ (item.score * 100) | number:'1.0-0' }}%
              </span>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
`
})
export class CardsViewerLLMResponseTypeComponent extends FieldType<FieldTypeConfig> {

  getValue() {
    const value = this.model[this.field.key as string];

    if (!value) return [];              // null or undefined → empty array
    if (Array.isArray(value)) return value; // already an array → return as is
    return [value];                     // single object → wrap in array
  }

  getSnippet(text_snippet: string, isExpanded: boolean, charLimit=300): string {
    if (isExpanded) return text_snippet;    

    // Limit to charLimit characters
    const snippet = text_snippet.substring(0, charLimit);
  
    // Add ellipsis if text was truncated
    return text_snippet.length > charLimit ? snippet + '...' : snippet;
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
