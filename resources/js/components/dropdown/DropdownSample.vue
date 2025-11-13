<script setup lang="ts">
import DropDown from './Dropdown.vue';
import DropdownContent from './DropdownContent.vue';
import DropdownTrigger from './DropdownTrigger.vue';

const disabled = ref(false);
</script>

<template>
  <DropDown
    :disabled="disabled"
    v-slot:default="{ isOpen, open, close, toggle }"
    class="mx-auto mb-6 max-w-96"
    @open="console.log(`Dropdown opened via ${$event}`)"
    @close="console.log(`Dropdown closed via ${$event}`)"
  >
    <div>{Test disabled state}</div>
    <button class="test-btn mt-2 mb-6" @click="disabled = !disabled">
      {{ disabled ? '{Enable Dropdown}' : '{Disable Dropdown}' }}
    </button>

    <DropdownTrigger
      class="mb-10 rounded-2xl bg-blue-950 px-4 py-3 text-white"
      :class="{ 'bg-blue-950/70': disabled }"
    >
      {Secondary Trigger}
    </DropdownTrigger>
    <DropdownTrigger
      class="rounded-2xl bg-blue-950 px-4 py-3 text-white"
      :class="{ 'rounded-b-none': isOpen, 'bg-blue-950/70': disabled }"
    >
      <div class="space-y-6">
        <h2 class="mb-4 leading-relaxed">
          <div class="text-xl font-extrabold">{Sample Dropdown Trigger}</div>
          <div class="text-sm opacity-50">{(see console for event-tracking)}</div>
          <div class="mt-2 opacity-80">
            {Try: click, enter, space, escape and click-outside the Dropdown}
          </div>
        </h2>

        <div>
          <h3 class="mb-2 text-lg font-bold">{Dropdown State}</h3>
          <ul>
            <li>isOpen: {{ isOpen }}</li>
            <li>disabled: {{ disabled }}</li>
          </ul>
        </div>

        <div class="space-y-2">
          <h3 class="mb-2 text-lg font-bold">{Exposed Dropdown Actions}</h3>
          <div class="flex justify-center gap-2">
            <button class="test-btn" :class="{ 'opacity-50': isOpen }" @click.stop="open()">
              {Open}
            </button>
            <button class="test-btn" :class="{ 'opacity-50': !isOpen }" @click.stop="close()">
              {Close}
            </button>
            <button class="test-btn" @click.stop="toggle()">{Toggle}</button>
          </div>
        </div>
      </div>
    </DropdownTrigger>

    <DropdownContent class="rounded-b-2xl bg-zinc-950 p-4 text-white">
      <div>{Custom Content}</div>
      <div><span>{Line}</span> 1</div>
      <div><span>{Line}</span> 2</div>
      <div><span>{Line}</span> 3</div>
      <div><span>{Line}</span> 4</div>
      <div><span>{Line}</span> 5</div>
    </DropdownContent>
  </DropDown>

  <div>{Sample Dropdown: Element after Dropdown}</div>
</template>

<style scoped>
button.test-btn {
  margin-left: 0.5rem;
  cursor: pointer;
  border-radius: 0.375rem;
  border-width: 2px;
  border-style: solid;
  background-color: rgba(255, 255, 255, 0.1);
  padding-inline: 0.75rem;
  padding-block: 0.125rem;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}
</style>

<i18n lang="yaml" locale="es_UY">
Custom Content: 'Contenido personalizado'
Line: 'Línea'
'Sample Dropdown: Element after Dropdown': 'Desplegable de Ejemplo: Elemento después del Desplegable'
Exposed Dropdown Actions: 'Acciones expuestas por el Desplegable'
'Try: click, enter, space, escape and click-outside the Dropdown': 'Prueba: click, enter, espacio, escape, y clickear fuera del Desplegable'
Dropdown State: 'Estado del Desplegable'
Open: 'Abrir'
Close: 'Cerrar'
Toggle: 'Alternar'
(see console for event-tracking): (ver la consola para seguimiento de eventos)
Sample Dropdown Trigger: Disparador de ejemplo
Secondary Trigger: Disparador secundario
Test disabled state: 'Probar Estado deshabilitado'
Enable Dropdown: Habilitar Desplegable
Disable Dropdown: Deshabilitar Desplegable
</i18n>
