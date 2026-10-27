<script setup lang="ts">
import UnitForm from '@/components/forms/UnitForm.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { UnitType } from '@/types/models';

defineProps<{ unitTypes: UnitType[]; unitType: number | null }>();
</script>

<template>
  <PageHeader
    :title="_('Add units')"
    :back="{ href: route('manage.units.index'), label: _('Units') }"
  >
    <template #info>
      <InfoTip :title="_('Adding units')" tour="manage.units">
        <p>
          {{
            _(
              "Add the project's units one by one, each with its type. Every new unit also appears on the plan, ready to be placed.",
            )
          }}
        </p>
      </InfoTip>
    </template>
  </PageHeader>
  <UnitForm :unit-types :unit-type />
</template>
