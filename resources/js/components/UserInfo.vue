<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

</script>

<template>
    <div class="grid flex-1 text-left text-sm leading-tight">
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">Nombre: {{ user.nombres }} {{ user.apellido_paterno }} {{ user.apellido_materno }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">Email: {{ user.email }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">Establecimiento: {{ user.establecimiento?.razon_social }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">Departamento: {{ user.departamento.nombre }}</span>
    </div>
</template>
