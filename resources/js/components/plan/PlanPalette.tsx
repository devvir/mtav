import { _ } from '@/lib/translations';
import { UnstyledButton } from '@mantine/core';
import { DEFAULT_COLORS, kindLabel, type ShapeType } from './kinds';
import type { PlanModel } from './model';

/** What a kind looks like, small: its material from the plan's defs (`look`), else its colour */
const swatch = (look: string, type: ShapeType) =>
  ({
    street: `url(#${look}-asphalt)`,
    parking: `url(#${look}-asphalt)`,
    green: `url(#${look}-grass)`,
    playground: `url(#${look}-play)`,
  })[type as string] ?? DEFAULT_COLORS[type];

/**
 * What can be added to the plan, each as it looks: one click puts one on the floor shown (a
 * unit asks its details first, `onAddUnit`, offered only with `withUnits`). Units added
 * elsewhere wait under it, to be put on the plan.
 */
export function PlanPalette({
  model,
  look,
  withUnits,
  onAddUnit,
}: {
  model: PlanModel;
  /** The plan's defs prefix, for the materials */
  look: string;
  withUnits: boolean;
  onAddUnit: () => void;
}) {
  const palette: { value: ShapeType; label: string }[] = [
    ...(withUnits ? [{ value: 'unit' as const, label: _('Unit') }] : []),
    { value: 'street', label: _('Street') },
    { value: 'green', label: _('Green area') },
    { value: 'common', label: _('Common space') },
    { value: 'parking', label: _('Parking') },
    { value: 'playground', label: _('Playground') },
    { value: 'entrance', label: _('Entrance') },
    { value: 'other', label: _('Other') },
  ];

  return (
    <nav className="plan-palette ds-surface" data-tour="plan.toolbar" aria-label={_('Add shapes')}>
      {palette.map((type) => (
        <UnstyledButton
          key={type.value}
          className="plan-tool"
          onClick={() =>
            type.value === 'unit' ? onAddUnit() : model.add(type.value, kindLabel(type.value) ?? '')
          }
        >
          <svg viewBox="0 0 28 18" width={28} height={18} aria-hidden>
            {type.value === 'entrance' ? (
              <polygon points="14,1 24,17 4,17" fill={swatch(look, type.value)} />
            ) : (
              <rect x={1} y={1} width={26} height={16} rx={3} fill={swatch(look, type.value)} />
            )}
          </svg>
          <span>{type.label}</span>
        </UnstyledButton>
      ))}
      {/* Units added elsewhere, waiting to be put on the plan */}
      {model.waiting.length ? (
        <div className="plan-waiting" role="group" aria-label={_('Units to place')}>
          <span className="plan-waiting-title">{_('To place')}</span>
          {model.waiting.map((item) => (
            <UnstyledButton
              key={item.key}
              className="unit-chip"
              aria-label={_('Place {unit} on the plan', { unit: item.unit?.identifier ?? '' })}
              onClick={() => model.place(item.key)}
            >
              {item.unit?.identifier}
            </UnstyledButton>
          ))}
        </div>
      ) : null}
    </nav>
  );
}
