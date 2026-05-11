export type ChatResponseState = 'answered' | 'insufficient_information' | 'failed';

export interface ChatCitation {
  documentId: string;
  documentName: string;
  sourceLabel: string;
  quoteText: string;
}

export interface ChatMessage {
  id: string;
  actorType: 'user' | 'assistant';
  body: string;
  responseState: ChatResponseState | null;
  citations: ChatCitation[] | null;
  createdAt: string;
}

export interface ConversationSummary {
  id: string;
  title: string;
  status: 'active' | 'archived';
  lastMessageAt: string | null;
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

export interface SubmitChatQuestionRequest {
  question: string;
}

export interface ChatExchangeResponse {
  conversationId: string;
  userMessage: ChatMessage;
  assistantMessage: ChatMessage;
}
