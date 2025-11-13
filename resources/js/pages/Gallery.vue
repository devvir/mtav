<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import CardBox from '@/components/shared/CardBox.vue';
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <Head title="Gallery" />

  <Breadcrumbs>
    <Breadcrumb route="gallery" text="Gallery" />
  </Breadcrumbs>

  <div
    class="has-image -mb-main-y @container/gallery column-gap-base w-full columns-1 @3xl:columns-2 @5xl:columns-3 @7xl:columns-4"
  >
    <CardBox
      v-for="idx in 20"
      :key="idx"
      :tabindex="idx"
      class="group w-full cursor-pointer overflow-hidden rounded-xl transition-all duration-300 not-hocus:opacity-90 not-active:hocus:scale-101"
    >
      <figure
        class="@container relative flex min-h-[11cqi] flex-col justify-center overflow-hidden"
      >
        <img
          :src="`https://picsum.photos/${Math.floor(Math.random() * 400) + 300}/${Math.floor(Math.random() * 400) + 300}?${idx}`"
          fetchpriority="high"
          class="max-h-[min(200cqi,80svh)] min-h-[50cqi] w-full object-cover"
          alt="TODO : use image description here"
        />

        <figcaption
          class="absolute -bottom-1 w-full bg-surface-sunken/90 p-2 text-sm transition-all not-group-hocus:bg-surface-sunken/80 not-group-hocus:backdrop-blur-sm"
        >
          <div
            class="transition-transform not-group-hocus:translate-y-[calc(100%-1.5em)] not-group-hocus:truncate group-hocus:line-clamp-3"
          >
            {{
              'Some description for this image goes here. '.repeat(
                Math.floor(Math.random() * 4) + 1,
              )
            }}
          </div>
          <div class="origin-bottom-left scale-85 leading-6 opacity-70">
            {{ _('Posted by') }} <span class="text-foreground/80">&lt;some user&gt;</span>
          </div>
        </figcaption>
      </figure>
    </CardBox>
  </div>
</template>
