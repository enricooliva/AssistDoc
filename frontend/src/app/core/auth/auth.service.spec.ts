import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { apiUrl } from '../api/api-url';
import { authInterceptor } from '../http/auth.interceptor';
import { AuthService } from './auth.service';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;
  let router: jasmine.SpyObj<Router>;

  beforeEach(() => {
    localStorage.clear();
    router = jasmine.createSpyObj<Router>('Router', ['navigateByUrl']);
    router.navigateByUrl.and.resolveTo(true);

    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
        AuthService,
        { provide: Router, useValue: router },
      ],
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  it('stores the authenticated session after sign-in', async () => {
    const promise = service.signIn('viewer@assistdoc.local', 'password123');

    const request = httpMock.expectOne(apiUrl('/api/v1/auth/password/login'));
    expect(request.request.method).toBe('POST');
    request.flush({
      token: 'jwt-token',
      token_type: 'Bearer',
      expires_in: 3600,
      user: {
        id: '1',
        email: 'viewer@assistdoc.local',
        full_name: 'Viewer Demo',
        role: 'viewer',
        tenant: {
          id: '1',
          name: 'AssistDoc Demo',
          slug: 'assistdoc-demo',
        },
      },
    });

    await promise;

    expect(service.session()?.token).toBe('jwt-token');
    expect(service.session()?.user.fullName).toBe('Viewer Demo');
    expect(localStorage.getItem('assistdoc.session')).toContain('jwt-token');
  });

  it('clears the stored session after logout', async () => {
    const loginPromise = service.signIn('viewer@assistdoc.local', 'password123');
    httpMock.expectOne(apiUrl('/api/v1/auth/password/login')).flush({
      token: 'jwt-token',
      token_type: 'Bearer',
      expires_in: 3600,
      user: {
        id: '1',
        email: 'viewer@assistdoc.local',
        full_name: 'Viewer Demo',
        role: 'viewer',
        tenant: {
          id: '1',
          name: 'AssistDoc Demo',
          slug: 'assistdoc-demo',
        },
      },
    });
    await loginPromise;

    const promise = service.signOut();
    const request = httpMock.expectOne(apiUrl('/api/v1/auth/logout'));
    expect(request.request.headers.get('Authorization')).toBe('Bearer jwt-token');
    request.flush({ message: 'Logout completed' });

    await promise;

    expect(service.session()).toBeNull();
    expect(localStorage.getItem('assistdoc.session')).toBeNull();
    expect(router.navigateByUrl).toHaveBeenCalledWith('/sign-in');
  });

  it('resets the session when restoreSession receives 401', async () => {
    localStorage.setItem(
      'assistdoc.session',
      JSON.stringify({
        token: 'expired-token',
        user: {
          id: '1',
          email: 'viewer@assistdoc.local',
          fullName: 'Viewer Demo',
          role: 'viewer',
        },
        tenant: {
          id: '1',
          name: 'AssistDoc Demo',
          slug: 'assistdoc-demo',
        },
      }),
    );

    TestBed.resetTestingModule();
    router = jasmine.createSpyObj<Router>('Router', ['navigateByUrl']);
    router.navigateByUrl.and.resolveTo(true);
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
        AuthService,
        { provide: Router, useValue: router },
      ],
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);

    const promise = service.restoreSession();
    const request = httpMock.expectOne(apiUrl('/api/v1/auth/me'));
    expect(request.request.headers.get('Authorization')).toBe('Bearer expired-token');
    request.flush(
      { error: { code: 'UNAUTHENTICATED', message: 'Autenticazione richiesta o non valida.' } },
      { status: 401, statusText: 'Unauthorized' },
    );

    await promise;

    expect(service.session()).toBeNull();
    expect(router.navigateByUrl).toHaveBeenCalledWith('/sign-in');
  });

  it('stores action-required state when MFA is requested', async () => {
    const promise = service.signInWithPassword('viewer@assistdoc.local', 'password123');

    const request = httpMock.expectOne(apiUrl('/api/v1/auth/password/login'));
    request.flush(
      {
        status: 'action_required',
        action: 'mfa_required',
        challenge_id: 'mfa-1',
        message: 'Inserisci il codice MFA.',
      },
      { status: 409, statusText: 'Conflict' },
    );

    await promise;

    expect(service.pendingChallengeId()).toBe('mfa-1');
    expect(service.lastActionRequired()?.action).toBe('mfa_required');
  });
});
