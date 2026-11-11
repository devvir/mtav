import {
  AUTO_AFTER_MS,
  autoTour,
  currentStep,
  defineTour,
  nextStep,
  offerTourInstead,
  previousStep,
  startTour,
  stopTour,
  tourState,
} from '@/lib/tour';

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

describe('tours played by themselves', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    stopTour();
    localStorage.clear();
    document.body.innerHTML = '<div data-tour="one"></div>';
    defineTour('first', () => [{ target: 'one', title: 'One', body: '' }]);
    window.history.replaceState({}, '', '/welcome');
  });

  afterEach(() => {
    stopTour();
    localStorage.clear();
    vi.useRealTimers();
  });

  it("wait while the Assistant offers the page's own instead", () => {
    autoTour('first');
    offerTourInstead();

    vi.advanceTimersByTime(AUTO_AFTER_MS);

    expect(currentStep()).toBeNull();
  });

  it('play on the next page when the one it was offered on had none', () => {
    offerTourInstead();

    window.history.pushState({}, '', '/families');
    autoTour('first');
    vi.advanceTimersByTime(AUTO_AFTER_MS);

    expect(currentStep()?.title).toBe('One');
  });
});
