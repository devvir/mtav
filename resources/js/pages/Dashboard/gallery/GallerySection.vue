<script setup lang="ts">
// Copilot - pending review
import { Link } from '@inertiajs/vue3';
import SectionHeader from '../shared/SectionHeader.vue';
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <section>
    <SectionHeader :title="_('Gallery')" :view-all-href="route('gallery')" />
    <Link :href="route('gallery')" class="block">
      <div class="relative h-64 overflow-hidden rounded-lg">
        <div class="absolute inset-0 flex items-center justify-center">
          <!-- Stacked images with rotation -->
          <div class="relative h-48 w-64">
            <div
              class="absolute inset-0 cursor-pointer overflow-hidden rounded-lg border-2 border-white shadow-lg transition-transform hover:rotate-0 hover:scale-105"
              style="transform: rotate(-6deg) translateY(8px)"
            >
              <img
                src="https://picsum.photos/400/300?1"
                alt="Gallery preview 1"
                class="h-full w-full object-cover"
              />
            </div>
            <div
              class="absolute inset-0 cursor-pointer overflow-hidden rounded-lg border-2 border-white shadow-lg transition-transform hover:rotate-0 hover:scale-105"
              style="transform: rotate(3deg) translateY(4px)"
            >
              <img
                src="https://picsum.photos/400/300?2"
                alt="Gallery preview 2"
                class="h-full w-full object-cover"
              />
            </div>
            <div
              class="absolute inset-0 cursor-pointer overflow-hidden rounded-lg border-2 border-white shadow-lg transition-transform hover:rotate-0 hover:scale-105"
              style="transform: rotate(0deg)"
            >
              <img
                src="https://picsum.photos/400/300?3"
                alt="Gallery preview 3"
                class="h-full w-full object-cover"
              />
            </div>
          </div>
        </div>
      </div>
    </Link>
  </section>
</template>
