import { Component, OnInit, Input } from '@angular/core';
import { ControlBase } from './control-base';
import { FormGroup } from '@angular/forms';
import { map } from 'rxjs/operators';

@Component({
    selector: 'app-dynamic-form',
    templateUrl: './dynamic-form.component.html',
    standalone: false
})
export class DynamicFormComponent implements OnInit {

  @Input() control: ControlBase<any>;
  @Input() form: FormGroup;
  get isValid() { return this.form.controls[this.control.key].valid; }
  
  constructor() {}

  ngOnInit() {
    // if (this.control.controlType === 'datepicker'){
    //   this.form.controls[this.control.key].valueChanges.subscribe(value => {      
    //     let d = value as Date;
    //     if(d) {
    //         if (typeof d.getMonth === 'function') {
    //             this.form.controls[this.control.key].patchValue({
    //                 date: {
    //                     day: d.getUTCDay(),
    //                     month: d.getUTCMonth(),
    //                     year: d.getUTCFullYear()
    //                 }
    //             });
    //         }
    //     }
    //     return value;
    //   });        
    // }
  }

}



