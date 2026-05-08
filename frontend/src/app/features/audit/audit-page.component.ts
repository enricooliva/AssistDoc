import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { AuditApiService } from './audit-api.service';
import { AuditFilterFormComponent } from './audit-filter-form.component';
import { AuditTableComponent } from './audit-table.component';

@Component({
  selector: 'app-audit-page',
  standalone: true,
  imports: [CommonModule, AuditFilterFormComponent, AuditTableComponent],
  template: `
    <section class="audit-page">
      <app-audit-filter-form />
      <app-audit-table [items]="api.items()" />
    </section>
  `,
  styles: [`.audit-page { display: grid; gap: 16px; }`],
})
export class AuditPageComponent {
  readonly api = inject(AuditApiService);
}

