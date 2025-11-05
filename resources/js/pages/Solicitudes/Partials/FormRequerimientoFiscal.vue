<script setup lang="ts">
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const fiscal_apellidos_nombres = ref('');
const fiscal_de_materia = ref('');
const numero_de_caso = ref('');
const solicitante_apellidos_nombres = ref('');
const solicitante_identificacion = ref('');

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
        <Heading :level="2">Detalles del Requerimiento Fiscal</Heading>
        <div class="space-y-2">
            <Label for="fiscal_apellidos_nombres">Nombres y Apellidos del Fiscal</Label>
            <Input
                id="fiscal_apellidos_nombres"
                v-model="fiscal_apellidos_nombres"
                type="text"
            />
        </div>
        <div class="space-y-2">
            <Label for="fiscal_de_materia">Materia del Fiscal</Label>
            <Input
                id="fiscal_de_materia"
                v-model="fiscal_de_materia"
                type="text"
            />
        </div>
        <div class="space-y-2">
            <Label for="numero_de_caso">Número de Caso</Label>
            <Input
                id="numero_de_caso"
                v-model="numero_de_caso"
                type="text"
            />
        </div>
        <div class="space-y-2">
            <Label for="solicitante_apellidos_nombres">Nombres y Apellidos del Solicitante</Label>
            <Input
                id="solicitante_apellidos_nombres"
                v-model="solicitante_apellidos_nombres"
                type="text"
            />
        </div>
        <div class="space-y-2">
            <Label for="solicitante_identificacion">Identificación del Solicitante</Label>
            <Input
                id="solicitante_identificacion"
                v-model="solicitante_identificacion"
                type="text"
            />
        </div>
    </div>
</template>
