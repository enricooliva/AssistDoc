import { signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { SignInPageComponent } from './sign-in-page.component';

class AuthServiceStub {
  readonly feedback = signal('');
  readonly signIn = jasmine.createSpy('signIn').and.resolveTo();
  readonly clearFeedback = jasmine.createSpy('clearFeedback');
}

describe('SignInPageComponent', () => {
  let fixture: ComponentFixture<SignInPageComponent>;
  let component: SignInPageComponent;
  let authService: AuthServiceStub;
  let router: Router;

  beforeEach(async () => {
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

  it('submits credentials and navigates after successful sign-in', async () => {
    component.email = 'viewer@assistdoc.local';
    component.password = 'password123';

    await component.submit();

    expect(authService.signIn).toHaveBeenCalledWith('viewer@assistdoc.local', 'password123');
    expect(router.navigateByUrl).toHaveBeenCalledWith('/');
  });

  it('shows an error when sign-in fails', async () => {
    authService.signIn.and.rejectWith(new Error('Credenziali non valide o accesso non consentito.'));

    await component.submit();

    expect(component.error()).toBe('Credenziali non valide o accesso non consentito.');
  });
});
