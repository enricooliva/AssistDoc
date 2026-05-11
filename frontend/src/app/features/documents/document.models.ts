export type DocumentStatus = 'accepted' | 'queued' | 'processing' | 'ready' | 'failed';

export interface DocumentUserSummary {
  id: string;
  fullName: string;
}

export interface DocumentListItem {
  id: string;
  filename: string;
  mediaType: string;
  sizeBytes: number;
  status: DocumentStatus;
  uploadedAt: string;
  lastStatusAt: string;
  indexedAt?: string | null;
  failureReason?: string | null;
  uploadedBy: DocumentUserSummary;
  segmentsCount?: number;
  searchableSegmentsCount?: number;
}

export interface DocumentListResponse {
  items: DocumentListItem[];
  page: number;
  perPage: number;
  total: number;
}

export interface DocumentRetryResponse {
  documentId: string;
  status: DocumentStatus;
}
