<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Description, FormActions, FormErrors, FormHeader } from '../shared';
import EditPreview from './EditPreview.vue';

const emit = defineEmits<{
  cancel: [];
  submit: [];
}>();

const props = defineProps<{
  media: ApiResource<Media>;
  category: MediaCategory;
  categories: Record<MediaCategory, string>;
}>();
console.log(props.categories, props.category);
const form = useForm({
  description: props.media.description,
  category: props.category,
});

const updateMedia = () => {
  form.patch(route('media.update', props.media.id), {
    onSuccess: () => emit('submit'),
  });
};
</script>

<template>
  <div class="mx-auto w-full max-w-2xl space-y-6 px-6">
    <FormHeader
      :title="_('Edit {mediaCategory}', { mediaCategory: categories[category] })"
      :subtitle="
        _('Update the description for your {mediaCategory}', {
          mediaCategory: categories[category],
        })
      "
    />

    <main class="space-y-6">
      <EditPreview :media />
      <Description v-model="form.description" :form action="edit" />
      <FormErrors :form />
    </main>

    <footer class="space-y-6">
      <FormActions
        :form
        :submit-text="_('Update {mediaCategory}', { mediaCategory: categories[category] })"
        @cancel="emit('cancel')"
        @submit="updateMedia"
      />
    </footer>
  </div>
</template>
