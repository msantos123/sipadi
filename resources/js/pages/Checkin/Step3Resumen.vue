<script setup lang="ts">
import { useCheckinStore } from '@/stores/useCheckinStore'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import type { TipoCuarto } from '@/types'
import { computed } from 'vue'

const props = defineProps<{
  tipoCuartos: TipoCuarto[]
}>()

const checkinStore = useCheckinStore()

const getTipoCuartoNombre = (id: string | number) => {
  const tipo = props.tipoCuartos.find(t => String(t.id) === String(id))
  return tipo ? tipo.nombre : 'Desconocido'
}

const formattedFechaEntrada = computed(() => {
    if (!checkinStore.reserva.fecha_entrada) return 'No especificada'
    return new Date(checkinStore.reserva.fecha_entrada).toLocaleString('es-ES', { dateStyle: 'long', timeStyle: 'short' })
})

const formattedFechaSalida = computed(() => {
    if (!checkinStore.reserva.fecha_salida) return 'No especificada'
    return new Date(checkinStore.reserva.fecha_salida).toLocaleString('es-ES', { dateStyle: 'long', timeStyle: 'short' })
})

</script>

<template>
  <div>
    <h2 class="text-xl font-semibold mb-4">Paso 3: Resumen del Check-in</h2>

    <div v-if="checkinStore.grupos.length > 0">
      <!-- Card de Resumen General -->
      <Card class="mb-8 border-primary">
        <CardHeader>
          <CardTitle class="text-lg text-primary">Resumen de la Estancia</CardTitle>
        </CardHeader>
        <CardContent class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="font-semibold">Código de Reserva:</p>
            <p>{{ checkinStore.reserva.codigo_reserva || 'N/A' }}</p>
          </div>
          <div>
            <p class="font-semibold">Fecha y Hora de Entrada:</p>
            <p>{{ formattedFechaEntrada }}</p>
          </div>
          <div>
            <p class="font-semibold">Fecha y Hora de Salida:</p>
            <p>{{ formattedFechaSalida }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Sección de Grupos -->
      <div class="space-y-6">
        <h3 class="text-lg font-medium">Grupos y Habitaciones</h3>
        <Card v-for="grupo in checkinStore.grupos" :key="grupo.id" class="bg-gray-50/50">
          <CardHeader>
            <CardTitle class="flex justify-between items-center">
              <span>Habitación: {{ grupo.nro_cuarto }}</span>
              <Badge variant="secondary">{{ getTipoCuartoNombre(grupo.tipo_cuarto_id) }}</Badge>
            </CardTitle>
            <CardDescription>
              <span class="font-semibold">Titular:</span> {{ grupo.titular.nombres }} {{ grupo.titular.apellido_paterno }} ({{ grupo.titular.nro_documento }})
            </CardDescription>
          </CardHeader>
          <CardContent>
            <h4 class="font-semibold mb-2 text-sm">Acompañantes:</h4>
            <ul v-if="grupo.dependientes.length > 0" class="space-y-2 text-sm">
              <li v-for="dep in grupo.dependientes" :key="dep.id" class="flex items-center justify-between p-2 rounded-md bg-white">
                <span>{{ dep.nombres }} {{ dep.apellido_paterno }}</span>
                <Badge variant="outline" class="capitalize">{{ dep.parentesco }}</Badge>
              </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">No hay acompañantes en este grupo.</p>
          </CardContent>
        </Card>
      </div>

    </div>

    <!-- Mensaje si no hay grupos -->
    <div v-else>
        <Card>
            <CardHeader>
                <CardTitle>Resumen no disponible</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-red-600">No se han agregado grupos a este check-in. Por favor, regresa al paso anterior para continuar.</p>
            </CardContent>
        </Card>
    </div>
  </div>
</template>
