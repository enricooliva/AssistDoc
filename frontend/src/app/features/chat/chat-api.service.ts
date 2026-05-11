import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../core/api/api-url';
import {
  ArchiveConversationResponse,
  ChatExchangeResponse,
  ChatMessage,
  ConversationDetailResponse,
  ConversationListResponse,
  ConversationSummary,
  CreateConversationRequest,
  SubmitChatQuestionRequest,
} from './chat.models';

@Injectable({ providedIn: 'root' })
export class ChatApiService {
  private readonly http = inject(HttpClient);

  readonly conversations = signal<ConversationSummary[]>([]);
  readonly messages = signal<ChatMessage[]>([]);
  readonly activeConversationId = signal<string | null>(null);
  readonly loadingConversations = signal(false);
  readonly loadingThread = signal(false);
  readonly creatingConversation = signal(false);
  readonly submitting = signal(false);
  readonly error = signal('');
  readonly loading = computed(
    () => this.loadingConversations() || this.loadingThread() || this.creatingConversation(),
  );
  readonly activeConversation = computed(
    () => this.conversations().find((conversation) => conversation.id === this.activeConversationId()) ?? null,
  );
  readonly isArchivedConversation = computed(() => this.activeConversation()?.status === 'archived');

  async initialize(): Promise<void> {
    await this.loadConversations();

    const firstConversation = this.conversations()[0];

    if (firstConversation) {
      await this.openConversation(firstConversation.id);
      return;
    }

    await this.createConversation();
  }

  async loadConversations(): Promise<void> {
    this.loadingConversations.set(true);
    this.error.set('');

    try {
      const response = await firstValueFrom(
        this.http.get<ConversationListResponse>(apiUrl('/api/v1/chat/conversations')),
      );

      this.conversations.set(response.items);
    } catch (error) {
      this.error.set(this.extractError(error, 'Impossibile caricare le conversazioni.'));
      throw error;
    } finally {
      this.loadingConversations.set(false);
    }
  }

  async createConversation(payload: CreateConversationRequest = {}): Promise<ConversationSummary> {
    this.creatingConversation.set(true);
    this.error.set('');

    try {
      const conversation = await firstValueFrom(
        this.http.post<ConversationSummary>(apiUrl('/api/v1/chat/conversations'), payload),
      );

      this.activeConversationId.set(conversation.id);
      this.messages.set([]);
      this.upsertConversation(conversation);

      return conversation;
    } catch (error) {
      const message = this.extractError(error, 'Impossibile creare una nuova conversazione.');
      this.error.set(message);
      throw new Error(message);
    } finally {
      this.creatingConversation.set(false);
    }
  }

  async openConversation(conversationId: string): Promise<void> {
    this.loadingThread.set(true);
    this.error.set('');

    try {
      const response = await firstValueFrom(
        this.http.get<ConversationDetailResponse>(apiUrl(`/api/v1/chat/conversations/${conversationId}`)),
      );

      this.activeConversationId.set(response.conversation.id);
      this.messages.set(response.messages);
      this.upsertConversation(response.conversation);
    } catch (error) {
      this.error.set(this.extractError(error, 'Impossibile aprire la conversazione selezionata.'));
      throw error;
    } finally {
      this.loadingThread.set(false);
    }
  }

  async send(question: string): Promise<void> {
    this.submitting.set(true);
    this.error.set('');

    try {
      let conversationId = this.activeConversationId();

      if (!conversationId) {
        const createdConversation = await this.createConversation();
        conversationId = createdConversation.id;
      }

      const response = await firstValueFrom(
        this.http.post<ChatExchangeResponse>(
          apiUrl(`/api/v1/chat/conversations/${conversationId}/messages`),
          { question } satisfies SubmitChatQuestionRequest,
        ),
      );

      this.messages.update((current) => [...current, response.userMessage, response.assistantMessage]);
      const currentConversation = this.activeConversation();
      this.upsertConversation({
        ...(this.activeConversation() ?? {
          id: response.conversationId,
          title: 'Nuova conversazione',
          status: 'active',
        }),
        title:
          currentConversation && currentConversation.title !== 'Nuova conversazione'
            ? currentConversation.title
            : this.generateTitleFromQuestion(question),
        lastMessageAt: response.assistantMessage.createdAt,
      });
    } catch (error) {
      const message = this.extractError(error, 'Invio della domanda non riuscito.');
      this.error.set(message);
      throw new Error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  async archiveConversation(conversationId: string): Promise<void> {
    this.error.set('');

    try {
      const archived = await firstValueFrom(
        this.http.post<ArchiveConversationResponse>(apiUrl(`/api/v1/chat/conversations/${conversationId}/archive`), {}),
      );

      this.upsertConversation(archived);
    } catch (error) {
      const message = this.extractError(error, 'Impossibile archiviare la conversazione.');
      this.error.set(message);
      throw new Error(message);
    }
  }

  private upsertConversation(conversation: ConversationSummary): void {
    const items = this.conversations().filter((item) => item.id !== conversation.id);
    items.unshift(conversation);
    items.sort((left, right) => (right.lastMessageAt ?? '').localeCompare(left.lastMessageAt ?? ''));
    this.conversations.set(items);
  }

  private generateTitleFromQuestion(question: string): string {
    const normalized = question.trim().replace(/\s+/g, ' ');

    if (normalized.length <= 72) {
      return normalized;
    }

    return `${normalized.slice(0, 69).trim()}...`;
  }

  private extractError(error: unknown, fallback: string): string {
    if (error instanceof HttpErrorResponse) {
      return error.error?.error?.message ?? fallback;
    }

    if (error instanceof Error) {
      return error.message;
    }

    return fallback;
  }
}
