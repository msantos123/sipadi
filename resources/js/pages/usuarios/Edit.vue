<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { BreadcrumbItem, Nacionalidad, Municipio, Departamento, Establecimiento, Sucursal, Role, User } from '@/types'
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'

interface EstablecimientoConSucursales extends Establecimiento {
    sucursales: Sucursal[];
}

interface UserConRoles extends User {
    roles: Role[];
}

const props = defineProps<{
    usuario: UserConRoles;
    nacionalidades: Nacionalidad[],
    departamentos: Departamento[],
    municipios: Municipio[],
    roles: Role[],
    establecimiento: EstablecimientoConSucursales | null
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Usuarios', href: '/usuarios' },
    { title: `Editar: ${props.usuario.nombres}`, href: '#' },
];

const isEmpleado = computed(() => props.usuario.establecimiento_id !== null)

const form = useForm({
    apellido_paterno: props.usuario.apellido_paterno || '',
    apellido_materno: props.usuario.apellido_materno || '',
    nombres: props.usuario.nombres || '',
    ci: props.usuario.ci || '',
    celular: props.usuario.celular || '',
    nacionalidad_id: props.usuario.nacionalidad_id,
    departamento_id: props.usuario.departamento_id,
    municipio_id: props.usuario.municipio_id,
    email: props.usuario.email || '',
    password: '',
    password_confirmation: '',
    // Si es empleado, calcula el valor de asignar_a, si no, déjalo vacío
    asignar_a: isEmpleado.value
        ? (props.usuario.sucursal_id ? `suc_${props.usuario.sucursal_id}` : `est_${props.usuario.establecimiento_id}`)
        : '',
    // Si no es empleado, asigna el primer rol que tenga, si no, déjalo vacío
    role_id: !isEmpleado.value && props.usuario.roles.length > 0 ? props.usuario.roles[0].id : '' as string | number,
});

const opcionesAsignacion = computed(() => {
    if (!props.establecimiento) return []

    const opciones = [
        {
            value: `est_${props.establecimiento.id_establecimiento}`,
            label: `Establecimiento: ${props.establecimiento.razon_social}`,
        },
    ]

    if (props.establecimiento.sucursales && props.establecimiento.sucursales.length > 0) {
        props.establecimiento.sucursales.forEach((sucursal) => {
            opciones.push({
                value: `suc_${sucursal.id_sucursal}`,
                label: `Sucursal: ${sucursal.nombre_sucursal}`,
            })
        })
    }

    return opciones
})

const isBoliviano = computed(() => {
  if (!form.nacionalidad_id) return false
  const nacionalidad = props.nacionalidades.find(n => n.id === form.nacionalidad_id)
  return nacionalidad?.pais === 'Bolivia'
})

const filteredMunicipios = computed(() => {
    if (!form.departamento_id) {
        return []
    }
    return props.municipios.filter(m => m.departamento_id === form.departamento_id)
})

const submit = () => {
  form.put(`/usuarios/${props.usuario.id}`);
};
</script>

<template>
    <Head title="Editar Usuario" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <form @submit.prevent="submit">
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Editar Usuario: {{ props.usuario.nombres }}</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <!-- Campos de Datos Personales -->
                        <div class="space-y-2">
                            <Label for="apellido_paterno">Apellido Paterno</Label>
                            <Input id="apellido_paterno" v-model="form.apellido_paterno" type="text" required />
                            <div v-if="form.errors.apellido_paterno" class="text-sm text-red-500">{{ form.errors.apellido_paterno }}</div>
                        </div>
                        <div class="space-y-2">
                            <Label for="apellido_materno">Apellido Materno</Label>
                            <Input id="apellido_materno" v-model="form.apellido_materno" type="text" />
                            <div v-if="form.errors.apellido_materno" class="text-sm text-red-500">{{ form.errors.apellido_materno }}</div>
                        </div>
                        <div class="space-y-2">
                            <Label for="nombres">Nombres</Label>
                            <Input id="nombres" v-model="form.nombres" type="text" required />
                            <div v-if="form.errors.nombres" class="text-sm text-red-500">{{ form.errors.nombres }}</div>
                        </div>
                        <div class="space-y-2">
                            <Label for="ci">CI</Label>
                            <Input id="ci" v-model="form.ci" type="text" required />
                            <div v-if="form.errors.ci" class="text-sm text-red-500">{{ form.errors.ci }}</div>
                        </div>
                        <div class="space-y-2">
                            <Label for="celular">Celular</Label>
                            <Input id="celular" v-model="form.celular" type="text" />
                            <div v-if="form.errors.celular" class="text-sm text-red-500">{{ form.errors.celular }}</div>
                        </div>
                        <div class="space-y-2">
                            <Label for="nacionalidad">Nacionalidad</Label>
                            <Select v-model="form.nacionalidad_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione nacionalidad" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="nac in nacionalidades" :key="nac.id" :value="nac.id">
                                        {{ nac.pais }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.nacionalidad_id" class="text-sm text-red-500">{{ form.errors.nacionalidad_id }}</div>
                        </div>

                        <!-- Campos de Ubicación (si es Boliviano) -->
                        <template v-if="isBoliviano">
                            <div class="space-y-2">
                                <Label for="departamento">Departamento</Label>
                                <Select v-model="form.departamento_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione departamento" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="dep in departamentos" :key="dep.id" :value="dep.id">
                                            {{ dep.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.departamento_id" class="text-sm text-red-500">{{ form.errors.departamento_id }}</div>
                            </div>
                            <div class="space-y-2">
                                <Label for="municipio">Municipio</Label>
                                <Select v-model="form.municipio_id" :disabled="!form.departamento_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione municipio" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="mun in filteredMunicipios" :key="mun.id" :value="mun.id">
                                            {{ mun.nombre_municipio }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.municipio_id" class="text-sm text-red-500">{{ form.errors.municipio_id }}</div>
                            </div>
                        </template>

                        <!-- Campos de Acceso y Rol/Asignación -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" required />
                            <div v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</div>
                        </div>

                        <!-- Lógica Condicional para Rol o Asignación -->
                        <div v-if="isEmpleado" class="space-y-2">
                            <Label for="asignar_a">Asignar a</Label>
                            <Select v-model="form.asignar_a">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione una asignación" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="opcion in opcionesAsignacion" :key="opcion.value" :value="opcion.value">
                                        {{ opcion.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.asignar_a" class="text-sm text-red-500">{{ form.errors.asignar_a }}</div>
                        </div>

                        <div v-else class="space-y-2">
                            <Label for="role">Rol</Label>
                            <Select v-model="form.role_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione un rol" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="rol in roles" :key="rol.id" :value="rol.id">
                                        {{ rol.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.role_id" class="text-sm text-red-500">{{ form.errors.role_id }}</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardFooter class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Actualizando...' : 'Actualizar Usuario' }}
                        </Button>
                        <Button as="a" href="/usuarios" variant="outline">Cancelar</Button>
                    </CardFooter>
                </Card>
            </div>
        </form>
    </AppLayout>
</template>
