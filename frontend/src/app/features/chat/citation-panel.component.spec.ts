import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CitationPanelComponent } from './citation-panel.component';

describe('CitationPanelComponent', () => {
  let fixture: ComponentFixture<CitationPanelComponent>;
  let component: CitationPanelComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CitationPanelComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(CitationPanelComponent);
    component = fixture.componentInstance;
  });

  it('renders the empty state when no citations are available', () => {
    component.citations = [];
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Nessuna citazione');
  });

  it('renders citation details when evidence is available', () => {
    component.citations = [
      {
        documentId: '1',
        documentName: 'Manuale Aziendale.pdf',
        sourceLabel: 'Segmento 1',
        quoteText: 'AssistDoc usa isolamento tenant lato server.',
      },
    ];
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Manuale Aziendale.pdf');
    expect(fixture.nativeElement.textContent).toContain('Segmento 1');
  });
});
