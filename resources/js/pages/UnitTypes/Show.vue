<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import EditButton from '@/components/EditButton.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import ShowWrapper from '../shared/ShowWrapper.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _ } from '@/composables/useTranslations';
import { Building2, Home, Users } from 'lucide-vue-next';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  unit_type: ApiResource<UnitType>;
}>();
</script>

<template>
  <Head :title="unit_type.name" />

  <Breadcrumbs>
    <Breadcrumb route="unit_types.index" text="Unit Types" />
    <Breadcrumb route="unit_types.show" :params="unit_type.id" :text="unit_type.name" />
  </Breadcrumbs>

  <MaybeModal>
    <ShowWrapper :resource-id="unit_type.id">
      <Card class="size-full">
        <template v-slot:header>
          <div class="flex items-center gap-4">
            <div class="rounded-lg bg-surface-sunken p-3">
              <Building2 class="h-8 w-8 text-text-subtle" />
            </div>
            <div class="flex-1">
              <h2 class="text-2xl font-semibold text-text">{{ unit_type.name }}</h2>
              <p v-if="unit_type.description" class="mt-1 text-sm text-text-muted">
                {{ unit_type.description }}
              </p>
            </div>
          </div>
        </template>

        <div class="mt-6 space-y-6 px-3 py-5">
          <!-- Stats Grid -->
          <div class="grid gap-4 @md:grid-cols-2">
            <!-- Units Count -->
            <div class="rounded-lg border border-border bg-surface-sunken p-4">
              <div class="flex items-center gap-3">
                <div class="rounded-md bg-interactive/10 p-2">
                  <Home class="h-5 w-5 text-interactive" />
                </div>
                <div>
                  <div class="text-sm font-medium text-text-subtle">{{ _('Units') }}</div>
                  <div class="text-2xl font-semibold text-text">{{ unit_type.units_count || 0 }}</div>
                </div>
              </div>
            </div>

            <!-- Families Count -->
            <div class="rounded-lg border border-border bg-surface-sunken p-4">
              <div class="flex items-center gap-3">
                <div class="rounded-md bg-interactive/10 p-2">
                  <Users class="h-5 w-5 text-interactive" />
                </div>
                <div>
                  <div class="text-sm font-medium text-text-subtle">{{ _('Families') }}</div>
                  <div class="text-2xl font-semibold text-text">{{ unit_type.families_count || 0 }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Metadata -->
          <div class="border-t border-border pt-4 text-sm text-text-subtle" :title="unit_type.created_at">
            <span>{{ _('Created') }}</span>: {{ unit_type.created_ago }}
          </div>
        </div>

        <!-- Edit Link -->
        <div class="flex items-center justify-end border-t border-border pt-base">
          <EditButton :resource="unit_type" route-name="unit_types.edit" />
        </div>
      </Card>
    </ShowWrapper>
  </MaybeModal>
</template>
