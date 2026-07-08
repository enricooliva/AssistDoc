import { Component, EventEmitter, OnDestroy, ViewChild } from '@angular/core';
import { NgbTypeahead } from '@ng-bootstrap/ng-bootstrap';
import { FieldType } from '@ngx-formly/core';
import { isObservable, Observable, of, OperatorFunction, merge } from 'rxjs';
import { Subject } from 'rxjs/internal/Subject';
import { debounceTime, distinctUntilChanged, filter, map, switchMap } from 'rxjs/operators';
import { IDropDown } from '../services/localita.service';
import { IComune } from '../store/comuni-data-store';


@Component({
    selector: 'app-typeahead-type',
    template: `
  <input type="text" class="form-control" [formControl]="formControl"
    [class.is-invalid]="showError"    
    [ngbTypeahead]="search"
    [inputFormatter]="inputformatter"
    [resultFormatter]="resultformatter"
    [editable]='false'
    (focus)="focus$.next($any($event).target.value)"
    (click)="click$.next($any($event).target.value)"
    #instance="ngbTypeahead"
   />    
  `,
    standalone: false
})
export class TypeaheadType extends FieldType implements OnDestroy {
    onDestroy$ = new Subject<void>();
    search$ = new EventEmitter();

    @ViewChild('instance', {static: true}) instance: NgbTypeahead;
    focus$ = new Subject<string>();
    click$ = new Subject<string>();

    search = (text$: Observable<string>) => {
    
        const debouncedText$ = text$.pipe(debounceTime(200), distinctUntilChanged());
        const clicksWithClosedPopup$ = this.click$.pipe(filter(() => !this.instance.isPopupOpen()));
        const inputFocus$ = this.focus$;

        return merge(debouncedText$, inputFocus$, clicksWithClosedPopup$).pipe(            
            //filter(term => term.length >= 2),
            switchMap(term => isObservable(this.to.options) ? this.to.options.pipe(
                map(x => 
                        [...new Set(x.filter(item => new RegExp('^'+term, 'i').test(this.inputformatter(item))).concat(
                            x.filter(item => new RegExp(term, 'mi').test(this.inputformatter(item))).slice(0, this.slice)
                        ).slice(0, this.slice))]                       
                    )
            ) : of(
                    [... new Set(this.to.options.filter(item => new RegExp('^'+term, 'i').test(this.inputformatter(item))).concat(
                        this.to.options.filter(item => new RegExp(term, 'mi').test(this.inputformatter(item))).slice(0, this.slice)
                    ).slice(0, this.slice))]
                )
            )
        );
    }

    get inputformatter(): (item: any) => string {
        return this.to.formatter || ((item: IDropDown): string => item.label);  
    }
    
    get resultformatter(): (item: any) => string {
        return this.to.formatter || ((item: IDropDown): string => item.label);  
    }

    get slice(): number {
        return this.to.slice || 10;
    }

    ngOnDestroy() {
        this.onDestroy$.complete();
    }
}


