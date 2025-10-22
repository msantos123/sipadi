<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { PropType } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Loader2 } from 'lucide-vue-next'

// Definición de la interfaz de Persona para tipado
interface Persona {
  id?: number
  nombres: string
  apellido_paterno: string
  apellido_materno?: string | null
  tipo_documento: string
  nro_documento: string
  complemento?: string | null
  fecha_nacimiento: string | null
  nacionalidad_id: number | null
  departamento_id?: number | null
  municipio_id?: number | null
  ciudad_origen?: string | null
  sexo: string | null
  estado_civil: string | null
  ocupacion?: string | null
}

// Definición de las props del componente
const props = defineProps({
  persona: {
    type: Object as PropType<Persona | null>,
    default: () => null,
  },
  nacionalidades: {
    type: Array as PropType<{ id: number; pais: string; gentilicio: string }[]>,
    required: true,
  },
  departamentos: {
    type: Array as PropType<{ id: number; nombre: string }[]>,
    required: true,
  },
  municipios: {
    type: Array as PropType<{ id: number; nombre_municipio: string; departamento_id: number }[]>,
    required: true,
  },
})

// Emits para comunicar eventos al componente padre
const emit = defineEmits(['save', 'cancel'])

// Estado del formulario
const form = ref<Persona>({
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  tipo_documento: 'CI',
  nro_documento: '',
  complemento: '',
  fecha_nacimiento: null,
  nacionalidad_id: null,
  departamento_id: null,
  municipio_id: null,
  ciudad_origen: '',
  sexo: null,
  estado_civil: null,
  ocupacion: '',
})

const isLoading = ref(false)
const errors = ref<Record<string, string>>({})

// Observa cambios en la prop `persona` para pre-llenar el formulario
watch(
  () => props.persona,
  (newPersona) => {
    if (newPersona) {
      form.value = { ...newPersona }
    } else {
      // Resetea el formulario si no hay persona
      form.value = {
        nombres: '',
        apellido_paterno: '',
        apellido_materno: '',
        tipo_documento: 'CI',
        nro_documento: '',
        complemento: '',
        fecha_nacimiento: null,
        nacionalidad_id: null,
        departamento_id: null,
        municipio_id: null,
        ciudad_origen: '',
        sexo: null,
        estado_civil: null,
        ocupacion: '',
      }
    }
  },
  { immediate: true, deep: true }
)

// Lógica para saber si la nacionalidad es Boliviana (ID 24)
const isBolivian = computed(() => form.value.nacionalidad_id === 24)

// Filtra los municipios según el departamento seleccionado
const filteredMunicipios = computed(() => {
  if (!form.value.departamento_id) return []
  return props.municipios.filter(
    (m) => m.departamento_id === form.value.departamento_id
  )
})

