<script setup lang="ts">
import { usePage, Link, router } from '@inertiajs/vue3';
import { Settings, LogOut } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';

const page = usePage();
const user = page.props.auth.user;

// Computed para nombre completo
const fullName = `${user.nombres} ${user.apellido_paterno}${user.apellido_materno ? ' ' + user.apellido_materno : ''}`;

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <div class="flex flex-col gap-2 p-2 border-t border-sidebar-border bg-sidebar">
        <!-- User Info Section -->
        <div class="flex items-center gap-3 px-2 py-2">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground font-semibold text-sm">
                {{ user.nombres.charAt(0).toUpperCase() }}
            </div>
            <div class="flex flex-col flex-1 min-w-0">
                <p class="text-sm font-medium leading-none truncate">
                    {{ fullName }}
                </p>
                <p class="text-xs text-muted-foreground truncate mt-1">
                    {{ user.email }}
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-1 px-1">
            <Button
                variant="ghost"
                size="sm"
                class="w-full justify-start gap-2 h-9"
                as-child
            >
                <Link :href="edit()" prefetch>
                    <Settings class="h-4 w-4" />
                    <span class="text-sm">Configuraciones</span>
                </Link>
            </Button>
            
            <Button
                variant="ghost"
                size="sm"
                class="w-full justify-start gap-2 h-9 text-destructive hover:text-destructive hover:bg-destructive/10"
                as-child
            >
                <Link
                    :href="logout()"
                    @click="handleLogout"
                    data-test="logout-button"
                >
                    <LogOut class="h-4 w-4" />
                    <span class="text-sm">Cerrar Sesión</span>
                </Link>
            </Button>
        </div>
    </div>
</template>
