
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import type { Persona, Nacionalidad, Municipio, Departamento } from '@/types'
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import {
  Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  persona?: Persona
  nacionalidades: Nacionalidad[]
  departamentos: Departamento[]
  municipios: Municipio[]
}>()

const emit = defineEmits<{
  (e: 'persona-saved', persona: Persona): void
  (e: 'cancel'): void
}>()

const form = useForm({
  nombres: props.persona?.nombres ?? '',
  apellido_paterno: props.persona?.apellido_paterno ?? '',
  apellido_materno: props.persona?.apellido_materno ?? '',
  tipo_documento: props.persona?.tipo_documento ?? '',
  nro_documento: props.persona?.nro_documento ?? '',
  complemento: props.persona?.complemento ?? '',
  fecha_nacimiento: props.persona?.fecha_nacimiento ?? '',
  nacionalidad_id: props.persona?.nacionalidad_id ?? null,
  departamento_id: props.persona?.departamento_id ?? null,
  municipio_id: props.persona?.municipio_id ?? null,
  ciudad_origen: props.persona?.ciudad_origen ?? '',
  sexo: props.persona?.sexo ?? '',
  estado_civil: props.persona?.estado_civil ?? '',
  ocupacion: props.persona?.ocupacion ?? '',
})

const filteredMunicipios = computed(() => {
  if (!form.departamento_id) return []
  return props.municipios.filter(m => m.departamento_id === form.departamento_id)
})

// Validación para campos que solo aceptan números y letras (sin espacios ni caracteres especiales)
const sanitizeAlphanumeric = (value: string) => {
  return value.replace(/[^0-9A-Za-z]/g, '').toUpperCase()
}

// Validación para campos que solo aceptan letras, espacios y acentos
const sanitizeLettersOnly = (value: string) => {
  return value.replace(/[^A-Za-zÀ-ÿ\u00f1\u00d1\s]/g, '').toUpperCase()
}

type AlphanumericField = 'nro_documento' | 'complemento'
type LettersOnlyField = 'nombres' | 'apellido_paterno' | 'apellido_materno' | 'ciudad_origen' | 'ocupacion'

// Handler para campos alfanuméricos (documento, complemento)
const handleAlphanumericInput = (field: AlphanumericField, event: Event) => {
  const target = event.target as HTMLInputElement | null
  if (!target) return
  const sanitized = sanitizeAlphanumeric(target.value)
  form[field] = sanitized
  // Actualizar el valor del input para reflejar el cambio inmediatamente
  target.value = sanitized
}

// Handler para campos de solo letras (nombres, apellidos, ciudad, ocupación)
const handleLettersOnlyInput = (field: LettersOnlyField, event: Event) => {
  const target = event.target as HTMLInputElement | null
  if (!target) return
  const sanitized = sanitizeLettersOnly(target.value)
  form[field] = sanitized
  // Actualizar el valor del input para reflejar el cambio inmediatamente
  target.value = sanitized
}

const nacionalidadSearch = ref('')
const showNacionalidadList = ref(false)
const nacionalidadSearchModel = computed({
  get: () => nacionalidadSearch.value,
  set: (value: string) => {
    // Para búsqueda de nacionalidad, permitir letras y espacios
    const sanitized = value.replace(/[^A-Za-zÀ-ÿ\u00f1\u00d1\s]/g, '')
    nacionalidadSearch.value = sanitized
    showNacionalidadList.value = true
    if (form.nacionalidad_id) {
      form.nacionalidad_id = null
    }
  },
})

const normalizeText = (value: string) => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
const filteredNacionalidades = computed(() => {
  if (!nacionalidadSearch.value) return props.nacionalidades
  const term = normalizeText(nacionalidadSearch.value)
  return props.nacionalidades.filter((nac) => {
    const searchable = normalizeText(`${nac.pais} ${nac.gentilicio}`)
    return searchable.includes(term)
  })
})

const formatNacionalidad = (nac: Nacionalidad) => `${nac.pais} - ${nac.gentilicio}`

