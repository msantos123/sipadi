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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { template } from 'lodash';
import Swal from 'sweetalert2'

const lotes = ref<any[]>([]);
const selectedLote = ref<any | null>(null);
const estancias = ref<any[]>([]);
const loading = ref(true);
const loadingEstancias = ref(false);
const error = ref<string | null>(null);

// Estado para selector de cambio de estado
const nuevoEstadoLote = ref('');

// Estado para selección múltiple de lotes
const selectedLotes = ref<Set<number>>(new Set());
const selectAllLotes = ref(false);

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

// Computed para contar lotes seleccionables (solo EN_REVISION_GAD)
const lotesSeleccionables = computed(() => {
  return filteredLotes.value.filter(lote => lote.estado_lote === 'EN_REVISION_GAD');
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

// Funciones para colores de badges
function getLoteStatusClasses(estado: string) {
    switch (estado) {
        case 'PENDIENTE_DE_ENVIO': 
            return 'bg-yellow-100 text-yellow-800 border-yellow-300';
        case 'EN_REVISION_GAD': 
            return 'bg-blue-100 text-blue-800 border-blue-300';
        case 'EN_REVISION_VMT': 
            return 'bg-purple-100 text-purple-800 border-purple-300';
        case 'COMPLETADO': 
            return 'bg-green-100 text-green-800 border-green-300';
        default: 
            return 'bg-gray-100 text-gray-800 border-gray-300';
    }
}

function getLoteStatusText(estado: string) {
    switch (estado) {
        case 'PENDIENTE_DE_ENVIO': return 'Pendiente de Envío';
        case 'EN_REVISION_GAD': return 'En Revisión GAD';
        case 'EN_REVISION_VMT': return 'En Revisión VMT';
        case 'COMPLETADO': return 'Completado';
        default: return estado;
    }
}

function getEstanciaStatusClasses(status: string) {
    switch (status) {
        case 'ACTIVA': 
            return 'bg-green-100 text-green-800 border-green-300';
        case 'FINALIZADA': 
            return 'bg-red-100 text-red-800 border-red-300';
        case 'CANCELADA': 
            return 'bg-red-100 text-red-800 border-red-300';
        default: 
            return 'bg-gray-100 text-gray-800 border-gray-300';
    }
}

function formatDate(dateString: string) {
  if (!dateString) return 'N/A';
  
  // Para formato ISO 8601 (2025-11-22T00:00:00.000000Z o 2025-11-22T19:49:35.000000Z)
  if (dateString.includes('T')) {
    // Extraer la parte de fecha y hora directamente del string
    const [datePart, timePart] = dateString.split('T');
    const [year, month, day] = datePart.split('-');
    
    // Si hay hora (y no es 00:00:00), mostrarla
    if (timePart && !timePart.startsWith('00:00:00')) {
      const [hour, minute] = timePart.split(':');
      return `${day}/${month}/${year}, ${hour}:${minute}`;
    }
    
    // Si es 00:00:00, solo mostrar la fecha
    return `${day}/${month}/${year}`;
  }
  
  // Manejar formato simple con hora (2025-11-22 19:49:35)
  if (dateString.includes(' ')) {
    const [datePart, timePart] = dateString.split(' ');
    const [year, month, day] = datePart.split('-');
    
    if (timePart && !timePart.startsWith('00:00:00')) {
      const [hour, minute] = timePart.split(':');
      return `${day}/${month}/${year}, ${hour}:${minute}`;
    }
    
    return `${day}/${month}/${year}`;
  }
  
  // Manejar formato solo fecha (2025-11-22)
  if (dateString.includes('-')) {
    const parts = dateString.trim().split('-');
    if (parts.length === 3) {
      const [year, month, day] = parts;
      return `${day}/${month}/${year}`;
    }
  }
  
  return dateString;
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

// Función para cambiar estado del lote
async function cambiarEstadoLote() {
    if (!selectedLote.value || !nuevoEstadoLote.value) {
        alert('Seleccione un estado');
        return;
    }
    
    if (confirm(`¿Está seguro de cambiar el estado del lote a "${getLoteStatusText(nuevoEstadoLote.value)}"?`)) {
        try {
            await axios.put(`/lotes/${selectedLote.value.id}/cambiar-estado`, {
                estado: nuevoEstadoLote.value
            });
            alert('Estado del lote actualizado exitosamente');
            // Recargar lote
            goBackToList();
            fetchLotes();
        } catch (error) {
            console.error('Error al cambiar estado del lote:', error);
            alert('Error al cambiar el estado del lote. Por favor, intente nuevamente.');
        }
    }
}

// Funciones para selección múltiple de lotes
function toggleLote(id: number, event: Event) {
    event.stopPropagation(); // Evitar que se abra el lote al hacer clic en el checkbox
    if (selectedLotes.value.has(id)) {
        selectedLotes.value.delete(id);
    } else {
        selectedLotes.value.add(id);
    }
    selectAllLotes.value = selectedLotes.value.size === filteredLotes.value.length;
}

function toggleSelectAllLotes() {
    if (selectAllLotes.value) {
        selectedLotes.value.clear();
        selectAllLotes.value = false;
    } else {
        // Solo seleccionar lotes que estén en estado EN_REVISION_GAD
        filteredLotes.value
            .filter(lote => lote.estado_lote === 'EN_REVISION_GAD')
            .forEach(lote => selectedLotes.value.add(lote.id));
        selectAllLotes.value = true;
    }
}

async function cambiarEstadoLotesSeleccionados() {
    if (selectedLotes.value.size === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Seleccione al menos un lote'
        });
        return;
    }
    
    const result = await Swal.fire({
        title: '¿Está seguro?',
        text: `¿Desea enviar ${selectedLotes.value.size} lote(s) a Revisión VMT?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            await axios.post('/lotes/cambiar-estado-multiple', {
                lote_ids: Array.from(selectedLotes.value),
                estado: 'EN_REVISION_VMT'
            });
            
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Lotes enviados a VMT exitosamente'
            });
            
            selectedLotes.value.clear();
            selectAllLotes.value = false;
            fetchLotes();
        } catch (error) {
            console.error('Error al cambiar estados:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al enviar los lotes a VMT. Por favor, intente nuevamente.'
            });
        }
    }
}

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
        
        <!-- Controles de selección múltiple -->
        <div v-if="filteredLotes.length > 0" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
            <div class="flex items-center gap-2">
                <input 
                    type="checkbox" 
                    :checked="selectAllLotes" 
                    @change="toggleSelectAllLotes"
                    class="w-4 h-4 cursor-pointer"
                />
                <Label class="cursor-pointer" @click="toggleSelectAllLotes">
                    Seleccionar todos ({{ selectedLotes.size }}/{{ lotesSeleccionables.length }})
                </Label>
            </div>
            
            <Button 
                @click="cambiarEstadoLotesSeleccionados" 
                :disabled="selectedLotes.size === 0"
                class="bg-blue-600 hover:bg-blue-700 text-white"
            >
                Enviar a VMT ({{ selectedLotes.size }})
            </Button>
        </div>
        
      <div v-if="filteredLotes.length === 0 && !error">
        No hay lotes pendientes de revisión para la fecha seleccionada.
      </div>
      <div
        v-for="lote in filteredLotes"
        :key="lote.id"
        class="p-4 transition-all duration-200 border rounded-lg cursor-pointer hover:bg-gray-50 hover:shadow-md"
        :class="{ 
            'border-blue-500 bg-blue-50': selectedLotes.has(lote.id),
            'opacity-60': lote.estado_lote === 'EN_REVISION_VMT' || lote.estado_lote === 'COMPLETADO'
        }"
        @click="selectLote(lote)"
      >
        <div class="flex items-start gap-3">
            <input 
                type="checkbox" 
                :checked="selectedLotes.has(lote.id)"
                :disabled="lote.estado_lote === 'EN_REVISION_VMT' || lote.estado_lote === 'COMPLETADO'"
                @click="toggleLote(lote.id, $event)"
                class="w-5 h-5 mt-1 cursor-pointer"
                :class="{ 'cursor-not-allowed': lote.estado_lote === 'EN_REVISION_VMT' || lote.estado_lote === 'COMPLETADO' }"
            />
            <div class="flex-1">
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
                <p class="flex items-center gap-2">
                    Estado: 
                    <Badge variant="outline" :class="getLoteStatusClasses(lote.estado_lote)">
                        {{ getLoteStatusText(lote.estado_lote) }}
                    </Badge>
                </p>
                <p v-if="lote.usuario_registra">Registrado por: {{ lote.usuario_registra.nombres }} {{ lote.usuario_registra.apellido_paterno }}</p>
            </div>
        </div>
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
                <Badge variant="outline" :class="getLoteStatusClasses(selectedLote.estado_lote)">
                    {{ getLoteStatusText(selectedLote.estado_lote) }}
                </Badge>
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
                        <TableHead>País</TableHead>
                        <TableHead>Estado Estancia</TableHead>
                        <TableHead>Fecha de Ingreso</TableHead>
                        <TableHead>Fecha de Salida</TableHead>
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
                                <Badge variant="outline" :class="getEstanciaStatusClasses(estancia.estado_estancia)">
                                    {{ estancia.estado_estancia }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ formatDate(estancia.fecha_hora_ingreso) }}</TableCell>
                            <TableCell>
                                <span v-if="estancia.fecha_hora_salida_efectiva">
                                    {{ formatDate(estancia.fecha_hora_salida_efectiva) }}
                                </span>
                                <span v-else class="text-gray-500 italic">
                                    Sin salir
                                </span>
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
                        <Badge variant="outline" :class="getEstanciaStatusClasses(selectedEstanciaForReview.estado_estancia)">
                            {{ selectedEstanciaForReview.estado_estancia }}
                        </Badge>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>

  </AppLayout>
</template>
