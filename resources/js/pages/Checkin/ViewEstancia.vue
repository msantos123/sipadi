<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import type { PropType } from 'vue'

// --- Interfaces ---
interface Persona {
  id: number;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string | null;
  nro_documento: string;
}

interface Reserva {
  id: number;
  fecha_entrada: string;
  fecha_salida: string;
}

interface Estancia {
  id: number;
  es_titular: boolean;
  tipo_parentesco: string | null;
  fecha_hora_ingreso: string;
  estado_estancia: 'ACTIVA' | 'FINALIZADA' | 'CANCELADA';
  persona: Persona;
  reserva: Reserva;
  dependientes: Estancia[];
}

interface Establecimiento {
    razon_social: string;
}

interface Departamento {
    nombre: string;
}

interface Lote {
    id: number;
    fecha_lote: string;
    estado_lote: 'PENDIENTE_DE_ENVIO' | 'EN_REVISION_GAD' | 'EN_REVISION_VMT' | 'COMPLETADO';
    fecha_envio: string | null;
    establecimiento: Establecimiento;
    departamento: Departamento;
}

// --- Props ---
const props = defineProps({
  estancias: {
    type: Array as PropType<Estancia[]>,
    required: true,
  },
  lote: {
    type: Object as PropType<Lote | null>,
    default: null,
  },
  fecha: {
    type: String,
    required: true,
  }
});

// --- State ---
const isModalOpen = ref(false)
const selectedEstancia = ref<Estancia | null>(null)
const checkoutDate = ref('')
const fechaSeleccionada = ref(props.fecha)

// --- Computed ---
const canSubmitLote = computed(() => {
    return props.lote && props.lote.estado_lote === 'PENDIENTE_DE_ENVIO' && props.estancias.length > 0;
});

const loteStatusInfo = computed(() => {
    if (!props.lote) {
        return { text: 'Sin registros para esta fecha', variant: 'outline' as const };
    }
    switch (props.lote.estado_lote) {
        case 'PENDIENTE_DE_ENVIO': return { text: 'Lote Pendiente de Envío', variant: 'default' as const };
        case 'EN_REVISION_GAD': return { text: 'Enviado a GAD', variant: 'secondary' as const };
        case 'EN_REVISION_VMT': return { text: 'Enviado a VMT', variant: 'secondary' as const };
        case 'COMPLETADO': return { text: 'Completado', variant: 'outline' as const };
        default: return { text: 'Desconocido', variant: 'destructive' as const };
    }
});

// --- Watchers ---
watch(fechaSeleccionada, (newDate) => {
    if (newDate !== props.fecha) {
        filtrarPorFecha(newDate);
    }
});

// --- Methods ---
function filtrarPorFecha(newDate: string) {
    router.get(window.location.pathname, { fecha: newDate }, {
        preserveState: true,
        replace: true,
    });
}

async function submitLote() {
    if (!canSubmitLote.value || !props.lote) return;

    if (confirm('¿Está seguro de que desea cerrar y enviar el reporte del día? Esta acción no se puede deshacer.')) {
        try {
            await axios.put(`/lotes/${props.lote.id}/enviar-gad`);
            router.visit(window.location.href, { preserveScroll: true, preserveState: true });
        } catch (error) {
            console.error('Error al enviar el lote:', error);
            // Aquí se podría mostrar una notificación de error
        }
    }
}

function openDetailsModal(estancia: Estancia) {
  selectedEstancia.value = estancia
  checkoutDate.value = new Date().toISOString().slice(0, 16)
  isModalOpen.value = true
}

async function submitCheckout() {
  if (!selectedEstancia.value) return

  try {
    await axios.put(`/estancias/${selectedEstancia.value.id}/checkout`, {
      fecha_hora_salida_efectiva: checkoutDate.value,
    })
    isModalOpen.value = false
    router.visit(window.location.href, { preserveScroll: true })
  }
  catch (error) {
    console.error('Error al registrar la salida:', error)
  }
}

async function submitCancel() {
  if (!selectedEstancia.value) return
  if (confirm('¿Estás seguro de que deseas cancelar esta estancia y la de todos sus acompañantes?')) {
    try {
      await axios.put(`/estancias/${selectedEstancia.value.id}/cancel`)
      isModalOpen.value = false
      router.visit(window.location.href, { preserveScroll: true })
    }
    catch (error) {
      console.error('Error al cancelar la estancia:', error)
    }
  }
}