const selectNacionalidad = (nac: Nacionalidad) => {
  form.nacionalidad_id = nac.id
  nacionalidadSearch.value = formatNacionalidad(nac)
  showNacionalidadList.value = false
}

const handleNacionalidadFocus = () => {
  showNacionalidadList.value = true
}

const handleNacionalidadBlur = () => {
  setTimeout(() => {
    showNacionalidadList.value = false
  }, 150)
}

const isBoliviano = computed(() => {
  if (!form.nacionalidad_id) return false
  const nacionalidad = props.nacionalidades.find(n => n.id === form.nacionalidad_id)
  return nacionalidad?.pais === 'Bolivia'
})

watch(() => form.nacionalidad_id, (newVal) => {
  if (isBoliviano.value) {
    form.ciudad_origen = ''
  } else {
    form.departamento_id = null
    form.municipio_id = null
  }
})

watch(() => form.nacionalidad_id, (newVal) => {
  if (!newVal) return
  const selected = props.nacionalidades.find(n => n.id === newVal)
  if (selected) {
    nacionalidadSearch.value = formatNacionalidad(selected)
  }
}, { immediate: true })

const edad = computed(() => {
  if (!form.fecha_nacimiento) return null
  const birthDate = new Date(form.fecha_nacimiento)
  if (isNaN(birthDate.getTime())) return null

  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const m = today.getMonth() - birthDate.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
    age--
  }
  return age
})

