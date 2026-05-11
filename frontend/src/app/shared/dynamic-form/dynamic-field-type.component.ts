import { Component, ChangeDetectorRef, OnChanges, OnInit, ViewRef, OnDestroy, AfterViewInit } from '@angular/core';
import { FieldType, FormlyFieldConfig } from '@ngx-formly/core';

@Component({
    selector: 'app-dynamic-field',
    template: `
  <formly-form [fields]="to.fields" [model]="model" [form]="form" [options]="options"></formly-form>
  `,
    standalone: false
})
//&& model[field.key]
// <formly-form [fields]="to.fields" [model]="model[field.key]" [form]="form"></formly-form>
export class DynamicFieldTypeComponent extends FieldType{
  constructor() {
    super();    
  }  
  
  onPopulate(field: FormlyFieldConfig){
    //possiamo caricare i dati di iniziali   
    if (field.props.getFirstValue){     
      console.log('onPopulate');
      field.props.fields = field.props.getFirstValue(field);      
    }
  }
  
}




