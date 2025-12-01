<script setup lang="ts">
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    errors?: {
        fiscal_apellidos_nombres?: string;
        fiscal_de_materia?: string;
        numero_de_caso?: string;
        solicitante_apellidos_nombres?: string;
        solicitante_identificacion?: string;
    };
}>();

const fiscal_apellidos_nombres = ref('');
const fiscal_de_materia = ref('');
const numero_de_caso = ref('');
const solicitante_apellidos_nombres = ref('');
const solicitante_identificacion = ref('');

const toUpperCase = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase();
};

defineExpose({
    fiscal_apellidos_nombres,
    fiscal_de_materia,
    numero_de_caso,
    solicitante_apellidos_nombres,
    solicitante_identificacion,
});
</script>

<template>
    <div class="space-y-4">
        <Heading title="Detalles del Requerimiento Fiscal" />
        <div class="space-y-2">
            <Label for="fiscal_apellidos_nombres">Nombres y Apellidos del Fiscal</Label>
            <Input
                id="fiscal_apellidos_nombres"
                v-model="fiscal_apellidos_nombres"
                type="text"
                required
                @input="toUpperCase"
            />
            <div v-if="errors?.fiscal_apellidos_nombres" class="text-sm text-red-500">
                {{ errors.fiscal_apellidos_nombres }}
            </div>
        </div>
        <div class="space-y-2">
            <Label for="fiscal_de_materia">Materia del Fiscal</Label>
            <Input
                id="fiscal_de_materia"
                v-model="fiscal_de_materia"
                type="text"
                required
                @input="toUpperCase"
            />
            <div v-if="errors?.fiscal_de_materia" class="text-sm text-red-500">
                {{ errors.fiscal_de_materia }}
            </div>
        </div>
        <div class="space-y-2">
            <Label for="numero_de_caso">Número de Caso</Label>
            <Input
                id="numero_de_caso"
                v-model="numero_de_caso"
                type="text"
                required
                @input="toUpperCase"
            />
            <div v-if="errors?.numero_de_caso" class="text-sm text-red-500">
                {{ errors.numero_de_caso }}
            </div>
        </div>
        <div class="space-y-2">
            <Label for="solicitante_apellidos_nombres">Nombres y Apellidos del Solicitante</Label>
            <Input
                id="solicitante_apellidos_nombres"
                v-model="solicitante_apellidos_nombres"
                type="text"
                required
                @input="toUpperCase"
            />
            <div v-if="errors?.solicitante_apellidos_nombres" class="text-sm text-red-500">
                {{ errors.solicitante_apellidos_nombres }}
            </div>
        </div>
        <div class="space-y-2">
            <Label for="solicitante_identificacion">Identificación del Solicitante</Label>
            <Input
                id="solicitante_identificacion"
                v-model="solicitante_identificacion"
                type="text"
                required
            />
            <div v-if="errors?.solicitante_identificacion" class="text-sm text-red-500">
                {{ errors.solicitante_identificacion }}
            </div>
        </div>
    </div>
</template>
