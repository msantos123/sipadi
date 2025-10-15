
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
import { Switch } from '@/components/ui/switch'
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  persona?: Persona
  nacionalidades: Nacionalidad[]
  departamentos: Departamento[]
  municipios: Municipio[]
}>()

const emit = defineEmits<{(e: 'persona-saved', persona: Persona): void }>()

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
        <CardTitle>Registrar Estadia</CardTitle>
      </CardHeader>
      <CardContent class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="space-y-2">
          <Label for="nr">Nro de Cuarto</Label>
          <Input id="documento" v-model="form.nro_documento" type="text" />
          <div v-if="form.errors.nro_documento">{{ form.errors.nro_documento }}</div>
        </div>
        <div class="space-y-2">
          <Label for="fecha_nacimiento">Fecha de Ingreso</Label>
          <Input id="fecha_nacimiento" v-model="form.fecha_nacimiento" type="date" />
          <div v-if="form.errors.fecha_nacimiento">{{ form.errors.fecha_nacimiento }}</div>
        </div>
        <div class="space-y-2">
          <Label for="fecha_nacimiento">Fecha de Salida</Label>
          <Input id="fecha_nacimiento" v-model="form.fecha_nacimiento" type="date" />
          <div v-if="form.errors.fecha_nacimiento">{{ form.errors.fecha_nacimiento }}</div>
        </div>
        <div class="space-y-2">
            <Label for="documento">Titular sin Dependientes</Label>
            <div class="flex items-center space-x-2">
                <Switch id="airplane-mode" />
                <Label for="airplane-mode">Es titular?</Label>
            </div>
        </div>
        <div class="space-y-2">
          <Label for="tipo_documento">Parentesco</Label>
          <Select v-model="form.tipo_documento">
            <SelectTrigger>
              <SelectValue placeholder="Seleccione Parestesco" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="hijo">
                Hijo(a)
              </SelectItem>
              <SelectItem value="sobrino">
                Sobrino(a)
              </SelectItem>
              <SelectItem value="nieto">
                Nieto(a)
              </SelectItem>
              <SelectItem value="apoderado">
                Apoderado
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardContent>
      <CardFooter>
        <Button type="submit" :disabled="form.processing" class="bg-indigo-500 text-white hover:bg-indigo-700">
          {{ form.processing ? 'Guardando...' : 'Guardar Estadia' }}
        </Button>
      </CardFooter>
    </Card>
  </form>
</template>
