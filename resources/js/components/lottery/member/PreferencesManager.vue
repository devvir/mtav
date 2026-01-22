<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { HeartIcon } from 'lucide-vue-next';
import PreferencesEmptyState from './PreferencesEmptyState.vue';
import PreferencesGridDesktop from './PreferencesGridDesktop.vue';
import PreferencesGridMobile from './PreferencesGridMobile.vue';
import PreferencesGridMobileSlot from './PreferencesGridMobileSlot.vue';

const props = defineProps<{
  preferences: ApiResource<Unit>[];
}>();

const preferences = reactive(props.preferences);
const form = useForm({ preferences });

const submit = (updatedPreferences: Unit[]) => {
  form.preferences = updatedPreferences;
  form.post(route('lottery.preferences'), { preserveScroll: true });
};
</script>

<template>
  <Card class="max-w-auto flex h-full flex-col">
    <CardHeader :title="_('Unit Preferences')">
      <span class="lg:hidden">{{
        _('Drag and drop to rank your preferred units or use the arrow buttons')
      }}</span>
      <span class="hidden lg:inline">{{
        _('Drag units to numbered slots to set your preferences (1 = highest priority)')
      }}</span>
    </CardHeader>

    <CardContent class="w-full flex-1 overflow-y-auto">
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
          <div
            v-if="index < 3"
            class="absolute -top-1.5 -right-1.5 flex items-center justify-center"
          >
            <div
              class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm"
            >
              <HeartIcon class="size-3.5" />
            </div>
          </div>
        </template>

        <template #unit-content="{ unit }">
          <h3 class="font-mono text-xl leading-tight font-semibold text-text">
            {{ unit.identifier }}
          </h3>
        </template>
      </PreferencesGridDesktop>
    </CardContent>
  </Card>
</template>
