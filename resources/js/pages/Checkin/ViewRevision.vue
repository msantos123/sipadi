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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { template } from 'lodash';

const lotes = ref<any[]>([]);
const selectedLote = ref<any | null>(null);
const estancias = ref<any[]>([]);
const loading = ref(true);
const loadingEstancias = ref(false);
const error = ref<string | null>(null);

const getTodayString = () => {
  const today = new Date();
  const year = today.getFullYear();
  const month = (today.getMonth() + 1).toString().padStart(2, '0');
  const day = today.getDate().toString().padStart(2, '0');
  return `${year}-${month}-${day}`;
}
const selectedDate = ref(getTodayString());

// State for the review dialog
const isReviewDialogOpen = ref(false);
const selectedEstanciaForReview = ref<any | null>(null);

const fetchLotes = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/lotes/revision/gad');
    lotes.value = response.data;
    console.log("Lotes cargados:", lotes.value);
  } catch (err) {
    console.error("Error al cargar los lotes:", err);
    error.value = 'No se pudieron cargar los lotes para revisión.';
  } finally {
    loading.value = false;
  }
};

const filteredLotes = computed(() => {
  if (!selectedDate.value) {
    return lotes.value;
  }
  return lotes.value.filter(lote => lote.fecha_lote.startsWith(selectedDate.value));
});

const processedEstancias = computed(() => {
    if (estancias.value.length === 0) return [];

    const allEstancias = [...estancias.value];
    const titulares = allEstancias.filter(e => e.es_titular);
    const dependientes = allEstancias.filter(e => !e.es_titular);

    const result: any[] = [];

    titulares.forEach(titular => {
        result.push({ ...titular, isTitular: true });
        const misDependientes = dependientes.filter(d => d.responsable_id === titular.id);
        misDependientes.forEach(dep => {
            result.push({ ...dep, isTitular: false });
        });
    });

    const dependientesSinTitular = dependientes.filter(d => !titulares.some(t => t.id === d.responsable_id));
    dependientesSinTitular.forEach(dep => {
            result.push({ ...dep, isTitular: false, hasNoTitular: true });
    });

    return result;
});

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
    isReviewDialogOpen.value = true;
};

const getEstanciaStatusVariant = (status: string): 'secondary' | 'destructive' | 'outline' | 'default' => {
  switch (status) {
    case 'ACTIVA':
      return 'secondary';
    case 'FINALIZADA':
      return 'outline';
    case 'CANCELADA':
      return 'destructive';
    default:
      return 'default';
  }
};

