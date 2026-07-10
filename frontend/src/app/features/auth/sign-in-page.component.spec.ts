import { signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { SignInPageComponent } from './sign-in-page.component';

class AuthServiceStub {
  readonly feedback = signal('');
  readonly lastActionRequired = signal(null);
  readonly loginOptions = signal([
    { id: 'company_account', label: 'Accedi con account aziendale' },
    { id: 'password', label: 'Accedi con email e password' },
  ]);
  readonly loadLoginOptions = jasmine.createSpy('loadLoginOptions').and.resolveTo();
  readonly startCompanyAccount = jasmine.createSpy('startCompanyAccount').and.resolveTo({ redirect_url: '/sso', status: 'redirect_required' });
  readonly signInWithPassword = jasmine.createSpy('signInWithPassword').and.resolveTo();
  readonly verifyMfa = jasmine.createSpy('verifyMfa').and.resolveTo();
  readonly session = signal({ token: 'jwt', user: { id: '1', email: 'viewer@assistdoc.local', fullName: 'Viewer Demo', role: 'viewer' }, tenant: { id: '1', name: 'AssistDoc Demo', slug: 'assistdoc-demo' } });
  readonly clearFeedback = jasmine.createSpy('clearFeedback');
  readonly clearActionRequired = jasmine.createSpy('clearActionRequired');
}

describe('SignInPageComponent', () => {
  let fixture: ComponentFixture<SignInPageComponent>;
  let component: SignInPageComponent;
  let authService: AuthServiceStub;
  let router: Router;

  beforeEach(async () => {
    spyOn(window.location, 'assign');

    await TestBed.configureTestingModule({
      imports: [SignInPageComponent],
      providers: [
        provideRouter([]),
        { provide: AuthService, useClass: AuthServiceStub },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(SignInPageComponent);
    component = fixture.componentInstance;
    authService = TestBed.inject(AuthService) as unknown as AuthServiceStub;
    router = TestBed.inject(Router);
    spyOn(router, 'navigateByUrl').and.resolveTo(true);
    fixture.detectChanges();
  });

  it('loads the login options on init', () => {
    expect(authService.loadLoginOptions).toHaveBeenCalled();
  });

  it('submits credentials and navigates after successful sign-in', async () => {
    component.email = 'viewer@assistdoc.local';
    component.password = 'password123';

    await component.submit();

    expect(authService.signInWithPassword).toHaveBeenCalledWith('viewer@assistdoc.local', 'password123');
    expect(router.navigateByUrl).toHaveBeenCalledWith('/');
  });

  it('shows an error when sign-in fails', async () => {
    authService.signInWithPassword.and.rejectWith(new Error('Credenziali non valide o accesso non consentito.'));

    await component.submit();

    expect(component.error()).toBe('Credenziali non valide o accesso non consentito.');
  });

  it('redirects to the company account provider when selected', async () => {
    component.selectMethod('company_account');

    await component.submit();

    expect(authService.startCompanyAccount).toHaveBeenCalled();
    expect(window.location.assign).toHaveBeenCalledWith('/sso');
  });
});
