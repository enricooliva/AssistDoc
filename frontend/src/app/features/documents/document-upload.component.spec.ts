import { provideHttpClient } from '@angular/common/http';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideFormlyCore } from '@ngx-formly/core';
import { withFormlyBootstrap } from '@ngx-formly/bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { DocumentApiService } from './document-api.service';
import { DocumentUploadComponent } from './document-upload.component';
import { InputFileComponent } from '../../shared/dynamic-form/input-file/input-file.component';

class AuthServiceStub {
  role: 'super-admin' | 'tenant-admin' | 'operator' | 'viewer' = 'operator';

  hasAnyRole(roles: string[]): boolean {
    return roles.includes(this.role);
  }
}

class DocumentApiServiceStub {
  uploadDocument = jasmine.createSpy('uploadDocument').and.resolveTo({
    id: 'doc-1',
    filename: 'manuale.txt',
    sourceType: 'file',
    tags: [],
  });
  createTextDocument = jasmine.createSpy('createTextDocument').and.resolveTo({
    id: 'doc-2',
    filename: 'Procedura interna',
    sourceType: 'text',
    tags: [],
  });

  failUpload(message: string): void {
    this.uploadDocument.and.callFake(async () => {
      throw new Error(message);
    });
  }
}

describe('DocumentUploadComponent', () => {
  let fixture: ComponentFixture<DocumentUploadComponent>;
  let authService: AuthServiceStub;

  beforeEach(async () => {
    authService = new AuthServiceStub();

    await TestBed.configureTestingModule({
      imports: [DocumentUploadComponent],
      providers: [
        provideHttpClient(),
        provideFormlyCore([
          ...withFormlyBootstrap(),
          {
            types: [{ name: 'document-file', component: InputFileComponent, wrappers: ['form-field'] }],
          },
        ]),
        { provide: AuthService, useValue: authService },
        { provide: DocumentApiService, useClass: DocumentApiServiceStub },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DocumentUploadComponent);
    fixture.detectChanges();
  });

  it('shows the upload heading for privileged users', () => {
    expect(fixture.nativeElement.textContent).toContain('Carica documento');
  });

  it('shows the tags field for privileged users', () => {
    expect(fixture.nativeElement.textContent).toContain('Tag');
  });

  it('offers both file upload and direct text modes', () => {
    expect(fixture.nativeElement.textContent).toContain('Carica file');
    expect(fixture.nativeElement.textContent).toContain('Incolla testo');
  });

  it('renders a single explicit action to choose the file', () => {
    const text = fixture.nativeElement.textContent as string;

    expect(text).toContain('Scegli file');
    expect(text).not.toContain('Sfoglia');
  });

  it('shows a permission notice for viewers', () => {
    authService.role = 'viewer';
    fixture = TestBed.createComponent(DocumentUploadComponent);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Solo gli utenti con ruolo tenant-admin, operatore o super-admin');
  });

  it('allows tenant-admin users to upload documents', () => {
    authService.role = 'tenant-admin';
    fixture = TestBed.createComponent(DocumentUploadComponent);
    fixture.detectChanges();

    expect((fixture.componentInstance as DocumentUploadComponent).canUpload()).toBeTrue();
  });

  it('shows only the error alert when the upload fails', async () => {
    const api = TestBed.inject(DocumentApiService) as unknown as DocumentApiServiceStub;
    api.failUpload('Caricamento non riuscito.');

    const component = fixture.componentInstance as DocumentUploadComponent;
    component.fileFields[0].props?.onSelected?.(new File(['contenuto'], 'manuale.txt'));
    fixture.detectChanges();

    await component.submit();
    fixture.detectChanges();

    const errorAlerts = fixture.nativeElement.querySelectorAll('.upload-card__error');
    const feedbackAlerts = fixture.nativeElement.querySelectorAll('.upload-card__feedback');

    expect(errorAlerts.length).toBe(1);
    expect(feedbackAlerts.length).toBe(0);
    expect(fixture.nativeElement.textContent).toContain('Caricamento non riuscito.');
  });

  it('shows an inline validation message when direct text is missing', async () => {
    const component = fixture.componentInstance as DocumentUploadComponent;
    component.switchMode('text');
    fixture.detectChanges();

    await component.submit();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Titolo e testo sono obbligatori per il contenuto incollato.');
  });

  it('emits completion when a text source is saved', async () => {
    const component = fixture.componentInstance as DocumentUploadComponent;
    const completedSpy = jasmine.createSpy('completed');

    component.completed.subscribe(completedSpy);
    component.switchMode('text');
    component.textModel.sourceLabel = 'Procedura';
    component.textModel.text = 'Contenuto indicizzabile';

    await component.submit();

    expect(completedSpy).toHaveBeenCalled();
  });
});
