<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import PersonAvatar from '@/components/feedback/PersonAvatar.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Person } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Mail, Phone } from 'lucide-vue-next';

const props = defineProps<{ person: Person }>();

const page = usePage();
const isMe = computed(() => page.props.auth.user?.id === props.person.id);

async function leave() {
  const project = page.props.project!.name;
  if (
    await confirm({
      title: _('Leave {project}?', { project }),
      message: _('You will stop seeing the project. Someone has to invite you again to come back.'),
      confirmLabel: _('Leave'),
      danger: true,
    })
  ) {
    router.delete(route('membership.leave'));
  }
}
</script>

<template>
  <PageHeader :title="person.name" :back="{ href: route('people.index'), label: _('People') }" />

  <div class="max-w-2xl rounded-2xl border-2 border-border bg-surface p-5 sm:p-6">
    <div class="flex items-center gap-4">
      <PersonAvatar :name="person.name" :src="person.avatar" size="lg" />
      <div class="space-y-1">
        <p class="flex flex-wrap items-center gap-2">
          <Link
            v-if="person.family"
            :href="route('families.show', person.family.id)"
            class="font-semibold text-text-link hover:underline"
          >
            {{ _('{family} family', { family: person.family.name }) }}
          </Link>
          <Badge v-if="person.is_admin" tone="info">{{ _('Admin') }}</Badge>
        </p>
        <p v-if="person.joined_at" class="text-sm text-text-muted">
          {{ _('Joined') }} <DateTime :value="person.joined_at" :time="false" />
        </p>
      </div>
    </div>

    <p v-if="person.about" class="mt-5 whitespace-pre-line">{{ person.about }}</p>

    <dl
      v-if="person.email || person.phone"
      class="mt-5 space-y-2 border-t border-border-subtle pt-5"
    >
      <div v-if="person.email" class="flex items-center gap-2">
        <Mail class="size-4 text-text-muted" />
        <a :href="`mailto:${person.email}`" class="text-text-link hover:underline">{{
          person.email
        }}</a>
      </div>
      <div v-if="person.phone" class="flex items-center gap-2">
        <Phone class="size-4 text-text-muted" />
        <a :href="`tel:${person.phone}`" class="text-text-link hover:underline">{{
          person.phone
        }}</a>
      </div>
    </dl>

    <div v-if="isMe" class="mt-6 flex flex-wrap gap-2 border-t border-border-subtle pt-5">
      <Button variant="outline" as-child
        ><Link :href="route('profile.edit')">{{ _('Edit your profile') }}</Link></Button
      >
      <Button variant="ghost" class="text-error" @click="leave"
        ><LogOut /> {{ _('Leave the project') }}</Button
      >
    </div>
  </div>
</template>
