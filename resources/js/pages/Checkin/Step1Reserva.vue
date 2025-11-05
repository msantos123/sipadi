<script setup lang="ts">
import { ref, watch, defineExpose, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useCheckinStore } from '@/stores/useCheckinStore'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const page = usePage()

// Usamos el store de Pinia como única fuente de verdad
const checkinStore = useCheckinStore()

// Refs para mostrar los datos del establecimiento
const codigoReserva = ref('')
const razonSocial = ref('')
const ciudad = ref('')
const direccion = ref('')

// Objeto reactivo para mantener los errores de validación
const errors = ref<{ [key: string]: string }>({})

onMounted(() => {
  const user = page.props.auth?.user
  if (user) {
    const est = user.establecimiento
    const suc = user.sucursal
    console.log(est);
    // Siempre se necesita el establecimiento para el código de reserva
    if (est) {
      checkinStore.reserva.establecimiento_id = est.id_establecimiento
      checkinStore.reserva.departamento_id = est.id_departamento
      const timestamp = Date.now().toString().slice(-5)
      const generatedCode = `${est.codigo}/${timestamp}`
      codigoReserva.value = generatedCode
      checkinStore.reserva.codigo_reserva = generatedCode
    }

    // Si el usuario tiene una sucursal, usamos sus datos para la UI
    if (suc) {
      checkinStore.reserva.sucursal_id = suc.id_sucursal
      razonSocial.value = suc.nombre_sucursal
      ciudad.value = suc.ciudad || 'No especificada'
      direccion.value = suc.direccion_sucursal || 'No especificada'
    }
    else if (est) {
      // Si no hay sucursal, usamos los datos del establecimiento
      razonSocial.value = est.razon_social
      ciudad.value = est.ciudad || 'No especificada'
      direccion.value = est.direccion_establecimiento || 'No especificada'
    }
  }

  // Asignar la fecha y hora actual a la fecha de entrada
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  checkinStore.reserva.fecha_entrada = now.toISOString().slice(0, 16)
})

// Observador para depuración: Muestra el estado de la reserva en la consola cuando cambia
watch(() => checkinStore.reserva, (newVal) => {
  console.log('Datos de la reserva en Pinia:', newVal)
}, { deep: true })

// Función de validación
const validate = (): boolean => {
  errors.value = {} // Limpiamos errores anteriores

  if (!checkinStore.reserva.fecha_entrada) {
    errors.value.fecha_entrada = 'La fecha y hora de entrada es obligatoria.'
  }
  if (!checkinStore.reserva.fecha_salida) {
    errors.value.fecha_salida = 'La fecha y hora de salida es obligatoria.'
  }
  if (checkinStore.reserva.fecha_entrada && checkinStore.reserva.fecha_salida) {
    if (new Date(checkinStore.reserva.fecha_salida) <= new Date(checkinStore.reserva.fecha_entrada)) {
      errors.value.fecha_salida = 'La fecha de salida debe ser posterior a la de entrada.'
    }
  }

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
        <CardTitle>Información del Establecimiento</CardTitle>
      </CardHeader>
      <CardContent class="grid grid-cols-3 gap-6 md:grid-cols-2">
        <!-- Columna de Información del Establecimiento -->
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="codigo_reserva">Código de Reserva</Label>
            <Input id="codigo_reserva" v-model="codigoReserva" type="text" disabled />
          </div>
          <div class="space-y-2">
            <Label for="razon_social">Razón Social</Label>
            <Input id="razon_social" v-model="razonSocial" type="text" disabled />
          </div>
          <div class="space-y-2">
            <Label for="ciudad">Ciudad</Label>
            <Input id="ciudad" v-model="ciudad" type="text" disabled />
          </div>
          <div class="space-y-2">
            <Label for="direccion">Dirección</Label>
            <Input id="direccion" v-model="direccion" type="text" disabled />
          </div>
        </div>

        <!-- Columna de Fechas de la Estancia -->
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="fecha_entrada">Fecha y Hora de Entrada</Label>
            <Input id="fecha_entrada" v-model="checkinStore.reserva.fecha_entrada" type="datetime-local" disabled />
            <p v-if="errors.fecha_entrada" class="text-sm text-red-600 mt-1">{{ errors.fecha_entrada }}</p>
          </div>
          <div class="space-y-2">
            <Label for="fecha_salida">Fecha y Hora de Salida</Label>
            <Input id="fecha_salida" v-model="checkinStore.reserva.fecha_salida" type="datetime-local" />
            <p v-if="errors.fecha_salida" class="text-sm text-red-600 mt-1">{{ errors.fecha_salida }}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
