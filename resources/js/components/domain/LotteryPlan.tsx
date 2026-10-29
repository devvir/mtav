import { FloorPlan } from '@/components/domain/FloorPlan';
import { moved } from '@/components/domain/RankingList';
import { _ } from '@/lib/translations';
import type { Plan, Unit } from '@/types/models';
import { Text } from '@mantine/core';
import { useEffect, useState, type PointerEvent as ReactPointerEvent } from 'react';

/** How far a press travels before it carries the unit (less is a click) */
const CARRY_AFTER = 5;
/** Near the window's top or bottom edge, carrying scrolls the page (the slots may be below) */
const EDGE = 48;

/**
 * The plan a family ranks by, with its ranking under it as numbered slots: a unit of its
 * type picked up off the plan (mouse or pen) and dropped on a slot takes that place; a
 * click on one (a tap on a touch screen) lights it up in the ranking. The unit being moved
 * or pointed at anywhere lights up on the plan and in its slot.
 */
export function LotteryPlan({
  plan,
  ranking,
  highlightType,
  ownUnit,
  canRank,
  focused,
  onFocus,
  onPick,
  onChange,
}: {
  plan: Plan;
  ranking: Unit[];
  highlightType: number | null;
  ownUnit: number | null;
  canRank: boolean;
  focused: number | null;
  onFocus: (unitId: number | null) => void;
  /** A unit clicked on the plan */
  onPick: (unitId: number) => void;
  onChange: (units: Unit[]) => void;
}) {
  // The unit carried off the plan, and where the pointer is
  const [carried, setCarried] = useState<{ unit: Unit; x: number; y: number } | null>(null);
  const [over, setOver] = useState<number | null>(null);
  const ranks = new Map(ranking.map((unit, index) => [unit.id, index + 1]));

  const press = (unitId: number, event: ReactPointerEvent) => {
    if (event.button !== 0) return;
    const unit = ranking.find((one) => one.id === unitId);
    if (!unit) return;
    const [x0, y0] = [event.clientX, event.clientY];
    const carries = canRank && event.pointerType !== 'touch';
    let carrying = false;
    const slotAt = (x: number, y: number) => {
      const slot = document.elementFromPoint(x, y)?.closest('[data-slot]');
      return slot ? Number(slot.getAttribute('data-slot')) : null;
    };
    const move = (moving: PointerEvent) => {
      if (!carries) return;
      if (!carrying && Math.hypot(moving.clientX - x0, moving.clientY - y0) < CARRY_AFTER) return;
      carrying = true;
      if (moving.clientY > window.innerHeight - EDGE) window.scrollBy(0, 14);
      else if (moving.clientY < EDGE) window.scrollBy(0, -14);
      onFocus(unit.id);
      setCarried({ unit, x: moving.clientX, y: moving.clientY });
      setOver(slotAt(moving.clientX, moving.clientY));
    };
    const end = (ended: PointerEvent) => {
      window.removeEventListener('pointermove', move);
      window.removeEventListener('pointerup', end);
      window.removeEventListener('pointercancel', end);
      setCarried(null);
      setOver(null);
      onFocus(null);
      if (!carrying) return onPick(unit.id);
      const slot = ended.type === 'pointerup' ? slotAt(ended.clientX, ended.clientY) : null;
      const from = ranking.indexOf(unit);
      if (slot !== null && slot !== from) onChange(moved(ranking, from, slot));
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', end);
    window.addEventListener('pointercancel', end);
  };

  // While carrying, the page does not select text under the pointer
  useEffect(() => {
    if (!carried) return undefined;
    document.documentElement.dataset.carrying = '';
    return () => {
      delete document.documentElement.dataset.carrying;
    };
  }, [carried]);

  return (
    <>
      <FloorPlan
        plan={plan}
        highlightType={highlightType}
        ownUnit={ownUnit}
        ranks={ranks}
        focused={focused}
        pressable={(unitId) => ranks.has(unitId)}
        onUnitPress={press}
      />
      {ranking.length ? (
        <div className="rank-slots" aria-label={_('Your preferences')} role="list">
          {canRank ? (
            <Text className="ds-caption rank-slots-hint">
              {_('Drag a unit from the plan onto a place to rank it there.')}
            </Text>
          ) : null}
          {ranking.map((unit, index) => (
            <div
              key={unit.id}
              role="listitem"
              className="rank-slot"
              data-slot={index}
              data-over={over === index || undefined}
              data-focused={focused === unit.id || undefined}
              onMouseEnter={() => onFocus(unit.id)}
              onMouseLeave={() => onFocus(null)}
            >
              <span className="rank-slot-position">{index + 1}</span>
              <span className="ds-truncate">{unit.identifier}</span>
            </div>
          ))}
        </div>
      ) : null}
      {carried ? (
        <div className="rank-carried" style={{ left: carried.x, top: carried.y }} aria-hidden>
          {carried.unit.identifier}
        </div>
      ) : null}
    </>
  );
}
