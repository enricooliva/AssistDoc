import { CommonModule } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
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
export class ChatPageComponent {
  readonly chatApi = inject(ChatApiService);
  readonly prompt = signal('');
  readonly messages = this.chatApi.messages;
  readonly lastAssistantCitations = computed(() => {
    const assistantMessages = this.messages().filter((message) => message.actorType === 'assistant');
    const lastAssistantMessage = assistantMessages[assistantMessages.length - 1];
    return lastAssistantMessage?.citations ?? [];
  });

  submit(): void {
    const value = this.prompt().trim();
    if (!value) {
      return;
    }

    this.chatApi.send(value);
    this.prompt.set('');
  }
}
