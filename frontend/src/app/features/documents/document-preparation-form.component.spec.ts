import { signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { DocumentPreparationFormComponent } from './document-preparation-form.component';
import { DocumentApiService } from './document-api.service';

describe('DocumentPreparationFormComponent', () => {
  it('renders the Italian preparation labels and chunking selector', async () => {
    const apiMock = {
      documents: signal([{ id: '1', filename: 'Procedura', sourceType: 'text' as const }]),
      chunkingProfiles: signal([{ id: '2', name: 'medium' }]),
      loadDocuments: jasmine.createSpy().and.resolveTo([]),
      loadChunkingProfiles: jasmine.createSpy().and.resolveTo([]),
      startPreparationRun: jasmine.createSpy().and.resolveTo({ documentId: '1', status: 'ready', preparationRunId: '99' }),
    };

    await TestBed.configureTestingModule({
      imports: [DocumentPreparationFormComponent],
      providers: [{ provide: DocumentApiService, useValue: apiMock }],
    }).compileComponents();

    const fixture = TestBed.createComponent(DocumentPreparationFormComponent);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Preparazione RAG');
    expect(fixture.nativeElement.textContent).toContain('Profilo segmentazione');
    expect(fixture.nativeElement.textContent).toContain('Avvia preparazione');
    expect(fixture.nativeElement.textContent).toContain('Procedura');
    expect(fixture.nativeElement.textContent).toContain('Testo diretto');
  });

  it('prefills the selected document and hides the selector when opened from the list', async () => {
    const apiMock = {
      documents: signal([{ id: '1', filename: 'Procedura', sourceType: 'text' as const }]),
      chunkingProfiles: signal([{ id: '2', name: 'medium' }]),
      loadDocuments: jasmine.createSpy().and.resolveTo([]),
      loadChunkingProfiles: jasmine.createSpy().and.resolveTo([]),
      startPreparationRun: jasmine.createSpy().and.resolveTo({ documentId: '1', status: 'ready', preparationRunId: '99' }),
    };

    await TestBed.configureTestingModule({
      imports: [DocumentPreparationFormComponent],
      providers: [{ provide: DocumentApiService, useValue: apiMock }],
    }).compileComponents();

    const fixture = TestBed.createComponent(DocumentPreparationFormComponent);
    fixture.componentInstance.presetDocumentId = '1';
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(fixture.componentInstance.form.controls.documentId.value).toBe('1');
    expect(fixture.nativeElement.textContent).toContain('Documento selezionato:');
    expect(fixture.nativeElement.textContent).not.toContain('Seleziona un documento');
  });
});
