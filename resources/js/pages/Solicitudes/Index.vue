<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import type { PropType } from 'vue'
import Heading from '@/components/Heading.vue'
import { format } from 'date-fns'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Link, router } from '@inertiajs/vue3'

interface Solicitud {
  id: number
  persona_buscada_nombre: string
  persona_buscada_identificacion: string
  fecha_solicitud: string
  usuario_creador: {
    nombres: string
  }
  detalles_orden_judicial: any
  detalles_orden_oficial: any
  detalles_requerimiento_fiscal: any
  resultado_busqueda: string | null
}

const props = defineProps({
  solicitudes: {
    type: Array as PropType<Solicitud[]>,
    required: true,
  },
})

const formattedSolicitudes = computed(() => {
  return props.solicitudes.map(s => {
    let tipo = 'N/A'
    let detalles: any = {}

    if (s.detalles_orden_judicial) {
      tipo = 'Orden Judicial'
      detalles = {
        'Juzgado/Tribunal': s.detalles_orden_judicial.nombre_juzgado_tribunal,
        'Nro. Orden': s.detalles_orden_judicial.numero_orden_judicial,
      }
    } else if (s.detalles_orden_oficial) {
      tipo = 'Orden Oficial'
      detalles = {
        Institución: s.detalles_orden_oficial.institucion,
      }
    } else if (s.detalles_requerimiento_fiscal) {
      tipo = 'Requerimiento Fiscal'
      detalles = {
        'Fiscal': s.detalles_requerimiento_fiscal.fiscal_apellidos_nombres,
        'Materia': s.detalles_requerimiento_fiscal.fiscal_de_materia,
        'Nro. Caso': s.detalles_requerimiento_fiscal.numero_de_caso,
      }
    }

    return {
      ...s,
      fecha_solicitud: format(new Date(s.fecha_solicitud), 'dd/MM/yyyy'),
      tipo,
      detalles,
      hasResult: s.resultado_busqueda && s.resultado_busqueda.length > 2, // >2 para '[]'
    }
  })
})

const downloadReport = (solicitudId: number) => {
  window.open(`/solicitudes/${solicitudId}/download`, '_blank')
}
</script>

<template>
  <AppLayout>
    <div class="p-4 md:p-8">
      <div class="flex justify-between items-center mb-4">
        <Heading title="Gestión de Solicitudes" />
        <Link href="/solicitud/create">
          <Button>Crear Solicitud</Button>
        </Link>
      </div>

      <div class="mt-8 border rounded-lg">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Persona Buscada</TableHead>
              <TableHead>Fecha Solicitud</TableHead>
              <TableHead>Tipo</TableHead>
              <TableHead>Detalles</TableHead>
              <TableHead>Creado por</TableHead>
              <TableHead class="text-right">
                Acciones
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="formattedSolicitudes.length > 0">
              <TableRow v-for="solicitud in formattedSolicitudes" :key="solicitud.id">
                <TableCell>{{ solicitud.id }}</TableCell>
                <TableCell>
                  <div>{{ solicitud.persona_buscada_nombre }}</div>
                  <div class="text-sm text-muted-foreground">
                    {{ solicitud.persona_buscada_identificacion }}
                  </div>
                </TableCell>
                <TableCell>{{ solicitud.fecha_solicitud }}</TableCell>
                <TableCell>{{ solicitud.tipo }}</TableCell>
                <TableCell>
                  <div v-for="(value, key) in solicitud.detalles" :key="key" class="text-sm">
                    <span class="font-semibold">{{ key }}:</span> {{ value }}
                  </div>
                </TableCell>

                <TableCell>{{ solicitud.usuario_creador.nombres }}</TableCell>
                <TableCell class="text-right">
                  <Button
                    v-if="solicitud.hasResult"
                    variant="outline"
                    size="sm"
                    @click="downloadReport(solicitud.id)"
                  >
                    Descargar
                  </Button>
                </TableCell>
              </TableRow>
            </template>
            <template v-else>
              <TableRow>
                <TableCell colspan="7" class="text-center py-8">
                  No hay solicitudes registradas.
                </TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
