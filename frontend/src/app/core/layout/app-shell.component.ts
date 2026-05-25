import { CommonModule } from '@angular/common';
import { Component, HostListener, TemplateRef, ViewChild, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { NgbOffcanvas, NgbOffcanvasModule, NgbOffcanvasRef } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../auth/auth.service';
import { TenantContextService } from '../tenant/tenant-context.service';

@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive, RouterOutlet, NgbOffcanvasModule],
  templateUrl: './app-shell.component.html',
  styleUrl: './app-shell.component.scss',
})
export class AppShellComponent {
  private readonly sidebarStorageKey = 'assistdoc.shell.sidebarCollapsed';
  private readonly mobileBreakpoint = 1080;
  private readonly authService = inject(AuthService);
  private readonly tenantContext = inject(TenantContextService);
  private readonly router = inject(Router);
  private readonly offcanvas = inject(NgbOffcanvas);

  @ViewChild('sidebarTemplate', { static: true }) private readonly sidebarTemplate!: TemplateRef<unknown>;

  readonly session = this.authService.session;
  readonly tenant = this.tenantContext.tenant;
  readonly feedback = this.authService.feedback;
  readonly initials = computed(() => this.session()?.user.fullName.slice(0, 1) ?? 'A');
  readonly canAccessChatAndDocuments = computed(() => ['super-admin', 'tenant-admin', 'operator', 'viewer'].includes(this.session()?.user.role ?? ''));
  readonly canManageTenants = computed(() => this.session()?.user.role === 'super-admin');
  readonly canManageUsers = computed(() => ['super-admin', 'tenant-admin'].includes(this.session()?.user.role ?? ''));
  readonly canViewAudit = computed(() => this.session()?.user.role === 'super-admin');
  readonly sidebarCollapsed = signal(this.loadSidebarPreference());
  readonly isMobileViewport = signal(this.detectMobileViewport());
  readonly sidebarOverlayOpen = signal(false);
  private sidebarOverlayRef: NgbOffcanvasRef | null = null;

  async signOut(): Promise<void> {
    await this.authService.signOut();
    await this.router.navigateByUrl('/sign-in');
  }

  toggleSidebar(): void {
    if (this.isMobileViewport()) {
      if (this.sidebarOverlayOpen()) {
        this.closeSidebarOverlay();
      } else {
        this.openSidebarOverlay();
      }

      return;
    }

    const nextValue = !this.sidebarCollapsed();
    this.sidebarCollapsed.set(nextValue);

    if (typeof window !== 'undefined') {
      window.localStorage.setItem(this.sidebarStorageKey, String(nextValue));
    }
  }

  handleSidebarNavigation(): void {
    if (this.isMobileViewport()) {
      this.closeSidebarOverlay();
    }
  }

  @HostListener('window:resize')
  handleWindowResize(): void {
    const nextViewportState = this.detectMobileViewport();

    if (nextViewportState !== this.isMobileViewport()) {
      this.isMobileViewport.set(nextViewportState);
    }

    if (!nextViewportState && this.sidebarOverlayOpen()) {
      this.closeSidebarOverlay();
    }
  }

  sidebarToggleLabel(): string {
    if (this.isMobileViewport()) {
      return this.sidebarOverlayOpen() ? 'Chiudi menu laterale' : 'Apri menu laterale';
    }

    return this.sidebarCollapsed() ? 'Mostra pannello laterale' : 'Nascondi pannello laterale';
  }

  sidebarToggleIcon(): string {
    if (this.isMobileViewport()) {
      return this.sidebarOverlayOpen() ? 'bi-x-lg' : 'bi-list';
    }

    return this.sidebarCollapsed() ? 'bi-layout-sidebar-inset-reverse' : 'bi-layout-sidebar-inset';
  }

  openSidebarOverlay(): void {
    if (this.sidebarOverlayOpen()) {
      return;
    }

    this.sidebarOverlayRef = this.offcanvas.open(this.sidebarTemplate, {
      position: 'start',
      scroll: false,
      backdrop: true,
      panelClass: 'shell__offcanvas-panel',
    });
    this.sidebarOverlayOpen.set(true);

    this.sidebarOverlayRef.closed.subscribe(() => this.resetSidebarOverlayState());
    this.sidebarOverlayRef.dismissed.subscribe(() => this.resetSidebarOverlayState());
  }

  closeSidebarOverlay(): void {
    this.sidebarOverlayRef?.close();
  }

  private loadSidebarPreference(): boolean {
    if (typeof window === 'undefined') {
      return false;
    }

    return window.localStorage.getItem(this.sidebarStorageKey) === 'true';
  }

  private resetSidebarOverlayState(): void {
    this.sidebarOverlayRef = null;
    this.sidebarOverlayOpen.set(false);
  }

  private detectMobileViewport(): boolean {
    if (typeof window === 'undefined') {
      return false;
    }

    return window.innerWidth <= this.mobileBreakpoint;
  }
}