function formatDate(dateString: string) {
  const date = new Date(dateString);
  return date.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function getBadgeVariant(estado: string) {
    switch (estado) {
        case 'ACTIVA': return 'default'
        case 'FINALIZADA': return 'secondary'
        case 'CANCELADA': return 'destructive'
        default: return 'outline'
    }
}

</script>

<template>
    <Head title="Ver Estancias" />
    <AppLayout>
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex flex-row items-start justify-between">
                        <div>
                            <CardTitle>Listado de Estancias</CardTitle>
                            <CardDescription v-if="lote" class="mt-2">
                                {{ lote.establecimiento.razon_social }} | {{ lote.departamento.nombre }}
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-4">
                            <Input
                                type="date"
                                v-model="fechaSeleccionada"
                                class="w-[180px]"
                            />
                            <Badge :variant="loteStatusInfo.variant">{{ loteStatusInfo.text }}</Badge>
                            <Button @click="submitLote" :disabled="!canSubmitLote">
                                Cerrar y Enviar Lote a Departamental
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Huésped</TableHead>
                                <TableHead>Documento</TableHead>
                                <TableHead>Rol / Parentesco</TableHead>
                                <TableHead>Fecha de Ingreso</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="estancias.length > 0">
                                <template v-for="titular in estancias" :key="titular.id">
                                    <!-- Fila del Titular -->
                                    <TableRow class="bg-muted/50 font-semibold">
                                        <TableCell>{{ titular.persona.nombres }} {{ titular.persona.apellido_paterno }}</TableCell>
                                        <TableCell>{{ titular.persona.nro_documento }}</TableCell>
                                        <TableCell>Titular</TableCell>
                                        <TableCell>{{ formatDate(titular.fecha_hora_ingreso) }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="getBadgeVariant(titular.estado_estancia)">{{ titular.estado_estancia }}</Badge>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button @click="openDetailsModal(titular)" size="sm" :disabled="titular.estado_estancia !== 'ACTIVA'">
                                                Gestionar Salida
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <!-- Filas de Dependientes -->
                                    <TableRow v-for="dependiente in titular.dependientes" :key="dependiente.id">
                                        <TableCell class="pl-8">{{ dependiente.persona.nombres }} {{ dependiente.persona.apellido_paterno }}</TableCell>
                                        <TableCell>{{ dependiente.persona.nro_documento }}</TableCell>
                                        <TableCell class="capitalize">{{ dependiente.tipo_parentesco }}</TableCell>
                                        <TableCell>{{ formatDate(dependiente.fecha_hora_ingreso) }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="getBadgeVariant(dependiente.estado_estancia)">{{ dependiente.estado_estancia }}</Badge>
                                        </TableCell>
                                        <TableCell></TableCell> <!-- Sin acciones para dependientes -->
                                    </TableRow>
                                </template>
                            </template>
                            <template v-else>
                                <TableRow>
                                    <TableCell colspan="6" class="text-center">No hay estancias registradas para la fecha seleccionada.</TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Modal de Detalles y Acciones -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = false">
            <DialogContent v-if="selectedEstancia">
                <DialogHeader>
                    <DialogTitle>Gestionar Salida de Huésped</DialogTitle>
                    <DialogDescription>
                        Estás gestionando la salida de **{{ selectedEstancia.persona.nombres }} {{ selectedEstancia.persona.apellido_paterno }}** y todos sus acompañantes.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Nombres</Label>
                            <p class="font-semibold">{{ selectedEstancia.persona.nombres }}</p>
                        </div>
                        <div>
                            <Label>Apellidos</Label>
                            <p class="font-semibold">{{ selectedEstancia.persona.apellido_paterno }} {{ selectedEstancia.persona.apellido_materno }}</p>
                        </div>
                    </div>
                     <div class="space-y-2">
                        <Label>Nro. Documento</Label>
                        <p class="font-semibold">{{ selectedEstancia.persona.nro_documento }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="checkout-date">Fecha y Hora de Salida Efectiva</Label>
                        <Input id="checkout-date" type="datetime-local" v-model="checkoutDate" />
                    </div>
                </div>

                <DialogFooter class="justify-between">
                    <Button variant="destructive" @click="submitCancel">Cancelar Estancia</Button>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="isModalOpen = false">Cerrar</Button>
                        <Button @click="submitCheckout">Registrar Salida</Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>