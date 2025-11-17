<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Plan } from '@/components/plans';
import { visitModal } from '@inertiaui/modal-vue';

const props = defineProps<{
  plan: ApiResource<Plan>;
}>();

// Get stats from plan items and project
const stats = {
  unitTypes: props.plan.project.unitTypes,
  totalUnits: props.plan.items.filter((item: PlanItem) => item.unit).length,
};
</script>

<template>
  <Head title="Project Plan" />

  <Breadcrumbs>
    <Breadcrumb route="plans.show" :params="plan.id" title="Project Plan" />
  </Breadcrumbs>

  <div class="space-y-6">
    <!-- Title and Stats Header -->
    <div>
      <h1 class="text-3xl font-bold">{{ plan.project.name }}</h1>
      <div class="mt-2 flex gap-6 text-sm text-muted-foreground">
        <span>{{ stats.unitTypes }} unit types</span>
        <span>{{ stats.totalUnits }} units</span>
      </div>
    </div>

    <!-- Plan Canvas -->
    <Card>
      <CardHeader>
        <div class="flex justify-between items-start">
          <div>
            <CardTitle>Floor Plan Layout</CardTitle>
            <CardDescription>
              {{ plan.width }}×{{ plan.height }} {{ plan.unit_system }}
            </CardDescription>
          </div>
        </div>
      </CardHeader>

      <CardContent>
        <div class="overflow-auto">
          <Plan :plan @unitClick="(id: number) => visitModal(route('units.show', id))" />
        </div>

        <div class="mt-4 text-sm text-muted-foreground">
          <p><strong>Controls:</strong> Hover over units to highlight them, click for details</p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>