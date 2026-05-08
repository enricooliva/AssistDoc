import { CommonModule } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-sign-in-page',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <section class="signin">
      <form class="signin__card" (ngSubmit)="submit()">
        <h1>Accedi ad AssistDoc</h1>
        <p>Usa credenziali locali per l'ambiente di sviluppo.</p>

        <label>
          Email
          <input [(ngModel)]="email" name="email" type="email" required />
        </label>

        <label>
          Password
          <input [(ngModel)]="password" name="password" type="password" required />
        </label>

        <p class="signin__error" *ngIf="error() || authFeedback()">{{ error() || authFeedback() }}</p>

        <button type="submit" [disabled]="submitting()">{{ submitting() ? 'Accesso in corso...' : 'Entra' }}</button>
      </form>
    </section>
  `,
  styles: [`
    .signin { min-height: 100vh; display: grid; place-items: center; background: #eef2f7; }
    .signin__card { width: min(420px, 92vw); background: #fff; padding: 32px; border-radius: 8px; display: grid; gap: 16px; box-shadow: 0 24px 48px rgba(16, 24, 32, .08); }
    label { display: grid; gap: 6px; color: #23313f; }
    input { border: 1px solid #c6d0db; border-radius: 8px; padding: 12px; }
    button { border: 0; border-radius: 8px; background: #2456d6; color: #fff; padding: 12px; }
    button[disabled] { opacity: .7; cursor: wait; }
    .signin__error { color: #b42318; margin: 0; }
  `],
})
export class SignInPageComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  email = 'viewer@assistdoc.local';
  password = 'password123';
  readonly error = signal('');
  readonly submitting = signal(false);
  readonly authFeedback = computed(() => this.authService.feedback());

  constructor() {
    this.authService.clearFeedback();
  }

  async submit(): Promise<void> {
    this.error.set('');
    this.submitting.set(true);

    try {
      await this.authService.signIn(this.email, this.password);
      await this.router.navigateByUrl('/');
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Accesso non riuscito.');
    } finally {
      this.submitting.set(false);
    }
  }
}