// Maneja el envío del formulario
async function handleSubmit() {
  isLoading.value = true
  errors.value = {}

  try {
    let response
    if (form.value.id) {
      // Actualización (PUT)
      response = await axios.put(`/personas/${form.value.id}`, form.value)
    } else {
      // Creación (POST)
      response = await axios.post('/personas', form.value)
    }

    // Si la operación es exitosa, emite el evento 'save' con los datos
    if (response.data) {
      emit('save', response.data)
    }
  } catch (error: any) {
    // Manejo de errores de validación de Laravel
    if (error.response && error.response.status === 422) {
      const validationErrors = error.response.data.errors
      const formattedErrors: Record<string, string> = {}
      for (const key in validationErrors) {
        formattedErrors[key] = validationErrors[key][0]
      }
      errors.value = formattedErrors
    } else {
      console.error('Error al guardar la persona:', error)
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

      <!-- Campos del formulario -->
       <div class="space-y-2">
        <Label for="nro_documento">Nro. de Documento</Label>
        <Input id="nro_documento" v-model="form.nro_documento" />
        <p v-if="errors.nro_documento" class="text-sm text-red-500">{{ errors.nro_documento }}</p>
      </div>

      <div class="space-y-2">
        <Label for="complemento">Complemento</Label>
        <Input id="complemento" :model="form.complemento" />
      </div>

      <div class="space-y-2">
        <Label for="tipo_documento">Tipo de Documento</Label>
        <Select v-model="form.tipo_documento">
          <SelectTrigger>
            <SelectValue placeholder="Seleccione Documento" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="ci">Carnet de Identidad</SelectItem>
            <SelectItem value="pasaporte">Pasaporte</SelectItem>
          </SelectContent>
        </Select>
        <p v-if="errors.tipo_documento" class="text-sm text-red-500">{{ errors.tipo_documento }}</p>
      </div>

      <div class="space-y-2">
        <Label for="nombres">Nombres</Label>
        <Input id="nombres" v-model="form.nombres" />
        <p v-if="errors.nombres" class="text-sm text-red-500">{{ errors.nombres }}</p>
      </div>

      <div class="space-y-2">
        <Label for="apellido_paterno">Apellido Paterno</Label>
        <Input id="apellido_paterno" v-model="form.apellido_paterno" />
        <p v-if="errors.apellido_paterno" class="text-sm text-red-500">{{ errors.apellido_paterno }}</p>
      </div>

      <div class="space-y-2">
        <Label for="apellido_materno">Apellido Materno</Label>
        <Input id="apellido_materno" :model="form.apellido_materno" />
      </div>

      <div class="space-y-2">
        <Label for="nacionalidad">Nacionalidad</Label>
        <Select v-model="form.nacionalidad_id">
          <SelectTrigger>
            <SelectValue placeholder="Seleccione..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="nac in nacionalidades"
              :key="nac.id"
              :value="nac.id"
            >
              {{ nac.pais }} - {{ nac.gentilicio }}
            </SelectItem>
          </SelectContent>
        </Select>
        <p v-if="errors.nacionalidad_id" class="text-sm text-red-500">{{ errors.nacionalidad_id }}</p>
      </div>




      <div class="space-y-2">
        <Label for="fecha_nacimiento">Fecha de Nacimiento</Label>
        <Input id="fecha_nacimiento" type="date" :model="form.fecha_nacimiento" />
        <p v-if="errors.fecha_nacimiento" class="text-sm text-red-500">{{ errors.fecha_nacimiento }}</p>
      </div>

      <div class="space-y-2">
        <Label for="sexo">Sexo</Label>
        <Select v-model="form.sexo">
          <SelectTrigger>
            <SelectValue placeholder="Seleccione..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="M">Masculino</SelectItem>
            <SelectItem value="F">Femenino</SelectItem>
            <SelectItem value="O">Otro</SelectItem>
          </SelectContent>
        </Select>
        <p v-if="errors.sexo" class="text-sm text-red-500">{{ errors.sexo }}</p>
      </div>

      <div class="space-y-2">
        <Label for="estado_civil">Estado Civil</Label>
        <Input id="estado_civil" :model="form.estado_civil" />
        <p v-if="errors.estado_civil" class="text-sm text-red-500">{{ errors.estado_civil }}</p>
      </div>



      <!-- Campos condicionales para Bolivia -->
      <template v-if="isBolivian">
        <div class="space-y-2">
          <Label for="departamento">Departamento</Label>
          <Select v-model="form.departamento_id">
            <SelectTrigger>
              <SelectValue placeholder="Seleccione..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="dep in departamentos"
                :key="dep.id"
                :value="dep.id"
              >
                {{ dep.nombre }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="errors.departamento_id" class="text-sm text-red-500">{{ errors.departamento_id }}</p>
        </div>

        <div class="space-y-2">
          <Label for="municipio">Municipio</Label>
          <Select v-model="form.municipio_id" :disabled="!form.departamento_id">
            <SelectTrigger>
              <SelectValue placeholder="Seleccione..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="mun in filteredMunicipios"
                :key="mun.id"
                :value="mun.id"
              >
                {{ mun.nombre_municipio }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="errors.municipio_id" class="text-sm text-red-500">{{ errors.municipio_id }}</p>
        </div>
      </template>

      <!-- Campo para extranjeros -->
      <template v-else>
        <div class="space-y-2">
          <Label for="ciudad_origen">Ciudad de Origen</Label>
          <Input id="ciudad_origen" :model="form.ciudad_origen" />
          <p v-if="errors.ciudad_origen" class="text-sm text-red-500">{{ errors.ciudad_origen }}</p>
        </div>
      </template>

      <div class="space-y-2">
        <Label for="ocupacion">Ocupación</Label>
        <Input id="ocupacion" :model="form.ocupacion" />
      </div>
    </div>

    <!-- Botones de acción -->
    <div class="flex justify-end gap-4 mt-6">
      <Button type="button" variant="outline" @click="emit('cancel')">
        Cancelar
      </Button>
      <Button type="submit" :disabled="isLoading">
        <Loader2 v-if="isLoading" class="w-4 h-4 mr-2 animate-spin" />
        {{ isLoading ? 'Guardando...' : 'Guardar' }}
      </Button>
    </div>
  </form>
</template>
