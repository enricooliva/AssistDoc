import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../core/api/api-url';
import {
  ArchiveConversationResponse,
  ConversationDeleteResponse,
  ChatExchangeResponse,
  ChatMessage,
  ChatSearchFilters,
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
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(0);
  readonly loadingConversations = signal(false);
  readonly loadingThread = signal(false);
  readonly creatingConversation = signal(false);
  readonly submitting = signal(false);
  readonly deletingConversationId = signal<string | null>(null);
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

  async loadConversations(page = this.page(), perPage = this.perPage()): Promise<void> {
    this.loadingConversations.set(true);
    this.error.set('');

    try {
      const response = await firstValueFrom(
        this.http.get<ConversationListResponse>(apiUrl('/api/v1/chat/conversations'), {
          params: {
            page: String(page),
            perPage: String(perPage),
          },
        }),
      );

      this.conversations.set(response.items);
      this.page.set(response.page);
      this.perPage.set(response.perPage);
      this.total.set(response.total);
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

      await this.loadConversations(1, this.perPage());
      this.activeConversationId.set(conversation.id);
      this.messages.set([]);

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

  async send(question: string, filters: ChatSearchFilters): Promise<void> {
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
          {
            question,
            tags: filters.tags,
            chunkingProfileId: filters.chunkingProfileId,
          } satisfies SubmitChatQuestionRequest,
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

  async deleteConversation(conversationId: string): Promise<ConversationDeleteResponse> {
    this.deletingConversationId.set(conversationId);
    this.error.set('');

    const activeWasDeleted = this.activeConversationId() === conversationId;
    const currentPage = this.page();
    const currentPerPage = this.perPage();

    try {
      const response = await firstValueFrom(
        this.http.delete<ConversationDeleteResponse>(apiUrl(`/api/v1/chat/conversations/${conversationId}`)),
      );

      const targetPage = currentPage > 1 && this.conversations().length === 1 ? currentPage - 1 : currentPage;
      await this.loadConversations(targetPage, currentPerPage);

      if (activeWasDeleted) {
        this.activeConversationId.set(null);
        this.messages.set([]);

        const nextConversation = this.conversations()[0];
        if (nextConversation) {
          await this.openConversation(nextConversation.id);
        } else {
          await this.createConversation();
        }
      }

      return response;
    } catch (error) {
      const message = this.extractError(error, 'Impossibile eliminare la conversazione.');
      this.error.set(message);
      throw new Error(message);
    } finally {
      this.deletingConversationId.set(null);
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
