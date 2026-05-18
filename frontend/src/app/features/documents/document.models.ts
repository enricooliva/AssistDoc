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
  activeRetrievalModelProfile?: { id: string; name: string } | null;
  activeChunkingProfile?: { id: string; name: string } | null;
  activePreparationRunId?: string | null;
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

export interface RetrievalModelProfile {
  id: string;
  name: string;
  slug: string;
  generationModel: string;
  embeddingModel: string;
  tokenizerKey: string;
  tokenWindow: number;
  embeddingDimensions: number;
  availableForNewRuns: boolean;
}

export interface RetrievalModelProfileListResponse {
  data: RetrievalModelProfile[];
}

export interface ChunkingProfile {
  id: string;
  name: string;
  slug: string;
  chunkSizeTokens: number;
  overlapTokens: number;
  active: boolean;
  notes?: string | null;
}

export interface ChunkingProfileListResponse {
  data: ChunkingProfile[];
}

export interface PreparationRunResponse {
  documentId: string;
  status: DocumentStatus | 'not_found' | 'processing';
  preparationRunId: string;
}

export interface PreparationRunDetail {
  id: string;
  documentId: string;
  retrievalModelProfileId: string;
  chunkingProfileId: string;
  status: 'queued' | 'processing' | 'ready' | 'failed';
  createdAt?: string | null;
  failure?: {
    code?: string | null;
    message?: string | null;
    measuredTokenCount?: number | null;
    allowedTokenCount?: number | null;
  } | null;
}
