import { CommonModule } from '@angular/common';
import { Component, computed, inject } from '@angular/core';
import { Router } from '@angular/router';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { TenantContextService } from '../tenant/tenant-context.service';

@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './app-shell.component.html',
  styleUrl: './app-shell.component.scss',
})
export class AppShellComponent {
  private readonly authService = inject(AuthService);
  private readonly tenantContext = inject(TenantContextService);
  private readonly router = inject(Router);

  readonly session = this.authService.session;
  readonly tenant = this.tenantContext.tenant;
  readonly feedback = this.authService.feedback;
  readonly initials = computed(() => this.session()?.user.fullName.slice(0, 1) ?? 'A');
  readonly canViewAudit = computed(() => this.session()?.user.role === 'super-admin');

  async signOut(): Promise<void> {
    await this.authService.signOut();
    await this.router.navigateByUrl('/sign-in');
  }
}
