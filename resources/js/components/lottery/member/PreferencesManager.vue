<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent, CardHeader } from '@/components/card';
import { HeartIcon } from 'lucide-vue-next';
import PreferencesEmptyState from './PreferencesEmptyState.vue';
import PreferencesGridMobile from './PreferencesGridMobile.vue';
import PreferencesGridDesktop from './PreferencesGridDesktop.vue';
import PreferencesGridMobileSlot from './PreferencesGridMobileSlot.vue';

const props = defineProps<{
  units: Unit[];
}>();

const preferences = reactive(props.units);
const form = useForm({ preferences });

const submit = (updatedPreferences: Unit[]) => {
  form.preferences = updatedPreferences;
  form.post(route('lottery.preferences'), { preserveScroll: true });
};
</script>

<template>
  <Card class="h-full flex flex-col max-w-auto">
    <CardHeader :title="_('Unit Preferences')">
      <span class="lg:hidden">{{ _('Drag and drop to rank your preferred units or use the arrow buttons') }}</span>
      <span class="hidden lg:inline">{{ _('Drag units to numbered slots to set your preferences (1 = highest priority)') }}</span>
    </CardHeader>

    <CardContent class="flex-1 overflow-y-auto w-full">
      <PreferencesEmptyState v-if="preferences.length === 0" />

      <!-- Mobile/Tablet: Vertical List -->
      <PreferencesGridMobile :preferences :disabled="form.processing" @change="submit">
        <template #unit-details="{ unit, index }">
          <PreferencesGridMobileSlot :unit :index />
        </template>
      </PreferencesGridMobile>

      <!-- Desktop: Grid Layout with Fixed Numbered Slots -->
      <PreferencesGridDesktop :preferences :disabled="form.processing" @change="submit">
        <template #priority-badge="{ index }">
          <div v-if="index < 3" class="absolute -top-1.5 -right-1.5 flex items-center justify-center">
            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm">
              <HeartIcon class="size-3.5" />
            </div>
          </div>
        </template>

        <template #unit-content="{ unit }">
          <h3 class="font-semibold text-text text-xl leading-tight font-mono">{{ unit.identifier }}</h3>
        </template>
      </PreferencesGridDesktop>
    </CardContent>
  </Card>
</template>