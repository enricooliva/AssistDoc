import { CommonModule } from '@angular/common';
import { Component, ViewChild, ViewContainerRef } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'formly-wrapper-accordion',
    imports: [CommonModule],
    template: `
    <div class="card">
      <h4 class="card-header">
         <span class="fs-6 mb-0">{{ to.label }}</span>
        <button
          class="btn btn-sm btn-link float-end"
          type="button"
          (click)="isCollapsed = !isCollapsed"
          [attr.aria-expanded]="!isCollapsed"
          [attr.aria-controls]="collapseId"
        >
          {{ isCollapsed ? 'Mostra' : 'Nascondi' }}
        </button>              
      </h4>
      <div [id]="collapseId" [hidden]="isCollapsed">
          <div class="card-body">
            <ng-container #fieldComponent></ng-container>
          </div>
      </div>
    </div>  
  `,
    styles: [`
    `],
    standalone: true
})
export class AccordionWrapperComponent extends FieldWrapper {
  public readonly collapseId = `accordion-${Math.random().toString(36).slice(2)}`;
  public isCollapsed = this.props.collapsed ?? true;
  @ViewChild('fieldComponent', { read: ViewContainerRef, static: true }) fieldComponent: ViewContainerRef;
}


