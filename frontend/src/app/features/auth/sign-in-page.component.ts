import { CommonModule } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';
import { LoginMethod, LoginOption } from '../../core/auth/auth.models';

@Component({
  selector: 'app-sign-in-page',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
    <section class="signin">
      <form class="signin__card" (ngSubmit)="submit()">
        <h1>Accedi ad AssistDoc</h1>
        <p>Usa il metodo di accesso previsto per il tuo account.</p>

        <div class="signin__methods">
          <button
            *ngFor="let option of availableMethods()"
            type="button"
            class="signin__method"
            [class.signin__method--active]="method() === option.id"
            (click)="selectMethod(option.id)"
          >
            <span>{{ option.label }}</span>
            <small *ngIf="option.description">{{ option.description }}</small>
          </button>
        </div>

        <ng-container *ngIf="method() === 'password'">
          <label>
            Email
            <input [(ngModel)]="email" name="email" type="email" required />
          </label>

          <label>
            Password
            <input [(ngModel)]="password" name="password" type="password" required />
          </label>

          <div *ngIf="requiresMfa()" class="signin__mfa">
            <label>
              Codice MFA
              <input [(ngModel)]="verificationCode" name="verificationCode" type="text" inputmode="numeric" maxlength="6" />
            </label>
            <small>Usa il codice di verifica richiesto per completare l'accesso.</small>
          </div>

          <a routerLink="/reset-password" [queryParams]="{ email: email }">Password dimenticata?</a>
        </ng-container>

        <p class="signin__hint" *ngIf="method() === 'company_account'">
          Verrai reindirizzato al sistema di autenticazione aziendale.
        </p>

        <p class="signin__status" *ngIf="requiresPasswordReset()">
          Devi reimpostare la password prima di completare l'accesso.
          <a routerLink="/reset-password" [queryParams]="{ email: email }">Apri reset password</a>
        </p>

        <p class="signin__error" *ngIf="error() || authFeedback()">{{ error() || authFeedback() }}</p>
        <p class="signin__status" *ngIf="actionMessage()">{{ actionMessage() }}</p>

        <button type="submit" [disabled]="submitting()">
          {{ submitLabel() }}
        </button>
      </form>
    </section>
  `,
  styles: [`
    .signin { min-height: 100vh; display: grid; place-items: center; background: #eef2f7; }
    .signin__card { width: min(480px, 92vw); background: #fff; padding: 32px; border-radius: 8px; display: grid; gap: 16px; box-shadow: 0 24px 48px rgba(16, 24, 32, .08); }
    .signin__methods { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .signin__method { border: 1px solid #c6d0db; border-radius: 8px; background: #fff; color: #23313f; padding: 12px; text-align: left; display: grid; gap: 4px; }
    .signin__method small { color: #5d6b79; }
    .signin__method--active { border-color: #2456d6; background: #eef3ff; color: #163a88; }
    .signin__mfa { display: grid; gap: 6px; padding: 12px; border-radius: 8px; background: #f8fafc; }
    .signin__hint, .signin__status { margin: 0; color: #415161; }
    label { display: grid; gap: 6px; color: #23313f; }
    input { border: 1px solid #c6d0db; border-radius: 8px; padding: 12px; }
    button[type="submit"] { border: 0; border-radius: 8px; background: #2456d6; color: #fff; padding: 12px; }
    button[disabled] { opacity: .7; cursor: wait; }
    .signin__error { color: #b42318; margin: 0; }
  `],
})
export class SignInPageComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  email = '';
  password = '';
  verificationCode = '';
  readonly method = signal<LoginMethod>('password');
  readonly error = signal('');
  readonly submitting = signal(false);
  readonly authFeedback = computed(() => this.authService.feedback());
  readonly availableMethods = computed<LoginOption[]>(() => this.authService.loginOptions());
  readonly actionRequired = this.authService.lastActionRequired;
  readonly actionMessage = computed(() => this.actionRequired()?.message ?? '');
  readonly requiresMfa = computed(() => this.actionRequired()?.action === 'mfa_required');
  readonly requiresPasswordReset = computed(() => this.actionRequired()?.action === 'password_reset_required');
  readonly submitLabel = computed(() => {
    if (this.method() === 'company_account') {
      return this.submitting() ? 'Reindirizzamento...' : 'Accedi con account aziendale';
    }

    if (this.requiresMfa()) {
      return this.submitting() ? 'Verifica in corso...' : 'Conferma MFA';
    }

    return this.submitting() ? 'Accesso in corso...' : 'Accedi con email e password';
  });

  constructor() {
    this.authService.clearFeedback();
    void this.authService.loadLoginOptions();
  }

  selectMethod(method: LoginMethod): void {
    this.method.set(method);
    this.error.set('');
    this.verificationCode = '';
    this.authService.clearActionRequired();
  }

  async submit(): Promise<void> {
    this.error.set('');
    this.submitting.set(true);

    try {
      if (this.method() === 'company_account') {
        const response = await this.authService.startCompanyAccount();
        if (typeof window !== 'undefined') {
          window.location.assign(response.redirect_url);
        }
        return;
      }

      if (this.requiresMfa()) {
        await this.authService.verifyMfa(this.verificationCode);
      } else {
        await this.authService.signInWithPassword(this.email, this.password);
      }

      if (this.authService.session()) {
        await this.router.navigateByUrl('/');
      }
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Accesso non riuscito.');
    } finally {
      this.submitting.set(false);
    }
  }
}
