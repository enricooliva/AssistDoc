import { ChangeDetectionStrategy, Component, ElementRef, Input, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ControlValueAccessor } from '@angular/forms';
import { FieldType, FormlyFieldConfig } from '@ngx-formly/core';

@Component({
    selector: 'app-textarea-highlight',
    templateUrl: './textarea-highlight.component.html',
    styleUrls: ['./textarea-highlight.component.scss'],
    changeDetection: ChangeDetectionStrategy.OnPush,
    standalone: false
})

export class TextareaHighlightComponent extends FieldType { 
  
  @ViewChild('backdrop', {static: true})  $backdrop: ElementRef<HTMLDivElement>;
  @ViewChild('textarea', {static: true}) $textarea: ElementRef<HTMLTextAreaElement>;
  
  get highlightTexts(): string[] {       
    if (this.field.props.highlightTexts){
      return this.field.props.highlightTexts;    
    }
    return [];
  }

  get highlightedText() {
    return this.applyHighlights(this.field.formControl.value);
  }

  applyHighlights(text) {
    text = text ? text.replace(/\n$/g, '\n\n') : '';    
    this.highlightTexts.forEach((x) => {
      text = text.replace(new RegExp(x, 'g'), '<mark class="mymark">$&</mark>');
    });
    //console.log(text);
    return text;
  }

  handleScroll() {
    var scrollTop = this.$textarea.nativeElement.scrollTop;
    this.$backdrop.nativeElement.scrollTop = scrollTop;

    var scrollLeft = this.$textarea.nativeElement.scrollLeft;
    this.$backdrop.nativeElement.scrollLeft = scrollLeft;
  }

}



