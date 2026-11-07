import { route } from '@/lib/route';
import { useStore } from '@/lib/store';
import { toast } from '@/lib/toasts';
import {
  currentStep,
  findTarget,
  keepTours,
  nextStep,
  noMoreTours,
  previousStep,
  stopTour,
  tourState,
} from '@/lib/tour';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { ActionIcon, Button, FocusTrap, Group, Portal, Text } from '@mantine/core';
import { useMediaQuery } from '@mantine/hooks';
import { IconX } from '@tabler/icons-react';
import {
  useCallback,
  useEffect,
  useLayoutEffect,
  useRef,
  useState,
  type CSSProperties,
} from 'react';

const PAD = 8;
const WIDTH = 352;

/** How long a step follows its element as the page scrolls it into view */
const FOLLOW_MS = 700;

/**
 * Plays the current tour: dims the page, lights up the step's element, and shows the step
 * beside it (at the bottom of the screen on phones). Keyboard: ← → Esc. The light follows
 * its element at once, a measure per frame at most, never behind it.
 */
export function TourHost() {
  const state = useStore(tourState);
  const step = state.id ? state.steps[state.index] : null;
  const narrow = useMediaQuery('(max-width: 640px)');
  const [rect, setRect] = useState<DOMRect | null>(null);
  // The step's own height, measured with its element: where it fits depends on it
  const [height, setHeight] = useState(220);
  const card = useRef<HTMLDivElement>(null);

  // One measure per frame, however many scroll events come
  const frame = useRef(0);
  const measure = useCallback(() => {
    if (frame.current) return;
    frame.current = requestAnimationFrame(() => {
      frame.current = 0;
      const current = currentStep();
      setRect((current && findTarget(current.target)?.getBoundingClientRect()) ?? null);
      setHeight(card.current?.offsetHeight || 220);
    });
  }, []);

  // The focus goes back where it was when the tour began, if that is still there and nothing
  // else took it (a tour ending by leaving the page: the page's heading)
  const playing = Boolean(step);
  useLayoutEffect(() => {
    if (!playing) return undefined;
    const before = document.activeElement as HTMLElement | null;
    return () => {
      if (before?.isConnected && document.activeElement === document.body) {
        before.focus({ preventScroll: true });
      }
    };
  }, [playing]);

  useLayoutEffect(() => {
    if (!step) return undefined;
    const target = findTarget(step.target);
    // On phones the step's card takes the bottom: the element goes to the top, under the bar
    target?.scrollIntoView({ block: narrow ? 'start' : 'center', behavior: 'smooth' });
    card.current?.focus({ preventScroll: true });
    // Along with the page as it scrolls the element into view
    const until = performance.now() + FOLLOW_MS;
    let follow = 0;
    const follow_ = () => {
      measure();
      if (performance.now() < until) follow = requestAnimationFrame(follow_);
    };
    follow = requestAnimationFrame(follow_);
    return () => cancelAnimationFrame(follow);
  }, [step, measure, narrow]);

  useEffect(() => () => cancelAnimationFrame(frame.current), []);

  useEffect(() => {
    if (!step) return undefined;
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') stopTour();
      if (event.key === 'ArrowRight') nextStep();
      if (event.key === 'ArrowLeft') previousStep();
    };
    window.addEventListener('resize', measure);
    window.addEventListener('scroll', measure, { passive: true, capture: true });
    window.addEventListener('keydown', onKey);
    return () => {
      window.removeEventListener('resize', measure);
      window.removeEventListener('scroll', measure, { capture: true });
      window.removeEventListener('keydown', onKey);
    };
  }, [step, measure]);

  // Leaving the page is no dismissal: the tour simply ends
  useEffect(() => router.on('navigate', () => stopTour({ finished: true })), []);

  if (!step) return state.offer ? <TourOffer /> : null;

  const last = state.index === state.steps.length - 1;

  /**
   * Below the element if it fits, else above it, else beside it (a sidebar as tall as the
   * screen); never off the screen. On phones, across the screen: at the bottom, or at the top
   * for an element in the lower half (the tabs)
   */
  let position: CSSProperties =
    narrow && rect && rect.top + rect.height / 2 > window.innerHeight / 2
      ? { left: 16, right: 16, top: 16 }
      : { left: 16, right: 16, bottom: 16 };
  if (!narrow && rect) {
    const gap = PAD + 12;
    const across = (value: number) => Math.min(Math.max(16, value), window.innerWidth - WIDTH - 16);
    const down = (value: number) => Math.min(Math.max(16, value), window.innerHeight - height - 16);
    if (rect.bottom + gap + height <= window.innerHeight - 16) {
      position = { left: across(rect.left), top: rect.bottom + gap, width: WIDTH };
    } else if (rect.top - gap - height >= 16) {
      position = { left: across(rect.left), top: rect.top - gap - height, width: WIDTH };
    } else if (rect.right + gap + WIDTH <= window.innerWidth - 16) {
      position = { left: rect.right + gap, top: down(rect.top), width: WIDTH };
    } else if (rect.left - gap - WIDTH >= 16) {
      position = { left: rect.left - gap - WIDTH, top: down(rect.top), width: WIDTH };
    } else {
      position = { left: across(rect.left), bottom: 16, width: WIDTH };
    }
  }

  return (
    <Portal>
      <div className="tour" style={{ position: 'fixed', inset: 0, zIndex: 400 }}>
        {/* A click outside ends the tour */}
        <div style={{ position: 'absolute', inset: 0 }} onClick={() => stopTour()} />
        {rect ? (
          <>
            {/* The rest of the page, a little blurred and dimmed, in four panes around it */}
            <div
              className="tour-dim"
              style={{ top: 0, left: 0, right: 0, height: Math.max(0, rect.top - PAD) }}
            />
            <div
              className="tour-dim"
              style={{ top: rect.bottom + PAD, left: 0, right: 0, bottom: 0 }}
            />
            <div
              className="tour-dim"
              style={{
                top: rect.top - PAD,
                left: 0,
                width: Math.max(0, rect.left - PAD),
                height: rect.height + PAD * 2,
              }}
            />
            <div
              className="tour-dim"
              style={{
                top: rect.top - PAD,
                left: rect.right + PAD,
                right: 0,
                height: rect.height + PAD * 2,
              }}
            />
            <div
              className="tour-spotlight"
              style={{
                transform: `translate(${rect.left - PAD}px, ${rect.top - PAD}px)`,
                width: rect.width + PAD * 2,
                height: rect.height + PAD * 2,
              }}
            />
          </>
        ) : (
          <div className="tour-dim" style={{ inset: 0 }} />
        )}

        <FocusTrap>
          <div
            ref={card}
            role="dialog"
            aria-modal="true"
            aria-label={step.title}
            tabIndex={-1}
            className="tour-card ds-floating"
            style={position}
          >
            <FocusTrap.InitialFocus />
            <Group align="flex-start" gap="sm" wrap="nowrap">
              <div style={{ flex: 1, minWidth: 0 }} aria-live="polite">
                <Text className="ds-label">
                  {_('Step {n} of {total}', { n: state.index + 1, total: state.steps.length })}
                </Text>
                <Text fw={700} mt={4} className="tour-title">
                  {step.title}
                </Text>
                <Text size="sm" c="dimmed" mt={4}>
                  {step.body}
                </Text>
              </div>
              <ActionIcon aria-label={_('End the tour')} onClick={() => stopTour()} radius="xl">
                <IconX size={18} />
              </ActionIcon>
            </Group>
            <Group justify="space-between" mt="md" gap="xs">
              <Button variant="subtle" color="gray" size="xs" px={6} onClick={endAllTours}>
                {_('No more tours')}
              </Button>
              <Group gap="xs">
                <Button
                  variant="subtle"
                  size="xs"
                  disabled={state.index === 0}
                  onClick={previousStep}
                >
                  {_('Back')}
                </Button>
                <Button size="xs" onClick={nextStep}>
                  {last ? _('Done') : _('Next')}
                </Button>
              </Group>
            </Group>
          </div>
        </FocusTrap>
      </div>
    </Portal>
  );
}

/** From any tour: this one ends, and none plays by itself again (Preferences turns them back on) */
function endAllTours() {
  stopTour();
  noMoreTours();
  toast({
    tone: 'info',
    message: _('No more tours. You can turn them back on in Preferences.'),
    href: route('profile.edit', { section: 'preferences' }),
  });
}

/** Asked once, after the first tour someone ended early: whether to show no more */
function TourOffer() {
  return (
    <Portal>
      <div className="tour-offer ds-floating" role="status" aria-label={_('Tours')}>
        <Text fw={700}>{_('Show no more tours?')}</Text>
        <Text size="sm" c="dimmed" mt={4}>
          {_("You can always take one from the ? by each page's title.")}
        </Text>
        <Group justify="flex-end" gap="xs" mt="md">
          <Button variant="subtle" size="xs" onClick={keepTours}>
            {_('Keep showing them')}
          </Button>
          <Button size="xs" onClick={noMoreTours}>
            {_('No more tours')}
          </Button>
        </Group>
      </div>
    </Portal>
  );
}
