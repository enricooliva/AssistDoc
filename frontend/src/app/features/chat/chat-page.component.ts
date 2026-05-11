import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ChatApiService } from './chat-api.service';
import { CitationPanelComponent } from './citation-panel.component';

@Component({
  selector: 'app-chat-page',
  standalone: true,
  imports: [CommonModule, FormsModule, CitationPanelComponent],
  templateUrl: './chat-page.component.html',
  styleUrl: './chat-page.component.scss',
})
export class ChatPageComponent implements OnInit {
  readonly chatApi = inject(ChatApiService);
  readonly prompt = signal('');
  readonly inlineError = signal('');
  readonly conversations = this.chatApi.conversations;
  readonly messages = this.chatApi.messages;
  readonly activeConversation = this.chatApi.activeConversation;
  readonly activeConversationId = this.chatApi.activeConversationId;
  readonly loading = this.chatApi.loading;
  readonly submitting = this.chatApi.submitting;
  readonly feedback = computed(() => this.inlineError() || this.chatApi.error());
  readonly lastAssistantCitations = computed(() => {
    const assistantMessages = this.messages().filter((message) => message.actorType === 'assistant');
    const lastAssistantMessage = assistantMessages[assistantMessages.length - 1];
    return lastAssistantMessage?.citations ?? [];
  });

  async ngOnInit(): Promise<void> {
    await this.chatApi.initialize();
  }

  async startConversation(): Promise<void> {
    this.inlineError.set('');
    await this.chatApi.createConversation();
  }

  async openConversation(conversationId: string): Promise<void> {
    this.inlineError.set('');
    await this.chatApi.openConversation(conversationId);
  }

  async submit(): Promise<void> {
    const value = this.prompt().trim();
    if (!value) {
      this.inlineError.set('Inserisci una domanda prima di inviare il messaggio.');
      return;
    }

    this.inlineError.set('');

    try {
      await this.chatApi.send(value);
      this.prompt.set('');
    } catch (error) {
      this.inlineError.set(error instanceof Error ? error.message : 'Invio della domanda non riuscito.');
    }
  }
}
