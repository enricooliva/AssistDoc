import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';

@Component({
    selector: 'app-button-select',
    template: `
  <div class="btn-group btn-group-toggle flex-wrap" data-toggle="buttons">
    <ng-container *ngFor="let opt of options">                   
    <label class="btn m-2 p-2 rounded-pill flex-md-grow-0" [ngClass]="calculateClasses(opt)">            
        <input type="radio" name="options" autocomplete="off"  
        [value]="opt.value"
        [(ngModel)]="defaultChoice"
        (ngModelChange)="choose($event)">
        {{opt.label}}
    </label>                    
    </ng-container>      
    <label *ngIf="options && deselezione" class="btn btn-outline-danger m-2 rounded-pill flex-md-grow-0" title="Deseleziona">            
        <input type="radio" name="options" autocomplete="off" [value]="null" 
        [(ngModel)]="currentValue"
        (ngModelChange)="choose($event)">
        <span class="mt-1 oi oi-trash"></span>  
    </label>   
  </div>  
  `,
    styles: [],
    standalone: false
})
export class ButtonSelectComponent implements OnInit {
  
  @Input() options: { value: any, label: string }[];
  @Input() defaultChoice: any;
  //@Input() groupName: string;
  @Input() currentValue: any;
  @Input() btnStyle: string = 'outline-primary';
  @Input() deselezione: boolean = true;

  @Output() valueChosen: EventEmitter<any> = new EventEmitter();

  ngOnInit() {
      this.choose(this.defaultChoice);
  }

  private choose(value: string) {  
    this.valueChosen.emit(value);
  }

  calculateClasses(opt: any) {
    return {        
        'btn-outline-primary': this.btnStyle === 'outline-primary',
        'btn-outline-secondary': this.btnStyle === 'outline-secondary',
        'active': opt.value == this.currentValue
    };
  }
}



