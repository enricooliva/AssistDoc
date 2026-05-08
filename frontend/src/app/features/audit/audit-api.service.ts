import { Injectable, signal } from '@angular/core';
import { AuditEventItem } from './audit.models';

@Injectable({ providedIn: 'root' })
export class AuditApiService {
  readonly items = signal<AuditEventItem[]>([
    {
      id: 'audit-001',
      actor: 'Admin Demo',
      eventType: 'chat.question_submitted',
      outcome: 'success',
      occurredAt: new Date().toISOString(),
    },
  ]);
}

