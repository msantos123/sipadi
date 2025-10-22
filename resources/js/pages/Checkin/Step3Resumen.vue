<script setup lang="ts">
import { useCheckinStore } from '@/stores/useCheckinStore'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const checkinStore = useCheckinStore()
</script>

<template>
  <div>
    <div v-if="checkinStore.titular">
      <!-- Card del Titular -->
      <Card class="mb-8 border-primary">
        <CardHeader>
          <CardTitle class="text-lg text-primary">Huésped Titular</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm">
          <p><strong>Nombre Completo:</strong> {{ checkinStore.titular.nombres }} {{ checkinStore.titular.apellido_paterno }} {{ checkinStore.titular.apellido_materno }}</p>
          <p><strong>Nro. Documento:</strong> {{ checkinStore.titular.nro_documento }}</p>
        </CardContent>
      </Card>

      <!-- Sección de Acompañantes -->
      <div>
        <h2 class="text-xl font-semibold mb-4">Acompañantes</h2>
        <div v-if="checkinStore.dependientes.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Card v-for="acompanante in checkinStore.dependientes" :key="acompanante.id">
            <CardHeader>
              <!-- El parentesco se muestra como título, con mayúscula inicial -->
              <CardTitle class="text-md capitalize">
                {{ acompanante.parentesco || 'Acompañante' }}
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-1 text-sm">
              <p><strong>Nombre:</strong> {{ acompanante.nombres }} {{ acompanante.apellido_paterno }}</p>
              <p><strong>Nro. Documento:</strong> {{ acompanante.nro_documento }}</p>
            </CardContent>
          </Card>
        </div>
        <div v-else>
          <p class="text-sm text-muted-foreground">No se han agregado acompañantes para este check-in.</p>
        </div>
      </div>

    </div>

    <!-- Mensaje si no hay titular -->
    <div v-else>
        <Card>
            <CardHeader>
                <CardTitle>Resumen no disponible</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-red-600">No se ha seleccionado un huésped titular. Por favor, regresa al paso anterior para continuar.</p>
            </CardContent>
        </Card>
    </div>
  </div>
</template>
