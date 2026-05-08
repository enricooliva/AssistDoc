import { computed, signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { TenantContextService } from '../tenant/tenant-context.service';
import { AppShellComponent } from './app-shell.component';

class AuthServiceStub {
  readonly session = signal({
    token: 'jwt-token',
    user: {
      id: '1',
      email: 'admin@assistdoc.local',
      fullName: 'Admin Demo',
      role: 'super-admin' as const,
    },
    tenant: {
      id: '1',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
    },
  });
  readonly feedback = signal('');
  readonly signOut = jasmine.createSpy('signOut').and.resolveTo();
}

describe('AppShellComponent', () => {
  let fixture: ComponentFixture<AppShellComponent>;
  let component: AppShellComponent;
  let authService: AuthServiceStub;
  let router: Router;

  beforeEach(async () => {
    authService = new AuthServiceStub();

    await TestBed.configureTestingModule({
      imports: [AppShellComponent],
      providers: [
        provideRouter([]),
        { provide: AuthService, useValue: authService },
        {
          provide: TenantContextService,
          useValue: { tenant: computed(() => authService.session().tenant) },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AppShellComponent);
    component = fixture.componentInstance;
    router = TestBed.inject(Router);
    spyOn(router, 'navigateByUrl').and.resolveTo(true);
    fixture.detectChanges();
  });

  it('renders authenticated shell chrome', () => {
    expect(fixture.nativeElement.textContent).toContain('AssistDoc');
    expect(fixture.nativeElement.textContent).toContain('Admin Demo');
  });

  it('signs out and redirects to sign-in', async () => {
    await component.signOut();

    expect(authService.signOut).toHaveBeenCalled();
    expect(router.navigateByUrl).toHaveBeenCalledWith('/sign-in');
  });
});
