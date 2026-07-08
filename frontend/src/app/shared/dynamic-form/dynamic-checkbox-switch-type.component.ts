import { Component, ChangeDetectorRef, OnChanges, OnInit, ViewRef, OnDestroy, AfterViewInit } from '@angular/core';
import { FieldType, FormlyExtension, FormlyFieldConfig } from '@ngx-formly/core';

@Component({
    selector: 'app-dynamic-checkbox-switch',
    template: `
  <formly-field  *ngFor="let field of field.fieldGroup" [field]="field"></formly-field>
  `,
    standalone: false
})
//&& model[field.key]
// <formly-form [fields]="to.fields" [model]="model[field.key]" [form]="form"></formly-form>
export class DynamicCheckboxSwitchTypeComponent extends FieldType implements FormlyExtension {


  static readonly KEY = 'dynamic-checkbox-switch';

  constructor() {
    super();
  }

  onPopulate(field: FormlyFieldConfig) {

    // skip if the field is already initialized
    if (field.fieldGroup) {
      return;
    }

    console.log('onPopulate');
    field.fieldGroup = [
      {
        type: 'checkbox-switch',
        key: field.props.key,
        props: {
          label: field.props.labelPostfix,
          indeterminate: true,
        },
        expressionProperties: {
          'props.description': (model: any) => this.labelDescription(model, field.props.key as string),
          'props.label': (model: any) => {
            const { customLabelTrue, customLabelFalse } = field.props;
            if (model){
              if (model && model[field.props.key as string] == null) {
                return `di ${field.props.avereOrEssere} o non ${field.props.avereOrEssere} ${field.props.labelPostfix}`;
              }
              if (model && model[field.props.key as string]) {
                return customLabelTrue ? customLabelTrue : `di ${field.props.avereOrEssere} ${field.props.labelPostfix}`;
              } else {
                return customLabelFalse ? customLabelFalse : `di non ${field.props.avereOrEssere} ${field.props.labelPostfix}`;
              }
            }
            
          }
        },
      },
      // {
      //   key: 'dati_'+field.props.key,
      //   fieldGroup: field.props.conditionalFieldGroup(),
      //   hideExpression: `field.parent.model.${field.props.key} === false || field.parent.model.${field.props.key} === null || field.parent.model.${field.props.key} === undefined`
      // },
    ];

    if (field.props.conditionalFieldGroup) {
      field.fieldGroup.push({
          key: 'dati_' + field.props.key,
          fieldGroup: field.props.conditionalFieldGroup(),
          hideExpression: `field.parent.model.${field.props.key} === false || field.parent.model.${field.props.key} === null || field.parent.model.${field.props.key} === undefined`
      });
    }
    
    if (field.props.conditionalFieldGroupTrue) {
      field.fieldGroup.push({
          key: 'dati_' + field.props.key,
          fieldGroup: field.props.conditionalFieldGroupTrue(),
          hideExpression: `field.parent.model.${field.props.key} === true || field.parent.model.${field.props.key} === null || field.parent.model.${field.props.key} === undefined`
      });
    }
    //console.log(field);
  }

  labelDescription = (model: any, prop: string): string => {
    return (model && model[prop] == null) ? 'scelta obbligatoria' : 'scelta obbligatoria';
  };

}




