import { currentStep, defineTour, nextStep, previousStep, startTour, tourState } from '@/lib/tour';

describe('guided tours', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div data-tour="one"></div><div data-tour="three"></div>';
    defineTour('sample', () => [
      { target: 'one', title: 'One', body: '' },
      { target: 'two', title: 'Two', body: '' },
      { target: 'three', title: 'Three', body: '' },
    ]);
  });

  it('skips steps whose element is not on the page', () => {
    startTour('sample');

    expect(tourState.get().steps.map((step) => step.title)).toEqual(['One', 'Three']);
    expect(currentStep()?.title).toBe('One');
  });

  it('goes back and forth, and ends after the last step', () => {
    startTour('sample');

    previousStep();
    expect(currentStep()?.title).toBe('One');

    nextStep();
    expect(currentStep()?.title).toBe('Three');

    nextStep();
    expect(currentStep()).toBeNull();
  });

  it('does not start a tour with nothing to show', () => {
    document.body.innerHTML = '';

    startTour('sample');

    expect(currentStep()).toBeNull();
  });
});
