import { Component, ViewChild, ViewContainerRef } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'formly-wrapper-panel',
    template: `
    <div class="card border-1" style="border-radius: 3px">  
      <!-- Conditional Title -->
      <div *ngIf="to.title" class="card-header">
        {{ to.title  }}
      </div>   
      <div class="card-body">
        <ng-container #fieldComponent></ng-container>
      </div>
    </div>
  `,
    standalone: false
})
export class PanelsubfieldWrapperComponent extends FieldWrapper {
  @ViewChild('fieldComponent', { read: ViewContainerRef, static: true }) fieldComponent: ViewContainerRef;
}


