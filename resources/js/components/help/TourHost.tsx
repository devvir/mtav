import { useStore } from '@/lib/store';
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
import { ActionIcon, Button, Group, Portal, Text } from '@mantine/core';
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
  const card = useRef<HTMLDivElement>(null);

  // One measure per frame, however many scroll events come
  const frame = useRef(0);
  const measure = useCallback(() => {
    if (frame.current) return;
    frame.current = requestAnimationFrame(() => {
      frame.current = 0;
      const current = currentStep();
      setRect((current && findTarget(current.target)?.getBoundingClientRect()) ?? null);
    });
  }, []);

  useLayoutEffect(() => {
    if (!step) return undefined;
    const target = findTarget(step.target);
    target?.scrollIntoView({ block: 'center', behavior: 'smooth' });
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
  }, [step, measure]);

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

  /** Below the element if it fits, else above it; pinned to the bottom on phones */
  let position: CSSProperties = { left: 16, right: 16, bottom: 16 };
  if (!narrow && rect) {
    const left = Math.min(Math.max(16, rect.left), window.innerWidth - WIDTH - 16);
    const below = rect.bottom + PAD + 12;
    position =
      below + 220 < window.innerHeight
        ? { left, top: below, width: WIDTH }
        : { left, bottom: window.innerHeight - rect.top + PAD + 12, width: WIDTH };
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

        <div
          ref={card}
          role="dialog"
          aria-modal="true"
          aria-label={step.title}
          tabIndex={-1}
          className="tour-card ds-floating"
          style={position}
        >
          <Group align="flex-start" gap="sm" wrap="nowrap">
            <div style={{ flex: 1, minWidth: 0 }} aria-live="polite">
              <Text className="ds-label">
                {_('Step {n} of {total}', { n: state.index + 1, total: state.steps.length })}
              </Text>
              <Text fw={700} mt={4}>
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
          <Group justify="space-between" mt="md">
            <Button variant="subtle" size="xs" disabled={state.index === 0} onClick={previousStep}>
              {_('Back')}
            </Button>
            <Button size="xs" onClick={nextStep}>
              {last ? _('Done') : _('Next')}
            </Button>
          </Group>
        </div>
      </div>
    </Portal>
  );
}

/** Asked once, after the first tour someone ended early: whether to show no more */
function TourOffer() {
  return (
    <Portal>
      <div className="tour-offer ds-floating" role="dialog" aria-label={_('Tours')}>
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