const submit = () => {
  const url = props.persona ? `/personas/${props.persona.id}` : '/personas'
  const method = props.persona ? 'put' : 'post'

  form[method](url, {
    onSuccess: (page) => {
      emit('persona-saved', page.props.persona as Persona)
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit" >
    <Card>
      <CardHeader>
        <CardTitle>{{ persona ? 'Editar Persona' : 'Registrar Nueva Persona' }}</CardTitle>
      </CardHeader>
      <CardContent class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="space-y-2">
          <Label for="documento">Documento</Label>
          <Input 
            id="documento" 
            v-model="form.nro_documento" 
            type="text" 
            placeholder="Ej: 12345678" 
            @input="handleAlphanumericInput('nro_documento', $event)" 
          />
          <InputError class="mt-2" :message="form.errors.nro_documento" />
        </div>
        <div class="space-y-2">
          <Label for="complemento">Complemento</Label>
          <Input 
            id="complemento" 
            v-model="form.complemento" 
            type="text" 
            placeholder="Ej: 1A" 
            @input="handleAlphanumericInput('complemento', $event)" 
          />
          <InputError class="mt-2" :message="form.errors.complemento" />
        </div>
        <div class="space-y-2">
          <Label for="tipo_documento">Tipo de Documento</Label>
          <Select v-model="form.tipo_documento">
            <SelectTrigger>
              <SelectValue placeholder="Seleccione Documento" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="ci">
                Carnet de Identidad
              </SelectItem>
              <SelectItem value="pasaporte">
                Pasaporte
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="nombres">Nombres</Label>
          <Input 
            id="nombres" 
            v-model="form.nombres" 
            type="text" 
            placeholder="Ej: JUAN CARLOS" 
            @input="handleLettersOnlyInput('nombres', $event)" 
          />
          <InputError class="mt-2" :message="form.errors.nombres" />
        </div>
        <div class="space-y-2">
          <Label for="apellido_paterno">Primer Apellido</Label>
          <Input 
            id="apellido_paterno" 
            v-model="form.apellido_paterno" 
            type="text" 
            placeholder="Ej: PÉREZ" 
            @input="handleLettersOnlyInput('apellido_paterno', $event)" 
          />
          <InputError class="mt-2" :message="form.errors.apellido_paterno" />
        </div>
        <div class="space-y-2">
          <Label for="apellido_materno">Segundo Apellido</Label>
          <Input 
            id="apellido_materno" 
            v-model="form.apellido_materno" 
            type="text" 
            placeholder="Ej: GARCÍA" 
            @input="handleLettersOnlyInput('apellido_materno', $event)" 
          />
          <InputError class="mt-2" :message="form.errors.apellido_materno" />
        </div>
        <div class="space-y-2 relative">
          <Label for="nacionalidad">Nacionalidad</Label>
          <div class="relative">
            <Input
              id="nacionalidad"
              v-model="nacionalidadSearchModel"
              type="text"
              placeholder="Buscar nacionalidad..."
              autocomplete="off"
              @focus="handleNacionalidadFocus"
              @blur="handleNacionalidadBlur"
            />
            <div
              v-if="showNacionalidadList"
              class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow"
            >
              <button
                v-for="nac in filteredNacionalidades"
                :key="nac.id"
                type="button"
                class="flex w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
                @mousedown.prevent="selectNacionalidad(nac)"
              >
                {{ formatNacionalidad(nac) }}
              </button>
              <div v-if="!filteredNacionalidades.length" class="px-3 py-2 text-sm text-gray-500">
                No se encontraron resultados
              </div>
            </div>
          </div>
          <InputError class="mt-2" :message="form.errors.nacionalidad_id" />
        </div>
        <div v-if="!isBoliviano" class="space-y-2">
          <Label for="ciudad_origen">Ciudad de Origen</Label>
          <Input 
            id="ciudad_origen" 
            v-model="form.ciudad_origen" 
            type="text" 
            placeholder="Ej: SÃO PAULO" 
            @input="handleLettersOnlyInput('ciudad_origen', $event)" 
          />
          <InputError class="mt-2" :message="form.errors.ciudad_origen" />
        </div>
        <div v-if="isBoliviano" class="space-y-2">
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
        </div>
        <div v-if="isBoliviano" class="space-y-2">
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
        </div>
        <div class="space-y-2">
          <Label for="fecha_nacimiento">Fecha de Nacimiento</Label>
          <Input id="fecha_nacimiento" v-model="form.fecha_nacimiento" type="date" />
          <InputError class="mt-2" :message="form.errors.fecha_nacimiento" />
        </div>
        <div class="space-y-2">
          <Label for="edad">Edad</Label>
          <Input id="edad" :value="edad" type="number" disabled />
        </div>
        <div class="space-y-2">
          <Label for="sexo">Sexo</Label>
          <Select v-model="form.sexo">
            <SelectTrigger>
              <SelectValue placeholder="Seleccione Sexo" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="M">
                Masculino
              </SelectItem>
              <SelectItem value="F">
                Femenino
              </SelectItem>
              <SelectItem value="O">
                Otro
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-2">
          <Label for="estado_civil">Estado Civil</Label>
          <Select v-model="form.estado_civil">
            <SelectTrigger>
              <SelectValue placeholder="Seleccione Estado Civil" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="soltero">
                Soltero(a)
              </SelectItem>
              <SelectItem value="casado">
                Casado(a)
              </SelectItem>
              <SelectItem value="divorciado">
                Divorciado(a)
              </SelectItem>
              <SelectItem value="viudo">
                Viudo()
              </SelectItem>
              <SelectItem value="union_libre">
                Union Libre
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-2">
          <Label for="ocupacion">Ocupación</Label>
          <Input 
            id="ocupacion" 
            v-model="form.ocupacion" 
            type="text" 
            placeholder="Ej: INGENIERO CIVIL" 
            @input="handleLettersOnlyInput('ocupacion', $event)" 
          />
          <InputError class="mt-2" :message="form.errors.ocupacion" />
        </div>

      </CardContent>
      <CardFooter>
        <Button type="button" variant="outline" @click="emit('cancel')">
            Cancelar
        </Button>
        <Button type="submit" :disabled="form.processing" class="bg-indigo-500 text-white hover:bg-indigo-700">
          {{ form.processing ? 'Guardando...' : 'Guardar Persona' }}
        </Button>
      </CardFooter>
    </Card>
  </form>
</template>
