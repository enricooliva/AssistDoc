import { signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ChatApiService } from './chat-api.service';
import { ChatPageComponent } from './chat-page.component';

class ChatApiServiceStub {
  readonly conversations = signal([
    { id: 'conv-1', title: 'Nuova conversazione', status: 'active', lastMessageAt: '2026-05-11T10:00:00Z' },
  ]);
  readonly messages = signal([
    {
      id: 'm-1',
      actorType: 'assistant' as const,
      body: 'Ciao, chiedimi qualcosa sui documenti del tenant.',
      responseState: 'answered' as const,
      citations: [],
      createdAt: '2026-05-11T10:00:00Z',
    },
  ]);
  readonly activeConversationId = signal('conv-1');
  readonly activeConversation = signal({
    id: 'conv-1',
    title: 'Nuova conversazione',
    status: 'active' as const,
    lastMessageAt: '2026-05-11T10:00:00Z',
  });
  readonly loading = signal(false);
  readonly submitting = signal(false);
  readonly error = signal('');
  readonly initialize = jasmine.createSpy('initialize').and.resolveTo();
  readonly createConversation = jasmine.createSpy('createConversation').and.resolveTo();
  readonly openConversation = jasmine.createSpy('openConversation').and.resolveTo();
  readonly archiveConversation = jasmine.createSpy('archiveConversation').and.resolveTo();
  readonly send = jasmine.createSpy('send').and.resolveTo();
  readonly isArchivedConversation = signal(false);
}

describe('ChatPageComponent', () => {
  let fixture: ComponentFixture<ChatPageComponent>;
  let component: ChatPageComponent;
  let chatApi: ChatApiServiceStub;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ChatPageComponent],
      providers: [{ provide: ChatApiService, useClass: ChatApiServiceStub }],
    }).compileComponents();

    fixture = TestBed.createComponent(ChatPageComponent);
    component = fixture.componentInstance;
    chatApi = TestBed.inject(ChatApiService) as unknown as ChatApiServiceStub;
    fixture.detectChanges();
    await fixture.whenStable();
  });

  it('initializes the conversation list on load', () => {
    expect(chatApi.initialize).toHaveBeenCalled();
  });

  it('submits the trimmed prompt and clears the composer', async () => {
    component.prompt.set(' Domanda sul tenant ');

    await component.submit();

    expect(chatApi.send).toHaveBeenCalledWith('Domanda sul tenant');
    expect(component.prompt()).toBe('');
  });

  it('shows an inline error when the prompt is blank', async () => {
    component.prompt.set('   ');

    await component.submit();

    expect(chatApi.send).not.toHaveBeenCalled();
    expect(component.feedback()).toContain('Inserisci una domanda');
  });

  it('archives the active conversation from the header action', async () => {
    await component.archiveConversation();

    expect(chatApi.archiveConversation).toHaveBeenCalledWith('conv-1');
  });
});
