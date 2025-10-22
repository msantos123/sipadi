<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3'
import axios from 'axios';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { template } from 'lodash';

const lotes = ref<any[]>([]);
const selectedLote = ref<any | null>(null);
const estancias = ref<any[]>([]);
const loading = ref(true);
const loadingEstancias = ref(false);
const error = ref<string | null>(null);

// State for the review dialog
const isReviewDialogOpen = ref(false);
const selectedEstanciaForReview = ref<any | null>(null);
const rejectionReason = ref('');


// Asumimos que el rol es GAD por ahora
const fetchLotes = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/lotes/revision/gad');
    lotes.value = response.data;
  } catch (err) {
    console.error("Error al cargar los lotes:", err);
    error.value = 'No se pudieron cargar los lotes para revisión.';
  } finally {
    loading.value = false;
  }
};

const selectLote = async (lote: any) => {
  selectedLote.value = lote;
  try {
    loadingEstancias.value = true;
    const response = await axios.get(`/lotes/${lote.id}/estancias`);
    estancias.value = response.data;
  } catch (err) {
    console.error(`Error al cargar estancias para el lote ${lote.id}:`, err);
    error.value = 'No se pudieron cargar las estancias del lote.';
  } finally {
    loadingEstancias.value = false;
  }
};

const goBackToList = () => {
  selectedLote.value = null;
  estancias.value = [];
  error.value = null;
};

const openReviewDialog = (estancia: any) => {
    selectedEstanciaForReview.value = estancia;
    rejectionReason.value = ''; // Reset
    isReviewDialogOpen.value = true;
};

const aprobarEstancia = async (estanciaId: number) => {
  try {
    await axios.put(`/estancias/${estanciaId}/aprobar-gad`);
    const index = estancias.value.findIndex(e => e.id === estanciaId);
    if (index !== -1) {
      estancias.value[index].estado_aprobacion_gad = 'APROBADO';
    }
    isReviewDialogOpen.value = false;
  } catch (err) {
    console.error(`Error al aprobar la estancia ${estanciaId}:`, err);
    alert('Hubo un error al aprobar la estancia.');
  }
};

const rechazarEstancia = async (estanciaId: number) => {
  if (!rejectionReason.value.trim()) {
    alert('El motivo del rechazo es obligatorio.');
    return;
  }

  try {
    await axios.put(`/estancias/${estanciaId}/rechazar-gad`, {
      gad_observaciones: rejectionReason.value,
    });
    const index = estancias.value.findIndex(e => e.id === estanciaId);
    if (index !== -1) {
      const estancia = estancias.value[index];
      estancia.estado_aprobacion_gad = 'RECHAZADO';
      estancia.gad_observaciones = rejectionReason.value;
    }
    isReviewDialogOpen.value = false;
  } catch (err) {
    console.error(`Error al rechazar la estancia ${estanciaId}:`, err);
    alert('Hubo un error al rechazar la estancia.');
  }
};

const getGadStatusVariant = (status: string): 'secondary' | 'destructive' | 'outline' | 'default' => {
  switch (status) {
    case 'APROBADO':
      return 'secondary';
    case 'RECHAZADO':
      return 'destructive';
    case 'PENDIENTE':
      return 'outline';
    default:
      return 'default';
  }
};

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
};

const loteStatusInfo = computed(() => {
    if (!selectedLote.value) {
        return { text: 'N/A', variant: 'outline' as const };
    }
    switch (selectedLote.value.estado_lote) {
        case 'PENDIENTE_DE_ENVIO': return { text: 'Pendiente de Envío', variant: 'default' as const };
        case 'EN_REVISION_GAD': return { text: 'En Revisión GAD', variant: 'outline' as const };
        case 'EN_REVISION_VMT': return { text: 'En Revisión VMT', variant: 'outline' as const };
        case 'COMPLETADO': return { text: 'Completado', variant: 'secondary' as const };
        default: return { text: 'Desconocido', variant: 'destructive' as const };
    }
});

const canSubmitToVmt = computed(() => {
    if (!estancias.value || estancias.value.length === 0) {
        return false;
    }
    return estancias.value.every(e => e.estado_aprobacion_gad !== 'PENDIENTE');
});

const submitToVmt = async () => {
    if (!selectedLote.value || !canSubmitToVmt.value) return;

    if (confirm('¿Está seguro de que desea finalizar la revisión y enviar este lote al VMT?')) {
        try {
            await axios.put(`/lotes/${selectedLote.value.id}/enviar-vmt`);
            goBackToList();
            fetchLotes();
        } catch (error) {
            console.error('Error al enviar el lote a VMT:', error);
            alert('Hubo un error al enviar el lote a VMT.');
        }
    }
};

onMounted(() => {
  fetchLotes();
});
</script>

