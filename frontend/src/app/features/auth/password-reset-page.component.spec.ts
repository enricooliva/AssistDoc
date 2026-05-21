import { ActivatedRoute, Router } from '@angular/router';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AuthService } from '../../core/auth/auth.service';
import { PasswordResetPageComponent } from './password-reset-page.component';

class AuthServiceStub {
  readonly requestPasswordReset = jasmine.createSpy('requestPasswordReset').and.resolveTo('reset-123');
  readonly completePasswordReset = jasmine.createSpy('completePasswordReset').and.resolveTo();
}

describe('PasswordResetPageComponent', () => {
  let fixture: ComponentFixture<PasswordResetPageComponent>;
  let component: PasswordResetPageComponent;
  let authService: AuthServiceStub;
  let router: Router;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PasswordResetPageComponent],
      providers: [
        { provide: AuthService, useClass: AuthServiceStub },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              queryParamMap: {
                get: (key: string) => key === 'email' ? 'viewer@assistdoc.local' : null,
              },
            },
          },
        },
        {
          provide: Router,
          useValue: {
            navigateByUrl: jasmine.createSpy('navigateByUrl').and.resolveTo(true),
          },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(PasswordResetPageComponent);
    component = fixture.componentInstance;
    authService = TestBed.inject(AuthService) as unknown as AuthServiceStub;
    router = TestBed.inject(Router);
    fixture.detectChanges();
  });

  it('requests a reset journey for the current email', async () => {
    await component.requestReset();

    expect(authService.requestPasswordReset).toHaveBeenCalledWith('viewer@assistdoc.local');
    expect(component.resetId).toBe('reset-123');
  });

  it('completes the reset and redirects to sign-in', async () => {
    component.resetId = 'reset-123';
    component.newPassword = 'NuovaPassword123';

    await component.submit();

    expect(authService.completePasswordReset).toHaveBeenCalledWith('reset-123', 'NuovaPassword123');
    expect(router.navigateByUrl).toHaveBeenCalledWith('/sign-in');
  });
});
