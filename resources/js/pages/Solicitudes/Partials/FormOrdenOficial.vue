<script setup lang="ts">
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const institucion = ref('');

defineExpose({
    institucion,
});
</script>

<template>
    <div class="space-y-4">
        <Heading :level="2">Detalles de la Orden Oficial</Heading>
        <div class="space-y-2">
            <Label for="institucion">Institución</Label>
            <Input
                id="institucion"
                v-model="institucion"
                type="text"
            />
        </div>
    </div>
</template>
