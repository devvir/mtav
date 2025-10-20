<script setup lang="ts">
const msg = '{Message}';
const msg2 = ref('{Message}');
</script>

<template>
  <div class="p-18">
    <div>&lbrace;Message&rbrace; (literal) => <span>{Message}</span></div>
    <div>&lbrace;Hello World&rbrace; (literal with spaces) => <span> { Hello World } </span></div>
    <div>&lbrace;&lbrace;msg&rbrace;&rbrace; (const = Message) => {{ msg }}</div>
    <div>&lbrace;&lbrace;msg2&rbrace;&rbrace; (ref = Message) => {{ msg2 }}</div>
  </div>
</template>

<i18n lang="yaml" locale="it">
Message: 'Mensaje'
'Hello World': 'Hola Mundo'
</i18n>

<i18n lang="yaml" locale="es_UY">
Message: 'Mensaje'
['Hello World']: 'Hola Mundo'
</i18n>

<style scoped>
div {
  border: none;
}
</style>
