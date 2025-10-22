<script setup lang="ts">
import { ref, watch, defineExpose } from 'vue'
import { useCheckinStore } from '@/stores/useCheckinStore'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

// Usamos el store de Pinia como única fuente de verdad
const checkinStore = useCheckinStore()

// Objeto reactivo para mantener los errores de validación
const errors = ref<{ [key: string]: string }>({})

// Observador para depuración: Muestra el estado de la reserva en la consola cuando cambia
watch(() => checkinStore.reserva, (newVal) => {
  console.log('Datos de la reserva en Pinia:', newVal)
}, { deep: true })

// Función de validación
const validate = (): boolean => {
  errors.value = {} // Limpiamos errores anteriores

  if (!checkinStore.reserva.fecha_entrada) {
    errors.value.fecha_entrada = 'La fecha de entrada es obligatoria.'
  }
  if (!checkinStore.reserva.fecha_salida) {
    errors.value.fecha_salida = 'La fecha de salida es obligatoria.'
  }
  if (checkinStore.reserva.fecha_entrada && checkinStore.reserva.fecha_salida) {
    if (new Date(checkinStore.reserva.fecha_salida) < new Date(checkinStore.reserva.fecha_entrada)) {
      errors.value.fecha_salida = 'La fecha de salida no puede ser anterior a la de entrada.'
    }
  }
  if (!checkinStore.reserva.nro_cuarto) {
    errors.value.nro_cuarto = 'El número de cuarto es obligatorio.'
  }
  // Puedes añadir más validaciones aquí (ej. establecimiento_id)

  return Object.keys(errors.value).length === 0
}

// Exponemos la función `validate` para que el componente padre pueda llamarla
defineExpose({
  validate,
})
</script>

<template>
  <div>
    <h2 class="text-xl font-semibold mb-4">Paso 1: Datos de la Reserva</h2>
    <Card>
      <CardHeader>
        <CardTitle>Información de la Estancia</CardTitle>
      </CardHeader>
      <CardContent class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="space-y-2">
          <Label for="codigo_reserva">Código de Reserva (Opcional)</Label>
          <Input id="codigo_reserva" v-model="checkinStore.reserva.codigo_reserva" type="text" />
        </div>
        <div class="space-y-2">
          <Label for="fecha_entrada">Fecha de Entrada</Label>
          <Input id="fecha_entrada" v-model="checkinStore.reserva.fecha_entrada" type="date" />
          <p v-if="errors.fecha_entrada" class="text-sm text-red-600 mt-1">{{ errors.fecha_entrada }}</p>
        </div>
        <div class="space-y-2">
          <Label for="fecha_salida">Fecha de Salida</Label>
          <Input id="fecha_salida" v-model="checkinStore.reserva.fecha_salida" type="date" />
          <p v-if="errors.fecha_salida" class="text-sm text-red-600 mt-1">{{ errors.fecha_salida }}</p>
        </div>
        <div class="space-y-2">
          <Label for="establecimiento_id">ID Establecimiento</Label>
          <Input id="establecimiento_id" v-model.number="checkinStore.reserva.establecimiento_id" type="number" />
           <!-- Aquí podrías tener un Select en lugar de un Input -->
        </div>
        <div class="space-y-2">
          <Label for="nro_cuarto">Número de Cuarto</Label>
          <Input id="nro_cuarto" v-model="checkinStore.reserva.nro_cuarto" type="text" />
           <p v-if="errors.nro_cuarto" class="text-sm text-red-600 mt-1">{{ errors.nro_cuarto }}</p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
