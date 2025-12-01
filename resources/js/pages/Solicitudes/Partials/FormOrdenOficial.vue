<script setup lang="ts">
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    errors?: {
        institucion?: string;
    };
}>();

const institucion = ref('');

const toUpperCase = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase();
};

defineExpose({
    institucion,
});
</script>

<template>
    <div class="space-y-4">
        <Heading title="Detalles de la Orden Oficial" />
        <div class="space-y-2">
            <Label for="institucion">Institución</Label>
            <Input
                id="institucion"
                v-model="institucion"
                type="text"
                required
                @input="toUpperCase"
            />
            <div v-if="errors?.institucion" class="text-sm text-red-500">
                {{ errors.institucion }}
            </div>
        </div>
    </div>
</template>
