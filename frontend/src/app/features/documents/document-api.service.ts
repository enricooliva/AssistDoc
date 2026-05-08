import { Injectable, signal } from '@angular/core';
import { DocumentListItem } from './document.models';

@Injectable({ providedIn: 'root' })
export class DocumentApiService {
  readonly documents = signal<DocumentListItem[]>([
    {
      id: 'doc-001',
      filename: 'Manuale Aziendale.pdf',
      status: 'indexed',
      uploadedAt: new Date().toISOString(),
    },
  ]);
}