<template>
  <AppLayout>
    <Head title="Revisión de Lotes" />

    <div v-if="loading" class="text-center">
      Cargando lotes...
    </div>
    <div v-if="error" class="p-4 text-red-700 bg-red-100 border border-red-400 rounded">
      {{ error }}
    </div>

    <!-- Vista de Lista de Lotes -->
    <div v-if="!selectedLote && !loading" class="space-y-4">
      <h1 class="text-2xl font-bold">Lotes Pendientes de Revisión (GAD)</h1>
      <div v-if="lotes.length === 0 && !error">
        No hay lotes pendientes de revisión.
      </div>
      <div
        v-for="lote in lotes"
        :key="lote.id"
        class="p-4 transition-all duration-200 border rounded-lg cursor-pointer hover:bg-gray-50 hover:shadow-md"
        @click="selectLote(lote)"
      >
        <h3 class="font-bold">Lote #{{ lote.id }}</h3>
        <p>Fecha: {{ lote.fecha_lote }}</p>
        <p>Estado: <span class="font-semibold">{{ lote.estado_lote }}</span></p>
        <p v-if="lote.usuario">Registrado por: {{ lote.usuario.nombres }} {{ lote.usuario.apellido_paterno }}</p>
      </div>
    </div>

    <!-- Vista de Detalle de Lote -->
    <div v-if="selectedLote">
      <button @click="goBackToList" class="mb-4 font-semibold text-blue-600 hover:underline">&larr; Volver a la lista</button>

      <Card>
        <CardHeader class="flex-row items-center justify-between">
            <CardTitle>Detalle del Lote #{{ selectedLote.id }}</CardTitle>
            <div class="flex items-center gap-4">
                <Badge :variant="loteStatusInfo.variant">{{ loteStatusInfo.text }}</Badge>
                <Button @click="submitToVmt" :disabled="!canSubmitToVmt">
                    Finalizar y Enviar a VMT
                </Button>
            </div>
        </CardHeader>
        <CardContent>
            <p class="mb-4"><span class="font-semibold">Fecha del Lote:</span> {{ selectedLote.fecha_lote }}</p>

            <div v-if="loadingEstancias" class="mt-4 text-center">Cargando estancias...</div>

            <Table v-if="!loadingEstancias && estancias.length > 0">
                <TableHeader>
                    <TableRow>
                        <TableHead>Huésped</TableHead>
                        <TableHead>Documento</TableHead>
                        <TableHead>Estado GAD</TableHead>
                        <TableHead class="text-right">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-for="estancia in estancias" :key="estancia.id">
                        <TableRow>
                            <TableCell class="font-medium">{{ estancia.persona.nombres }} {{ estancia.persona.apellido_paterno }}</TableCell>
                            <TableCell>{{ estancia.persona.nro_documento }}</TableCell>
                            <TableCell>
                                <Badge :variant="getGadStatusVariant(estancia.estado_aprobacion_gad)">
                                    {{ estancia.estado_aprobacion_gad }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button @click="openReviewDialog(estancia)" size="sm">
                                    Gestionar
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="estancia.estado_aprobacion_gad === 'RECHAZADO' && estancia.gad_observaciones">
                            <TableCell colspan="4" class="p-2 bg-red-50">
                                <p class="text-sm text-red-800"><span class="font-bold">Observaciones:</span> {{ estancia.gad_observaciones }}</p>
                            </TableCell>
                        </TableRow>
                    </template>>
                </TableBody>
            </Table>

            <div v-if="!loadingEstancias && estancias.length === 0" class="mt-4 text-center">
              Este lote no tiene estancias asociadas.
            </div>
        </CardContent>
    </Card>
    </div>

    <!-- Dialog para Revisión de Estancia -->
    <Dialog :open="isReviewDialogOpen" @update:open="isReviewDialogOpen = false">
        <DialogContent v-if="selectedEstanciaForReview" class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>Revisar Estancia</DialogTitle>
                <DialogDescription>
                    Gestionar la aprobación para el huésped **{{ selectedEstanciaForReview.persona.nombres }}**.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                 <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                    <div>
                        <Label>Huésped</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.persona.nombres }} {{ selectedEstanciaForReview.persona.apellido_paterno }}</p>
                    </div>
                    <div>
                        <Label>Documento</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.persona.nro_documento }}</p>
                    </div>
                    <div>
                        <Label>Nacionalidad</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.persona.nacionalidad?.pais || 'N/A' }}</p>
                    </div>
                    <div>
                        <Label>Estado Actual</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.estado_aprobacion_gad }}</p>
                    </div>
                </div>
            </div>

            <!-- Acciones para estado PENDIENTE -->
            <div v-if="selectedEstanciaForReview.estado_aprobacion_gad === 'PENDIENTE'">
                <div class="space-y-2">
                    <Label for="rejection-reason">Observaciones (requerido si se rechaza)</Label>
                    <Textarea id="rejection-reason" v-model="rejectionReason" placeholder="En caso de rechazo, ingrese el motivo aquí..." />
                </div>

                <DialogFooter>
                    <div class="space-y-2">
                    <Button variant="outline" @click="aprobarEstancia(selectedEstanciaForReview.id)">Aprobar</Button>
                    <Button variant="destructive" @click="rechazarEstancia(selectedEstanciaForReview.id)" :disabled="!rejectionReason.trim()">Rechazar</Button>
                    </div>
                </DialogFooter>

            </div>

            <!-- Info para estado RECHAZADO -->
            <div v-if="selectedEstanciaForReview.estado_aprobacion_gad === 'RECHAZADO'">
                <div class="space-y-2">
                    <Label>Observaciones de Rechazo</Label>
                    <p class="p-2 text-sm text-red-800 bg-red-100 rounded">{{ selectedEstanciaForReview.gad_observaciones }}</p>
                </div>
            </div>

        </DialogContent>
    </Dialog>

  </AppLayout>
</template>
