<script setup lang="ts">
import { EntityCard, CardContent, CardFooter } from '@/components/card';
import CreatedMeta from '@/components/card/snippets/CreatedMeta.vue';
import { User } from 'lucide-vue-next';

defineProps<{
  log: ApiResource<Log>;
}>();
</script>

<template>
  <EntityCard :resource="log" entity="log" type="show">
    <CardContent class="gap-3">
      <div class="flex items-center gap-2 text-sm text-text-muted">
        <User class="h-4 w-4" />
        <span v-if="log.creator_href">
          <Link :href="log.creator_href" class="text-primary hover:underline">
            {{ log.created_by }}
          </Link>
        </span>
        <span v-else>{{ log.created_by }}</span>
      </div>

      <h3 class="text-lg font-semibold text-text">{{ log.event }}</h3>
    </CardContent>

    <CardFooter class="flex items-center justify-between">
      <span v-if="log.project.name" class="text-sm text-text-muted">
        {{ log.project.name }}
      </span>

      <CreatedMeta />
    </CardFooter>
  </EntityCard>
</template>
