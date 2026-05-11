import { Component, ViewChild, ViewContainerRef } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'formly-wrapper-panel',
    template: `
    <div class="card border-0">     
      <div class="card-body">
        <ng-container #fieldComponent></ng-container>
      </div>
    </div>
  `,
    standalone: false
})
export class PanelidentWrapperComponent extends FieldWrapper {  
}


