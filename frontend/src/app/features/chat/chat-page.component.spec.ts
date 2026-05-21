import { signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ChatApiService } from './chat-api.service';
import { ChatPageComponent } from './chat-page.component';
import { DocumentApiService } from '../documents/document-api.service';

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
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(1);
  readonly loading = signal(false);
  readonly submitting = signal(false);
  readonly error = signal('');
  readonly initialize = jasmine.createSpy('initialize').and.resolveTo();
  readonly createConversation = jasmine.createSpy('createConversation').and.resolveTo();
  readonly openConversation = jasmine.createSpy('openConversation').and.resolveTo();
  readonly loadConversations = jasmine.createSpy('loadConversations').and.resolveTo();
  readonly deleteConversation = jasmine.createSpy('deleteConversation').and.resolveTo();
  readonly send = jasmine.createSpy('send').and.resolveTo();
  readonly isArchivedConversation = signal(false);
}

class DocumentApiServiceStub {
  readonly documents = signal([
    {
      id: 'doc-1',
      filename: 'manuale.txt',
      mediaType: 'text/plain',
      sizeBytes: 120,
      tags: ['privacy', 'tenant'],
      status: 'ready' as const,
      uploadedAt: '2026-05-11T10:00:00Z',
      lastStatusAt: '2026-05-11T10:00:00Z',
      uploadedBy: { id: '1', fullName: 'Operator' },
    },
  ]);
  readonly chunkingProfiles = signal([
    { id: 'cp-small', name: 'Small', slug: 'small', chunkSizeTokens: 512, overlapTokens: 64, active: true },
    { id: 'cp-medium', name: 'Medium', slug: 'medium', chunkSizeTokens: 1024, overlapTokens: 128, active: true },
  ]);
  readonly loadDocuments = jasmine.createSpy('loadDocuments').and.resolveTo();
  readonly loadChunkingProfiles = jasmine.createSpy('loadChunkingProfiles').and.resolveTo();
}

describe('ChatPageComponent', () => {
  let fixture: ComponentFixture<ChatPageComponent>;
  let component: ChatPageComponent;
  let chatApi: ChatApiServiceStub;
  let documentApi: DocumentApiServiceStub;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ChatPageComponent],
      providers: [
        { provide: ChatApiService, useClass: ChatApiServiceStub },
        { provide: DocumentApiService, useClass: DocumentApiServiceStub },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(ChatPageComponent);
    component = fixture.componentInstance;
    chatApi = TestBed.inject(ChatApiService) as unknown as ChatApiServiceStub;
    documentApi = TestBed.inject(DocumentApiService) as unknown as DocumentApiServiceStub;
    fixture.detectChanges();
    await fixture.whenStable();
  });

  it('initializes the conversation list on load', () => {
    expect(chatApi.initialize).toHaveBeenCalled();
    expect(documentApi.loadDocuments).toHaveBeenCalled();
    expect(documentApi.loadChunkingProfiles).toHaveBeenCalled();
  });

  it('submits the trimmed prompt and clears the composer', async () => {
    component.onComposerModelChange({
      prompt: ' Domanda sul tenant ',
      tags: ['privacy'],
      chunkingProfileId: 'cp-small',
    });

    await component.submit();

    expect(chatApi.send).toHaveBeenCalledWith('Domanda sul tenant', {
      tags: ['privacy'],
      chunkingProfileId: 'cp-small',
    });
    expect(component.composerModel().prompt).toBe('');
  });

  it('shows an inline error when the prompt is blank', async () => {
    component.onComposerModelChange({
      prompt: '   ',
      tags: [],
      chunkingProfileId: null,
    });

    await component.submit();

    expect(chatApi.send).not.toHaveBeenCalled();
    expect(component.feedback()).toContain('Inserisci una domanda');
  });

  it('loads the previous conversation page when requested', async () => {
    chatApi.page.set(2);

    await component.previousConversationPage();

    expect(chatApi.loadConversations).toHaveBeenCalledWith(1, 10);
  });

  it('invokes conversation deletion from the history row action', async () => {
    await component.deleteConversation('conv-1');

    expect(chatApi.deleteConversation).toHaveBeenCalledWith('conv-1');
  });

  it('tracks composer model changes for tag filters', () => {
    component.onComposerModelChange({
      prompt: '',
      tags: ['privacy', 'tenant'],
      chunkingProfileId: null,
    });

    expect(component.composerModel().tags).toEqual(['privacy', 'tenant']);
  });
});
