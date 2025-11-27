<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
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
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select'
import Swal from 'sweetalert2'

const props = defineProps({
  departamentos: {
    type: Array as () => any[],
    required: true,
  },
});

const lotes = ref<any[]>([]);
const selectedLote = ref<any | null>(null);
const estancias = ref<any[]>([]);
const loading = ref(true);
const loadingEstancias = ref(false);
const error = ref<string | null>(null);

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
const selectedDepartamento = ref('');

// State for the review dialog
const isReviewDialogOpen = ref(false);
const selectedEstanciaForReview = ref<any | null>(null);

const fetchLotes = async () => {
  try {
    loading.value = true;
    const params = new URLSearchParams();
    if (selectedDate.value) {
      params.append('fecha_lote', selectedDate.value);
    }
    if (selectedDepartamento.value && selectedDepartamento.value !== 'all') {
      params.append('departamento_id', selectedDepartamento.value);
    }

    const response = await axios.get(`/lotes/revision/vmt?${params.toString()}`);
    lotes.value = response.data;
  } catch (err) {
    console.error("Error al cargar los lotes:", err);
    error.value = 'No se pudieron cargar los lotes para confirmación.';
  } finally {
    loading.value = false;
  }
};

watch([selectedDate, selectedDepartamento], fetchLotes);

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
  fetchLotes(); // Re-fetch to reflect any changes
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
    const [datePart, timePart] = dateString.split('T');
    const [year, month, day] = datePart.split('-');
    
    if (timePart && !timePart.startsWith('00:00:00')) {
      const [hour, minute] = timePart.split(':');
      return `${day}/${month}/${year}, ${hour}:${minute}`;
    }
    
    return `${day}/${month}/${year}`;
  }
  
  if (dateString.includes(' ')) {
    const [datePart, timePart] = dateString.split(' ');
    const [year, month, day] = datePart.split('-');
    
    if (timePart && !timePart.startsWith('00:00:00')) {
      const [hour, minute] = timePart.split(':');
      return `${day}/${month}/${year}, ${hour}:${minute}`;
    }
    
    return `${day}/${month}/${year}`;
  }
  
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
        case 'EN_REVISION_VMT': return { text: 'En Revisión VMT', variant: 'outline' as const };
        case 'COMPLETADO': return { text: 'Completado', variant: 'secondary' as const };
        default: return { text: 'Desconocido', variant: 'destructive' as const };
    }
});

const canCompleteLote = computed(() => {
    return selectedLote.value?.estado_lote === 'EN_REVISION_VMT';
});

const completeLote = async () => {
    if (!selectedLote.value || !canCompleteLote.value) return;

    if (confirm('¿Está seguro de que desea confirmar toda la información y completar este lote? Esta acción es final.')) {
        try {
            await axios.put(`/lotes/${selectedLote.value.id}/completar`);
            goBackToList();
        } catch (error) {
            console.error('Error al completar el lote:', error);
            alert('Hubo un error al completar el lote.');
        }
    }
};

// Computed para contar lotes seleccionables (solo EN_REVISION_VMT)
const lotesSeleccionables = computed(() => {
  return lotes.value.filter(lote => lote.estado_lote === 'EN_REVISION_VMT');
});

// Funciones para selección múltiple de lotes
function toggleLote(id: number, event: Event) {
    event.stopPropagation();
    if (selectedLotes.value.has(id)) {
        selectedLotes.value.delete(id);
    } else {
        selectedLotes.value.add(id);
    }
    selectAllLotes.value = selectedLotes.value.size === lotesSeleccionables.value.length;
}

function toggleSelectAllLotes() {
    if (selectAllLotes.value) {
        selectedLotes.value.clear();
        selectAllLotes.value = false;
    } else {
        lotesSeleccionables.value.forEach(lote => selectedLotes.value.add(lote.id));
        selectAllLotes.value = true;
    }
}

async function completarLotesSeleccionados() {
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
        text: `¿Desea completar ${selectedLotes.value.size} lote(s)? Esta acción es final.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, completar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            await axios.post('/lotes/completar-multiple', {
                lote_ids: Array.from(selectedLotes.value)
            });
            
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Lotes completados exitosamente'
            });
            
            selectedLotes.value.clear();
            selectAllLotes.value = false;
            fetchLotes();
        } catch (error) {
            console.error('Error al completar lotes:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al completar los lotes. Por favor, intente nuevamente.'
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
    <Head title="Confirmación de Lotes (VMT)" />

    <div v-if="loading" class="text-center">
      Cargando lotes...
    </div>
    <div v-if="error" class="p-4 text-red-700 bg-red-100 border border-red-400 rounded">
      {{ error }}
    </div>

    <!-- Vista de Lista de Lotes -->
    <div v-if="!selectedLote && !loading" class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Lotes Pendientes de Confirmación (VMT)</h1>
            <div class="flex items-center gap-2">
                <Select v-model="selectedDepartamento">
                    <SelectTrigger class="w-[200px]">
                        <SelectValue placeholder="Filtrar por Departamento" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos los departamentos</SelectItem>
                        <SelectItem v-for="depto in departamentos" :key="depto.id" :value="depto.id.toString()">
                            {{ depto.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Input
                    type="date"
                    v-model="selectedDate"
                    class="w-[180px]"
                />
                <Button variant="outline" @click="selectedDate = ''">Limpiar Fecha</Button>
            </div>
        </div>
        
        <!-- Controles de selección múltiple -->
        <div v-if="lotes.length > 0" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
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
                @click="completarLotesSeleccionados" 
                :disabled="selectedLotes.size === 0"
                class="bg-green-600 hover:bg-green-700 text-white"
            >
                Completar Revision Lotes ({{ selectedLotes.size }})
            </Button>
        </div>
        
      <div v-if="lotes.length === 0 && !error">
        No hay lotes pendientes de confirmación para los filtros seleccionados.
      </div>

      <div
        v-for="lote in lotes"
        :key="lote.id"
        class="p-4 transition-all duration-200 border rounded-lg cursor-pointer hover:bg-gray-50 hover:shadow-md"
        :class="{ 
            'border-blue-500 bg-blue-50': selectedLotes.has(lote.id),
            'opacity-60': lote.estado_lote === 'COMPLETADO'
        }"
        @click="selectLote(lote)"
      >
        <div class="flex items-start gap-3">
            <input 
                type="checkbox" 
                :checked="selectedLotes.has(lote.id)"
                :disabled="lote.estado_lote === 'COMPLETADO'"
                @click="toggleLote(lote.id, $event)"
                class="w-5 h-5 mt-1 cursor-pointer"
                :class="{ 'cursor-not-allowed': lote.estado_lote === 'COMPLETADO' }"
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

                <p>Fecha: {{ formatDate(lote.fecha_lote) }} | Departamento: {{ lote.departamento.nombre }}</p>
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
            <CardTitle>Detalle del Lote #{{ selectedLote.id }}</CardTitle>
            <Badge variant="outline" :class="getLoteStatusClasses(selectedLote.estado_lote)">
                {{ getLoteStatusText(selectedLote.estado_lote) }}
            </Badge>
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
              Este lote no tiene estancias para confirmar.
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
                        <Label>Nacionalidad</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.persona.nacionalidad?.pais || 'N/A' }}</p>
                    </div>
                    <div>
                        <Label>Parentesco</Label>
                        <p class="font-semibold">{{ selectedEstanciaForReview.tipo_parentesco || 'N/A' }}</p>
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
