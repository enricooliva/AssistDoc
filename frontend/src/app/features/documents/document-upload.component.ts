import { CommonModule } from '@angular/common';
import { Component, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-document-upload',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <section class="card">
      <h2>Carica documento</h2>
      <label>
        Nome file
        <input [(ngModel)]="filename" name="filename" />
      </label>
      <button (click)="submit()">Carica</button>
      <p *ngIf="message()">{{ message() }}</p>
    </section>
  `,
  styles: [`.card { background: #fff; border: 1px solid #d7dfe8; border-radius: 8px; padding: 16px; }`],
})
export class DocumentUploadComponent {
  filename = '';
  readonly message = signal('');

  submit(): void {
    this.message.set(this.filename ? 'Documento inviato in coda.' : 'Nome file obbligatorio.');
  }
}

