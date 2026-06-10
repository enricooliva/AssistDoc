import { ComponentFixture, TestBed } from '@angular/core/testing';
import { signal } from '@angular/core';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { DocumentApiService } from './document-api.service';
import { DocumentListComponent } from './document-list.component';
import { DocumentListItem } from './document.models';

class AuthServiceStub {
  role: 'super-admin' | 'operator' | 'viewer' = 'operator';

  hasAnyRole(roles: string[]): boolean {
    return roles.includes(this.role);
  }
}

class DocumentApiServiceStub {
  readonly documents = signal<DocumentListItem[]>([{
    id: 'doc-1',
    filename: 'Manuale Aziendale.txt',
    sourceType: 'text' as const,
    mediaType: 'text/plain',
    sizeBytes: 200,
    tags: ['manuale', 'tenant'],
    status: 'ready' as const,
    uploadedAt: new Date().toISOString(),
    lastStatusAt: new Date().toISOString(),
    uploadedBy: { id: '1', fullName: 'Operator Demo' },
    deletedBy: null,
    failureReason: null,
    segmentsCount: 1,
    searchableSegmentsCount: 1,
  }]);
  readonly page = signal(1);
  readonly perPage = signal(25);
  readonly total = signal(1);
  readonly loading = signal(false);
  readonly deletingDocumentId = signal<string | null>(null);
  readonly error = signal('');
  readonly loadDocuments = jasmine.createSpy('loadDocuments').and.resolveTo();
  readonly deleteDocument = jasmine.createSpy('deleteDocument').and.resolveTo({
    documentId: 'doc-1',
    status: 'deleted',
    removedFromList: true,
  });
  readonly retryDocument = jasmine.createSpy('retryDocument').and.resolveTo({
    documentId: 'doc-1',
    status: 'queued',
  });
}

class NgbModalStub {
  open = jasmine.createSpy('open').and.returnValue({
    result: Promise.resolve(true),
  });
}

describe('DocumentListComponent', () => {
  let fixture: ComponentFixture<DocumentListComponent>;
  let api: DocumentApiServiceStub;
  let authService: AuthServiceStub;
  let modal: NgbModalStub;

  beforeEach(async () => {
    api = new DocumentApiServiceStub();
    authService = new AuthServiceStub();
    modal = new NgbModalStub();

    await TestBed.configureTestingModule({
      imports: [DocumentListComponent],
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: DocumentApiService, useValue: api },
        { provide: NgbModal, useValue: modal },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DocumentListComponent);
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();
  });

  it('renders document rows with tags and uploader metadata', () => {
    expect(fixture.nativeElement.textContent).toContain('Manuale Aziendale.txt');
    expect(fixture.nativeElement.textContent).toContain('Fonte: Testo diretto');
    expect(fixture.nativeElement.textContent).toContain('Tag: manuale, tenant');
    expect(fixture.nativeElement.textContent).toContain('Caricato da Operator Demo');
  });

  it('shows pagination controls for the list', () => {
    expect(fixture.nativeElement.textContent).toContain('Pagina 1 di 1');
    expect(fixture.nativeElement.textContent).toContain('Precedente');
    expect(fixture.nativeElement.textContent).toContain('Successiva');
  });

  it('shows a single add-source action for privileged users', () => {
    expect(fixture.nativeElement.textContent).toContain('Aggiungi fonte');
  });

  it('opens the add-source modal from the header action', () => {
    const addButton = Array.from(fixture.nativeElement.querySelectorAll('button'))
      .find((button: HTMLButtonElement) => button.textContent?.includes('Aggiungi fonte')) as HTMLButtonElement;

    addButton.click();

    expect(modal.open).toHaveBeenCalled();
  });

  it('reveals a soft delete confirmation before deleting a document', async () => {
    fixture.nativeElement.querySelector('button.btn-outline-danger')?.click();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('La rimozione sarà una soft delete');
    expect(fixture.nativeElement.textContent).toContain('Conferma eliminazione');
  });

  it('hides delete actions for viewers', async () => {
    authService.role = 'viewer';
    fixture = TestBed.createComponent(DocumentListComponent);
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).not.toContain('Elimina');
    expect(fixture.nativeElement.textContent).not.toContain('Aggiungi fonte');
  });

  it('shows failure reasons and a retry action for failed documents', async () => {
    api.documents.set([{
      id: 'doc-2',
      filename: 'Errore.txt',
      sourceType: 'file' as const,
      mediaType: 'text/plain',
      sizeBytes: 10,
      tags: [],
      status: 'failed' as const,
      uploadedAt: new Date().toISOString(),
      lastStatusAt: new Date().toISOString(),
      uploadedBy: { id: '1', fullName: 'Operator Demo' },
      deletedBy: null,
      failureReason: 'Il documento non contiene testo estraibile.',
      segmentsCount: 0,
      searchableSegmentsCount: 0,
    }]);
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Il documento non contiene testo estraibile.');
    expect(fixture.nativeElement.textContent).toContain('Riprova');
  });
});
