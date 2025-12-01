<script setup lang="ts">
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    errors?: {
        nombre_juzgado_tribunal?: string;
        numero_orden_judicial?: string;
    };
}>();

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
        <Heading title="Detalles de la Orden Judicial" />
        <div class="space-y-2">
            <Label for="nombre_juzgado_tribunal">Nombre del Juzgado o Tribunal</Label>
            <Input
                id="nombre_juzgado_tribunal"
                v-model="nombre_juzgado_tribunal"
                type="text"
                required
                @input="toUpperCase"
            />
            <div v-if="errors?.nombre_juzgado_tribunal" class="text-sm text-red-500">
                {{ errors.nombre_juzgado_tribunal }}
            </div>
        </div>
        <div class="space-y-2">
            <Label for="numero_orden_judicial">Número de Orden Judicial</Label>
            <Input
                id="numero_orden_judicial"
                v-model="numero_orden_judicial"
                type="text"
                required
                @input="toUpperCase"
            />
            <div v-if="errors?.numero_orden_judicial" class="text-sm text-red-500">
                {{ errors.numero_orden_judicial }}
            </div>
        </div>
    </div>
</template>
