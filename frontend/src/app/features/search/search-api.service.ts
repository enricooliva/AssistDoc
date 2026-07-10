import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class SearchApiService {
  search(query: string) {
    return [
      {
        documentName: 'Manuale Aziendale.pdf',
        snippet: `Risultato per: ${query}`,
        sourceLabel: 'Pagina 4',
      },
    ];
  }
}

