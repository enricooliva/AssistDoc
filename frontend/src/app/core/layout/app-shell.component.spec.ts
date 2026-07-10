import { computed, signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { NgbOffcanvas } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../auth/auth.service';
import { SessionState } from '../auth/auth.models';
import { TenantContextService } from '../tenant/tenant-context.service';
import { AppShellComponent } from './app-shell.component';

class AuthServiceStub {
  readonly session = signal<SessionState>({
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

class OffcanvasRefStub {
  readonly closed = new Subject<void>();
  readonly dismissed = new Subject<void>();
  readonly close = jasmine.createSpy('close').and.callFake(() => this.closed.next());
}

class OffcanvasServiceStub {
  readonly open = jasmine.createSpy('open').and.callFake(() => new OffcanvasRefStub() as never);
}

describe('AppShellComponent', () => {
  let fixture: ComponentFixture<AppShellComponent>;
  let component: AppShellComponent;
  let authService: AuthServiceStub;
  let router: Router;
  let offcanvas: OffcanvasServiceStub;

  beforeEach(async () => {
    authService = new AuthServiceStub();
    offcanvas = new OffcanvasServiceStub();

    await TestBed.configureTestingModule({
      imports: [AppShellComponent],
      providers: [
        provideRouter([]),
        { provide: AuthService, useValue: authService },
        { provide: NgbOffcanvas, useValue: offcanvas },
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
    expect(fixture.nativeElement.textContent).toContain('Tenant');
  });

  it('collapses and expands the left panel', () => {
    const toggleButton = fixture.nativeElement.querySelector('.shell__toggle') as HTMLButtonElement;

    toggleButton.click();
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.shell')?.classList.contains('shell--collapsed')).toBeTrue();
  });

  it('opens the lateral menu as an overlay on mobile screens', () => {
    component.isMobileViewport.set(true);
    fixture.detectChanges();

    const toggleButton = fixture.nativeElement.querySelector('.shell__toggle') as HTMLButtonElement;
    toggleButton.click();
    fixture.detectChanges();

    expect(offcanvas.open).toHaveBeenCalled();
    expect(component.sidebarOverlayOpen()).toBeTrue();
    expect(component.sidebarToggleLabel()).toBe('Chiudi menu laterale');
  });

  it('shows the user-management link for tenant-admin users', () => {
    authService.session.set({
      token: 'jwt-token',
      user: {
        id: '2',
        email: 'tenant-admin@assistdoc.local',
        fullName: 'Tenant Admin Demo',
        role: 'tenant-admin',
      },
      tenant: {
        id: '1',
        name: 'AssistDoc Demo',
        slug: 'assistdoc-demo',
      },
    });
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Utenti');
  });

  it('signs out and redirects to sign-in', async () => {
    await component.signOut();

    expect(authService.signOut).toHaveBeenCalled();
    expect(router.navigateByUrl).toHaveBeenCalledWith('/sign-in');
  });
});