function formatDate(dateString: string) {
  const date = new Date(dateString);
  return date.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

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
    return selectedLote.value?.estado_lote === 'EN_REVISION_GAD';
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
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Lotes Pendientes de Revisión (GAD)</h1>
            <div class="flex items-center gap-2">
                <Input
                    type="date"
                    v-model="selectedDate"
                    class="w-[180px]"
                />
                <Button variant="outline" @click="selectedDate = ''">Limpiar</Button>
            </div>
        </div>
      <div v-if="filteredLotes.length === 0 && !error">
        No hay lotes pendientes de revisión para la fecha seleccionada.
      </div>
      <div
        v-for="lote in filteredLotes"
        :key="lote.id"
        class="p-4 transition-all duration-200 border rounded-lg cursor-pointer hover:bg-gray-50 hover:shadow-md"
        @click="selectLote(lote)"
      >
        <h3 v-if="lote && lote.sucursales && lote.sucursales.length > 0" class="font-bold">
    Establecimiento: {{ lote.sucursales[0].nombre_sucursal }}
</h3>

        <h3 v-else-if="lote && lote.establecimiento" class="font-bold">
            Establecimiento: {{ lote.establecimiento.razon_social }}
        </h3>

        <h3 v-else class="font-bold">
            Establecimiento: No disponible
        </h3>
        <p>Fecha: {{ formatDate(lote.fecha_lote) }}</p>
        <p>Estado: <span class="font-semibold">{{ lote.estado_lote }}</span></p>
        <p v-if="lote.usuario_registra">Registrado por: {{ lote.usuario_registra.nombres }} {{ lote.usuario_registra.apellido_paterno }}</p>
      </div>
    </div>

    <!-- Vista de Detalle de Lote -->
    <div v-if="selectedLote">
      <button @click="goBackToList" class="mb-4 font-semibold text-blue-600 hover:underline">&larr; Volver a la lista</button>

      <Card>
        <CardHeader class="flex-row items-center justify-between">
            <CardTitle>
                Establecimiento:
                {{
                    // 1. ¿Hay lote Y sucursales con elementos?
                    selectedLote && selectedLote.sucursales && selectedLote.sucursales.length > 0
                    ? selectedLote.sucursales[0].nombre_sucursal + ' | ' + selectedLote.sucursales[0].ciudad

                    // 2. Si no, ¿hay lote Y establecimiento principal?
                    : selectedLote && selectedLote.establecimiento
                        ? selectedLote.establecimiento.razon_social + ' | ' + selectedLote.establecimiento.ciudad

                        // 3. Caso por defecto
                        : 'No disponible'
                }}
            </CardTitle>
            <div class="flex items-center gap-4">
                <Badge :variant="loteStatusInfo.variant">{{ loteStatusInfo.text }}</Badge>
                <Button @click="submitToVmt" :disabled="!canSubmitToVmt">
                    Finalizar y Enviar a VMT
                </Button>
            </div>
        </CardHeader>
        <CardContent>
            <p class="mb-4"><span class="font-semibold">Fecha del Lote:</span> {{ formatDate(selectedLote.fecha_lote) }}</p>

            <div v-if="loadingEstancias" class="mt-4 text-center">Cargando estancias...</div>

            <Table v-if="!loadingEstancias && estancias.length > 0">
                <TableHeader>
                    <TableRow>
                        <TableHead>Huésped</TableHead>
                        <TableHead>Documento</TableHead>
                        <TableHead>pais</TableHead>
                        <TableHead>Estado Estancia</TableHead>
                        <TableHead class="text-right">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-for="estancia in processedEstancias" :key="estancia.id">
                        <TableRow>
                            <TableCell class="font-medium" :class="{ 'pl-8': !estancia.isTitular }">
                                <span v-if="!estancia.isTitular" class="text-gray-400">↳ </span>
                                {{ estancia.persona.nombres }} {{ estancia.persona.apellido_paterno }}
                            </TableCell>
                            <TableCell>{{ estancia.persona.nro_documento }}</TableCell>
                            <TableCell>{{ estancia.persona.nacionalidad.pais }}</TableCell>
                            <TableCell>
                                <Badge :variant="getEstanciaStatusVariant(estancia.estado_estancia)">
                                    {{ estancia.estado_estancia }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button @click="openReviewDialog(estancia)" size="sm">
                                    Gestionar
                                </Button>
                            </TableCell>
                        </TableRow>
                    </template>
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
                <DialogTitle>Detalles de la Estancia</DialogTitle>
                <DialogDescription>
                    Información del huésped **{{ selectedEstanciaForReview.persona.nombres }}**.
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
                        <Label>Pais</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.persona.nacionalidad?.pais || 'N/A' }}</p>
                    </div>
                    <div>
                        <Label>Parentesco</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.tipo_parentesco || 'N/A' }}</p>
                    </div>
                     <div>
                        <Label>Fecha y Hora Ingreso</Label>
                        <p class="font-semibold">{{ formatDate(selectedEstanciaForReview.fecha_hora_ingreso || ' ') }}</p>
                    </div>
                    <div>
                        <Label>Fecha y Hora Salida</Label>
                        <p class="font-semibold">{{ formatDate(selectedEstanciaForReview.fecha_hora_salida_efectiva || ' ') }}</p>
                    </div>
                    <div>
                        <Label>Estado de la Estancia</Label>
                        <Badge :variant="getEstanciaStatusVariant(selectedEstanciaForReview.estado_estancia)">
                            {{ selectedEstanciaForReview.estado_estancia }}
                        </Badge>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>

  </AppLayout>
</template>
