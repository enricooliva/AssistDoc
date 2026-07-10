import { Component, OnInit, ViewChild } from '@angular/core';
import { FieldType } from '@ngx-formly/core';
import { NgbPopover } from '@ng-bootstrap/ng-bootstrap';

interface TimeStruct {
    hour: number;
    minute: number;
    second?: number;
}

@Component({
    selector: 'app-timepicker-type',
    template: `
    <!-- POPUP TEMPLATE -->
    <ng-template #popTemplate>
      <div class="p-2">
          <!-- HEADER WITH EXIT BUTTON -->
        <div class="d-flex justify-content-between align-items-center mb-2">
        <span>Seleziona ora</span>

        <button
            type="button"
            class="btn btn-sm btn-link p-0"
            (click)="closePopover()">
            <i class="oi oi-x"></i>
        </button>
        </div>
        
        <ngb-timepicker
          [(ngModel)]="timeModel"
          [meridian]="false"
          [seconds]="false"
          [minuteStep]="props.minuteStep ?? 1"
          (ngModelChange)="onTimeChange($event)">
        </ngb-timepicker>
      </div>
    </ng-template>

    <!-- INPUT GROUP -->
    <div class="input-group">
      <input
        class="form-control"
        [formControl]="formControl"
        [formlyAttributes]="field"
        [placeholder]="props.placeholder || 'HH:mm'"
        [pattern]="timePattern"
        [class.is-invalid]="showError"
        (blur)="normalizeInput()"
      />

      <!-- BUTTON LIKE DATEPICKER -->
      <button
        class="btn btn-outline-secondary input-group-text oi oi-clock"
        type="button"
        [ngbPopover]="popTemplate"
        triggers="manual"
        placement="bottom"
        autoClose="outside"
        #p="ngbPopover"
        (click)="toggle(p)"
        (hidden)="onPopoverClosed()">
      </button>
    </div>

  `,
    standalone: false,
})
export class TimepickerTypeComponent extends FieldType implements OnInit {
    @ViewChild('p') popover?: NgbPopover;

    timeModel: TimeStruct = { hour: 7, minute: 0 };
    timePattern = '^([01][0-9]|2[0-3]):([0-5][0-9])$';

    override defaultOptions = {
        props: {
            placeholder: 'HH:mm',
            minuteStep: 15,
        },
        validators: {
            time: {
                expression: (c) =>
                    !c.value || /^([01][0-9]|2[0-3]):([0-5][0-9])$/.test(c.value),
                message: 'Formato valido HH:mm',
            },
        },
    };

    ngOnInit(): void {
        const value = this.formControl.value as string;

        if (value && this.isValid(value)) {
            this.timeModel = this.parse(value);
        }
    }

    toggle(popover: NgbPopover) {
        const value = this.formControl.value;
        if (value && this.isValid(value)) {
            this.timeModel = this.parse(value);
        }
        popover.toggle();
    }

    onTimeChange(t: TimeStruct) {
        const step = Number(this.props.minuteStep ?? 15);

        let minute = this.snapToClosest(t.minute, step);
        let hour = t.hour;

        // handle overflow, e.g. 58 snapped to 60
        if (minute === 60) {
            minute = 0;
            hour = Math.min(hour + 1, 23);
        }

        const snapped: TimeStruct = { hour, minute };

        // optional min/max block
        // if (!this.isWithinRange(snapped)) {
        //     return;
        // }
        this.timeModel = snapped;
        //const value = this.format(t);
        this.formControl.setValue(this.format(snapped));
        this.formControl.markAsTouched();
        this.formControl.markAsDirty();        
    }

    private snapToClosest(minute: number, step: number): number {
        return Math.floor(minute / step) * step;
    }

    normalizeInput() {
        const value = this.formControl.value;
        if (!value || !this.isValid(value)) return;

        const normalized = this.format(this.parse(value));
        this.formControl.setValue(normalized);
    }

    private isValid(v: string): boolean {
        return /^([01][0-9]|2[0-3]):([0-5][0-9])$/.test(v);
    }

    private parse(v: string): TimeStruct {
        const [h, m] = v.split(':').map(Number);
        return { hour: h, minute: m };
    }

    private format(t: TimeStruct): string {
        const hh = String(t.hour).padStart(2, '0');
        const mm = String(t.minute).padStart(2, '0');
        return `${hh}:${mm}`;
    }

    onPopoverClosed() {
        this.formControl.markAsTouched();
        this.formControl.markAsDirty();
        this.formControl.updateValueAndValidity({ emitEvent: false });
    }

    closePopover(): void {
        this.popover?.close();
    }

}