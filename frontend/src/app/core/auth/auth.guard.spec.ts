import { signal, WritableSignal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router, UrlTree } from '@angular/router';
import { authGuard } from './auth.guard';
import { AuthService } from './auth.service';
import { SessionState } from './auth.models';

class AuthServiceStub {
  session: WritableSignal<SessionState | null> = signal(null);
  readonly feedback = signal('');
  readonly setAccessDenied = jasmine.createSpy('setAccessDenied');

  hasAnyRole(roles: string[]): boolean {
    const role = this.session()?.user.role;
    return !!role && roles.includes(role);
  }
}

describe('authGuard', () => {
  let authService: AuthServiceStub;
  let router: Router;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideRouter([]),
        { provide: AuthService, useClass: AuthServiceStub },
      ],
    });

    authService = TestBed.inject(AuthService) as unknown as AuthServiceStub;
    router = TestBed.inject(Router);
  });

  it('redirects unauthenticated users to sign-in', () => {
    const result = TestBed.runInInjectionContext(() => authGuard({ data: {} } as never, {} as never));

    expect(result instanceof UrlTree).toBeTrue();
    expect(router.serializeUrl(result as UrlTree)).toBe('/sign-in');
  });

  it('redirects authenticated users without a required role', () => {
    authService.session.set({
      token: 'token',
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
    });

    const result = TestBed.runInInjectionContext(() =>
      authGuard({ data: { roles: ['super-admin'] } } as never, {} as never),
    );

    expect(result instanceof UrlTree).toBeTrue();
    expect(router.serializeUrl(result as UrlTree)).toBe('/');
    expect(authService.setAccessDenied).toHaveBeenCalled();
  });
});
