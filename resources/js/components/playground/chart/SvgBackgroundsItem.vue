<script setup lang="ts"></script>

<template>
  <div class="relative rounded">
    <div v-for="j in 4" :key="j" class="flex">
      <div v-for="i in 3" :key="i">
        <slot />
      </div>
    </div>

    <div
      class="absolute inset-8 translate-x-3 -translate-y-6 -rotate-5 space-y-4 overflow-hidden rounded-xs bg-white text-left font-serif text-xs text-black shadow-md ring-1 shadow-cyan-300/40 ring-gray-900/20"
    >
      <div class="mx-auto my-[2%] h-[94%] w-[90%] overflow-hidden leading-5">
        <h1 class="">Example letter</h1>

        <h2 class="">Example H2</h2>
        <p>
          Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia aspernatur, ipsam, sit delectus inventore eum
          maiores nisi, temporibus maxime eius voluptates. Ut voluptatibus porro facere? Eaque modi earum unde culpa.
          Lorem ipsum dolor sit amet consectetur, adipisicing elit.
        </p>

        <h3 class="">Example H3</h3>
        <p>
          Molestiae quas debitis perspiciatis praesentium iure ratione labore eos nobis sunt, quae ad quo dignissimos
          cumque eum quos natus dolores similique! Quam? A fugit maiores modi officia iste eum. Praesentium dolorem
          voluptates eum soluta, explicabo omnis labore consectetur nemo velit accusantium, saepe laudantium
          exercitationem voluptatibus cupiditate, impedit nihil. Eveniet est quia deserunt? Assumenda, nisi nostrum
          ipsum aut inventore hic? Totam vel, temporibus aliquid eius illum enim magnam nulla eligendi pariatur sed
          perferendis voluptate laudantium ullam praesentium cupiditate, est doloribus adipisci explicabo delectus?
        </p>

        <h4 class="">Example H4</h4>
        <p>
          Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia aspernatur, ipsam, sit delectus inventore eum
          maiores nisi, temporibus maxime eius voluptates. Ut voluptatibus porro facere? Eaque modi earum unde culpa.
          Lorem ipsum dolor sit amet consectetur, adipisicing elit.
        </p>

        <h3 class="">Example H3</h3>
        <p>
          Molestiae quas debitis perspiciatis praesentium iure ratione labore eos nobis sunt, quae ad quo dignissimos
          cumque eum quos natus dolores similique! Quam? A fugit maiores modi officia iste eum. Praesentium dolorem
          voluptates eum soluta, explicabo omnis labore consectetur nemo velit accusantium, saepe laudantium
          exercitationem voluptatibus cupiditate, impedit nihil. Eveniet est quia deserunt? Assumenda, nisi nostrum
          ipsum aut inventore hic? Totam vel, temporibus aliquid eius illum enim magnam nulla eligendi pariatur sed
          perferendis voluptate laudantium ullam praesentium cupiditate, est doloribus adipisci explicabo delectus?
        </p>

        <p>
          Molestiae quas debitis perspiciatis praesentium iure ratione labore eos nobis sunt, quae ad quo dignissimos
          cumque eum quos natus dolores similique! Quam? A fugit maiores modi officia iste eum. Praesentium dolorem
          voluptates eum soluta, explicabo omnis labore consectetur nemo velit accusantium, saepe laudantium
          exercitationem voluptatibus cupiditate, impedit nihil.
        </p>

        <p>
          Molestiae quas debitis perspiciatis praesentium iure ratione labore eos nobis sunt, quae ad quo dignissimos
          cumque eum quos natus dolores similique! Quam? A fugit maiores modi officia iste eum. Praesentium dolorem
          voluptates eum soluta, explicabo omnis labore consectetur nemo velit accusantium, saepe laudantium
          exercitationem voluptatibus cupiditate, impedit nihil.
        </p>
      </div>
    </div>

    <svg
      viewBox="0 0 24 24"
      fill="none"
      class="absolute top-6 -right-5 size-24 rotate-90 fill-stone-600 stroke-stone-700/20 stroke-1"
    >
      <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" />
      <path
        d="M20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"
      />
    </svg>
  </div>
</template>

<style scoped>
@reference '@/../css/app.css';

h1 {
  @apply mt-5 mb-4 text-3xl font-bold;
}

h2 {
  @apply mt-4 mb-2 text-xl font-semibold;
}

h3 {
  @apply mt-3 mb-1 text-base font-semibold text-accent-foreground;
}

h4 {
  @apply mt-3 mb-1 text-sm text-background/80;
}
</style>
