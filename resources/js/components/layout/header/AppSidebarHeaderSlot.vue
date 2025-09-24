<template>
  <Teleport to="[data-slot='header-after']" defer>
    <div v-bind="$attrs">
      <slot />
    </div>
  </Teleport>
</template>
