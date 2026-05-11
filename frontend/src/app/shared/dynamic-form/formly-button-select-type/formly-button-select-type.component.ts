import { ChangeDetectionStrategy, Component, OnInit } from '@angular/core';
import { FieldType } from '@ngx-formly/core';

@Component({
    selector: 'app-formly-button-select-type',
    template: `
    <div class="d-flex flex-wrap gap-2">
    <ng-container *ngIf="to.options | async as options">
      <ng-container *ngFor="let opt of options; let i = index">
        <label
          class="btn rounded-pill"
          [ngClass]="[
            'btn-' + (opt.color || to.btnStyle || 'outline-primary'),
            isSelected(opt) ? 'active' : ''
          ]"
          [attr.aria-checked]="opt.value === formControl.value"
          [attr.title]="opt.label"
          (click)="onButtonClick(opt)"
          [attr.tabindex]="0"
        >
          <span *ngIf="opt.icon" class="me-1"><i [class]="opt.icon"></i></span>
          {{ opt.label }}
        </label>
      </ng-container>
    </ng-container>
  </div>
  `,
    changeDetection: ChangeDetectionStrategy.OnPush,
    styles: [],
    standalone: false
})
export class FormlyButtonSelectTypeComponent extends FieldType {
  
  defaultOptions = {
    props: {
      options: [],
      deselezione: false,    
      btnStyle: 'outline-primary', // 'outline-primary' | 'outline-secondary'
    },
  };
  
  calculateClasses(opt: any) {
    
    return {        
        'btn-outline-primary': this.to.btnStyle ?  this.to.btnStyle === 'outline-primary' : true,
        'btn-outline-secondary': this.to.btnStyle === 'outline-secondary',
        'active': JSON.stringify(opt.value) === JSON.stringify(this.formControl.value)
    };
  }

  isSelected(opt: any): boolean {
    const value = this.formControl.value;
  
    // If either is null/undefined, return false
    if (!value || !opt.value) return false;
  
    // Compare arrays by value using JSON.stringify
    return JSON.stringify(opt.value) === JSON.stringify(value);
  }

  onButtonClick(opt: any) {
    const currentValue = this.formControl.value;
    this.formControl.setValue(currentValue === opt.value ? null : opt.value);
  }

}



