import { defineTour, startTour, useTour } from '@/composables/useTour';

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
    const tour = useTour();

    startTour('sample');

    expect(tour.state.steps.map((step) => step.title)).toEqual(['One', 'Three']);
    expect(tour.current()?.title).toBe('One');
  });

  it('goes back and forth, and ends after the last step', () => {
    const tour = useTour();
    startTour('sample');

    tour.back();
    expect(tour.current()?.title).toBe('One');

    tour.next();
    expect(tour.current()?.title).toBe('Three');

    tour.next();
    expect(tour.current()).toBeNull();
  });

  it('does not start a tour with nothing to show', () => {
    document.body.innerHTML = '';
    const tour = useTour();

    startTour('sample');

    expect(tour.current()).toBeNull();
  });
});
