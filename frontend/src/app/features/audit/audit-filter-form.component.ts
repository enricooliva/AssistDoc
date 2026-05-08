import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';

@Component({
  selector: 'app-audit-filter-form',
  standalone: true,
  imports: [CommonModule],
  template: `<section class="card"><strong>Filtri audit</strong><p>Filtro per attore, data, evento ed esito.</p></section>`,
  styles: [`.card { background: #fff; border: 1px solid #d7dfe8; border-radius: 8px; padding: 16px; }`],
})
export class AuditFilterFormComponent {}

