import { CommonModule } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-password-reset-page',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <section class="reset">
      <form class="reset__card" (ngSubmit)="submit()">
        <h1>Reimposta password</h1>
        <p>Richiedi o completa il reset per gli account gestiti con email e password.</p>

        <div class="reset__section">
          <label>
            Email
            <input [(ngModel)]="email" name="email" type="email" required />
          </label>

          <button type="button" (click)="requestReset()" [disabled]="loading() || !email">
            {{ loading() ? 'Invio richiesta...' : 'Richiedi reset' }}
          </button>
        </div>

        <div class="reset__section">
          <label>
            Codice reset
            <input [(ngModel)]="resetId" name="resetId" type="text" required />
          </label>

          <label>
            Nuova password
            <input [(ngModel)]="newPassword" name="newPassword" type="password" minlength="12" required />
          </label>
        </div>

        <p class="reset__message" *ngIf="message()">{{ message() }}</p>
        <p class="reset__error" *ngIf="error()">{{ error() }}</p>

        <button type="submit" [disabled]="loading() || !resetId || !newPassword">
          {{ loading() ? 'Salvataggio...' : 'Completa reset' }}
        </button>
      </form>
    </section>
  `,
  styles: [`
    .reset { min-height: 100vh; display: grid; place-items: center; background: #eef2f7; }
    .reset__card { width: min(480px, 92vw); background: #fff; padding: 32px; border-radius: 8px; display: grid; gap: 16px; }
    .reset__section { display: grid; gap: 12px; }
    label { display: grid; gap: 6px; color: #23313f; }
    input { border: 1px solid #c6d0db; border-radius: 8px; padding: 12px; }
    button { border: 0; border-radius: 8px; background: #2456d6; color: #fff; padding: 12px; }
    .reset__message { color: #2456d6; margin: 0; }
    .reset__error { color: #b42318; margin: 0; }
  `],
})
export class PasswordResetPageComponent {
  private readonly authService = inject(AuthService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  email = this.route.snapshot.queryParamMap.get('email') ?? '';
  resetId = '';
  newPassword = '';
  readonly message = signal('');
  readonly error = signal('');
  readonly loading = signal(false);

  async requestReset(): Promise<void> {
    this.error.set('');
    this.message.set('');
    this.loading.set(true);

    try {
      this.resetId = await this.authService.requestPasswordReset(this.email);
      this.message.set('Richiesta registrata. Usa il codice reset per completare l\'operazione.');
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Richiesta reset non riuscita.');
    } finally {
      this.loading.set(false);
    }
  }

  async submit(): Promise<void> {
    this.error.set('');
    this.message.set('');
    this.loading.set(true);

    try {
      await this.authService.completePasswordReset(this.resetId, this.newPassword);
      this.message.set('Password aggiornata. Ora puoi accedere.');
      await this.router.navigateByUrl('/sign-in');
    } catch (error) {
      this.error.set(error instanceof Error ? error.message : 'Completamento reset non riuscito.');
    } finally {
      this.loading.set(false);
    }
  }
}
