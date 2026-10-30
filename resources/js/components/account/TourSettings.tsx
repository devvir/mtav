import { setToursOn, toursOn } from '@/lib/tour';
import { _ } from '@/lib/translations';
import { Switch } from '@mantine/core';
import { useState } from 'react';

/**
 * Whether each page's tour plays by itself on a first visit, on this device (the (?) by a
 * page's title has it either way)
 */
export function TourSettings() {
  const [on, setOn] = useState(toursOn);

  return (
    <Switch
      label={_('Guided tours')}
      description={_("Each page's tour on a first visit. The ? by its title plays it anytime.")}
      checked={on}
      onChange={(event) => {
        setToursOn(event.currentTarget.checked);
        setOn(event.currentTarget.checked);
      }}
    />
  );
}
