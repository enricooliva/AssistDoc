export type ChatResponseState = 'answered' | 'insufficient_information' | 'failed';

export interface ChatCitation {
  documentId: string;
  documentName: string;
  sourceLabel: string;
  quoteText: string;
  embedding?: string | null;
  collection?: string | null;
}

export interface ChatMessage {
  id: string;
  actorType: 'user' | 'assistant';
  body: string;
  responseState: ChatResponseState | null;
  generationModel?: string | null;
  citations: ChatCitation[] | null;
  createdAt: string;
}

export interface ConversationSummary {
  id: string;
  title: string;
  status: 'active' | 'archived';
  lastMessageAt: string | null;
  deletedAt?: string | null;
}

export interface ConversationUserSummary {
  id: string;
  fullName: string;
}

export interface ConversationListResponse {
  items: ConversationSummary[];
  page: number;
  perPage: number;
  total: number;
}

export interface ConversationDetailResponse {
  conversation: ConversationSummary;
  messages: ChatMessage[];
}

export interface CreateConversationRequest {
  title?: string;
}

export interface ArchiveConversationResponse {
  id: string;
  title: string;
  status: 'active' | 'archived';
  lastMessageAt: string | null;
}

export interface ConversationDeleteResponse {
  conversationId: string;
  title: string;
  status: 'active' | 'archived' | 'not_found' | 'already_deleted';
  lastMessageAt: string | null;
  deletedAt?: string | null;
  deletedBy?: ConversationUserSummary | null;
  removedFromList?: boolean;
}

export interface SubmitChatQuestionRequest {
  question: string;
  tags?: string[];
  chunkingProfileId?: string | null;
}

export interface ChatExchangeResponse {
  conversationId: string;
  userMessage: ChatMessage;
  assistantMessage: ChatMessage;
  retrievalModelProfileId?: string | null;
  generationModel?: string | null;
}

export interface ChatSearchFilters {
  tags: string[];
  chunkingProfileId: string | null;
}
