<script setup lang="ts">
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const nombre_juzgado_tribunal = ref('');
const numero_orden_judicial = ref('');

const toUpperCase = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase();
};

defineExpose({
    nombre_juzgado_tribunal,
    numero_orden_judicial,
});
</script>

<template>
    <div class="space-y-4">
        <Heading :level="2">Detalles de la Orden Judicial</Heading>
        <div class="space-y-2">
            <Label for="nombre_juzgado_tribunal">Nombre del Juzgado o Tribunal</Label>
            <Input
                id="nombre_juzgado_tribunal"
                v-model="nombre_juzgado_tribunal"
                type="text"
                @input="toUpperCase"
            />
        </div>
        <div class="space-y-2">
            <Label for="numero_orden_judicial">Número de Orden Judicial</Label>
            <Input
                id="numero_orden_judicial"
                v-model="numero_orden_judicial"
                type="text"
                @input="toUpperCase"
            />
        </div>
    </div>
</template>
