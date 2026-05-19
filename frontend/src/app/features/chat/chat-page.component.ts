import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormGroup, ReactiveFormsModule } from '@angular/forms';
import { FormlyFieldConfig, FormlyModule, provideFormlyCore } from '@ngx-formly/core';
import { FormlyBootstrapModule } from '@ngx-formly/bootstrap';
import { ChatApiService } from './chat-api.service';
import { CitationPanelComponent } from './citation-panel.component';
import { DocumentApiService } from '../documents/document-api.service';
import { AccordionWrapperComponent } from '../../shared/dynamic-form/wrapper/accordion-wrapper.component';

interface ChatComposerModel {
  chunkingProfileId: string | null;
  prompt: string;
  tags: string[];
}

@Component({
  selector: 'app-chat-page',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    FormlyModule,
    FormlyBootstrapModule,
    AccordionWrapperComponent,
    CitationPanelComponent,
  ],
  providers: [
    provideFormlyCore({
      wrappers: [
        {
          name: 'accordion',
          component: AccordionWrapperComponent,
        },
      ],
    }),
  ],
  templateUrl: './chat-page.component.html',
  styleUrl: './chat-page.component.scss',
})
export class ChatPageComponent implements OnInit {
  readonly chatApi = inject(ChatApiService);
  readonly documentApi = inject(DocumentApiService);
  readonly inlineError = signal('');
  readonly conversations = this.chatApi.conversations;
  readonly messages = this.chatApi.messages;
  readonly activeConversation = this.chatApi.activeConversation;
  readonly activeConversationId = this.chatApi.activeConversationId;
  readonly isArchivedConversation = this.chatApi.isArchivedConversation;
  readonly loading = this.chatApi.loading;
  readonly submitting = this.chatApi.submitting;
  readonly feedback = computed(() => this.inlineError() || this.chatApi.error());
  readonly filtersForm = new FormGroup({});
  readonly composerForm = new FormGroup({});
  readonly composerModel = signal<ChatComposerModel>({
    chunkingProfileId: null,
    prompt: '',
    tags: [],
  });
  readonly availableTags = computed(() => {
    const unique = new Map<string, string>();

    this.documentApi.documents().forEach((document) => {
      document.tags.forEach((tag) => unique.set(tag.toLowerCase(), tag));
    });

    return Array.from(unique.values()).sort((left, right) => left.localeCompare(right));
  });
  readonly availableChunkingProfiles = computed(() =>
    this.documentApi.chunkingProfiles().filter((profile) => profile.active),
  );
  readonly filterFields = computed<FormlyFieldConfig[]>(() => [
    {
      wrappers: ['accordion'],
      props: {
        label: 'Filtri ricerca',
        collapsed: false,
      },
      fieldGroupClassName: 'd-grid gap-3',
      fieldGroup: [
        {
          template: `
            <p class="chat-layout__filters-description">
              Limita il recupero semantico per profilo chunk e tag documento prima di inviare la domanda.
            </p>
          `,
        },
        {
          key: 'chunkingProfileId',
          type: 'select',
          props: {
            label: 'Profilo chunk',
            disabled: this.isComposerDisabled(),
            options: [
              { label: 'Tutti', value: null },
              ...this.availableChunkingProfiles().map((profile) => ({
                label: profile.name,
                value: profile.id,
              })),
            ],
          },
        },
        ...(this.availableTags().length
          ? [{
              key: 'tags',
              type: 'multicheckbox',
              wrappers: ['accordion'],
              defaultValue: [],
              props: {
                label: 'Tag documento',
                collapsed: true,
                disabled: this.isComposerDisabled(),
                type: 'array',
                formCheck: 'default',
                options: this.availableTags().map((tag) => ({
                  label: tag,
                  value: tag,
                })),
              },
            } satisfies FormlyFieldConfig]
          : []),
      ],
    },
  ]);
  readonly composerFields = computed<FormlyFieldConfig[]>(() => [
    {
      key: 'prompt',
      type: 'textarea',
      props: {
        label: 'Domanda',
        rows: 3,
        placeholder: 'Fai una domanda sui documenti del tenant',
        disabled: this.isComposerDisabled(),
      },
    },
  ]);
  readonly lastAssistantCitations = computed(() => {
    const assistantMessages = this.messages().filter((message) => message.actorType === 'assistant');
    const lastAssistantMessage = assistantMessages[assistantMessages.length - 1];
    return lastAssistantMessage?.citations ?? [];
  });

  async ngOnInit(): Promise<void> {
    await Promise.all([
      this.chatApi.initialize(),
      this.documentApi.loadDocuments(),
      this.documentApi.loadChunkingProfiles(),
    ]);
  }

  async startConversation(): Promise<void> {
    this.inlineError.set('');
    await this.chatApi.createConversation();
  }

  async openConversation(conversationId: string): Promise<void> {
    this.inlineError.set('');
    await this.chatApi.openConversation(conversationId);
  }

  async archiveConversation(): Promise<void> {
    const activeConversation = this.activeConversation();

    if (!activeConversation) {
      return;
    }

    this.inlineError.set('');

    try {
      await this.chatApi.archiveConversation(activeConversation.id);
    } catch (error) {
      this.inlineError.set(error instanceof Error ? error.message : 'Impossibile archiviare la conversazione.');
    }
  }

  async submit(): Promise<void> {
    const value = this.composerModel().prompt.trim();
    if (!value) {
      this.inlineError.set('Inserisci una domanda prima di inviare il messaggio.');
      return;
    }

    if (this.isArchivedConversation()) {
      this.inlineError.set('La conversazione è archiviata. Aprine una nuova per continuare.');
      return;
    }

    this.inlineError.set('');

    try {
      await this.chatApi.send(value, {
        tags: this.composerModel().tags,
        chunkingProfileId: this.composerModel().chunkingProfileId,
      });
      this.composerModel.update((current) => ({ ...current, prompt: '' }));
    } catch (error) {
      this.inlineError.set(error instanceof Error ? error.message : 'Invio della domanda non riuscito.');
    }
  }

  onComposerModelChange(model: ChatComposerModel): void {
    this.composerModel.set({
      chunkingProfileId: model.chunkingProfileId ?? null,
      prompt: model.prompt ?? '',
      tags: model.tags ?? [],
    });
  }

  private isComposerDisabled(): boolean {
    return this.submitting() || this.isArchivedConversation();
  }
}
