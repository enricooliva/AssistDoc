export interface DocumentListItem {
  id: string;
  filename: string;
  status: 'queued' | 'processing' | 'indexed' | 'failed';
  uploadedAt: string;
}

