import { Injectable, signal } from '@angular/core';

export interface Citation {
  documentName: string;
  sourceLabel: string;
  quoteText: string;
}

export interface ConversationMessage {
  actorType: 'user' | 'assistant';
  body: string;
  responseState?: 'completed' | 'insufficient_information';
  citations?: Citation[];
}

@Injectable({ providedIn: 'root' })
export class ChatApiService {
  readonly conversations = signal([
    { id: 'conv-001', title: 'Panoramica AssistDoc' },
    { id: 'conv-002', title: 'Policy sicurezza tenant' },
  ]);

  readonly messages = signal<ConversationMessage[]>([
    {
      actorType: 'assistant',
      body: 'Ciao, posso rispondere usando solo i documenti del tuo tenant e mostrarti le citazioni.',
      responseState: 'completed',
      citations: [],
    },
  ]);

  send(question: string): void {
    this.messages.update((current) => [
      ...current,
      { actorType: 'user', body: question },
      {
        actorType: 'assistant',
        body: 'In base ai documenti indicizzati del tenant, AssistDoc applica isolamento lato server e mostra le citazioni dei contenuti usati.',
        responseState: 'completed',
        citations: [
          {
            documentName: 'Manuale Aziendale.pdf',
            sourceLabel: 'Pagina 4',
            quoteText: 'AssistDoc usa isolamento tenant lato server per tutte le risorse.',
          },
        ],
      },
    ]);
  }
}

