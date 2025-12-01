<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import FormOrdenJudicial from '@/pages/Solicitudes/Partials/FormOrdenJudicial.vue';
import FormOrdenOficial from '@/pages/Solicitudes/Partials/FormOrdenOficial.vue';
import FormRequerimientoFiscal from '@/pages/Solicitudes/Partials/FormRequerimientoFiscal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Search, FileText } from 'lucide-vue-next';
import axios from 'axios';
import { format } from 'date-fns';

type DetalleType = 'orden_judicial' | 'orden_oficial' | 'requerimiento_fiscal';

const detalleType = ref<DetalleType>('orden_judicial');
const pdfFile = ref<File | null>(null);

const formOrdenJudicial = ref<InstanceType<typeof FormOrdenJudicial> | null>(null);
const formOrdenOficial = ref<InstanceType<typeof FormOrdenOficial> | null>(null);
const formRequerimientoFiscal = ref<InstanceType<typeof FormRequerimientoFiscal> | null>(null);

const searchPerformed = ref(false);
const searchResults = ref<any[]>([]);
const searchMessage = ref('');
const isSearching = ref(false);
const fileError = ref<string>('');

const form = useForm({
    detalleType: 'orden_judicial' as DetalleType,
    pdfFile: null as File | null,
    persona_buscada_nombre: '',
    persona_buscada_identificacion: '',
    fecha_solicitud: '',
    resultado_busqueda: '',
    // Campos específicos de orden judicial
    nombre_juzgado_tribunal: '',
    numero_orden_judicial: '',
    // Campos específicos de orden oficial
    institucion: '',
    // Campos específicos de requerimiento fiscal
    fiscal_apellidos_nombres: '',
    fiscal_de_materia: '',
    numero_de_caso: '',
    solicitante_apellidos_nombres: '',
    solicitante_identificacion: '',
});

const formatDateTime = (dateString: string | null) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return format(date, 'dd/MM/yyyy HH:mm');
    } catch (error) {
        return dateString;
    }
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        pdfFile.value = target.files[0];
        form.pdfFile = target.files[0];
        fileError.value = '';
    }
};

const toUpperCase = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase();
};

const searchPerson = async () => {
    if (!form.persona_buscada_nombre || !form.persona_buscada_identificacion) {
        alert('Por favor, complete el nombre e identificación de la persona buscada.');
        return;
    }

    isSearching.value = true;
    searchPerformed.value = false;

    try {
        const response = await axios.post('/solicitudes/search', {
            persona_buscada_nombre: form.persona_buscada_nombre.trim().toUpperCase(),
            persona_buscada_identificacion: form.persona_buscada_identificacion.trim(),
        });

        if (response.data.success) {
            searchResults.value = response.data.results;
            searchMessage.value = response.data.message;
            searchPerformed.value = true;
            
            // Guardar resultados como JSON string para enviar al backend
            form.resultado_busqueda = JSON.stringify(response.data.results);
        }
    } catch (error: any) {
        console.error('Error en búsqueda:', error);
        searchMessage.value = error.response?.data?.message || 'Error al buscar persona';
        searchResults.value = [];
        searchPerformed.value = true;
        form.resultado_busqueda = '[]';
    } finally {
        isSearching.value = false;
    }
};

const createSolicitud = () => {
    
    if (!pdfFile.value) {
        fileError.value = 'Por favor, sube un archivo PDF.';
        return;
    }

    if (!searchPerformed.value) {
        alert('Por favor, realiza la búsqueda de la persona antes de crear la solicitud.');
        return;
    }

    fileError.value = '';
    form.detalleType = detalleType.value;
    form.persona_buscada_nombre = form.persona_buscada_nombre.trim().toUpperCase();
    form.persona_buscada_identificacion = form.persona_buscada_identificacion.trim();

    // Asignar campos específicos según el tipo de detalle
    switch (detalleType.value) {
        case 'orden_judicial':
            if (formOrdenJudicial.value) {
                form.nombre_juzgado_tribunal = formOrdenJudicial.value.nombre_juzgado_tribunal.trim().toUpperCase();
                form.numero_orden_judicial = formOrdenJudicial.value.numero_orden_judicial.trim().toUpperCase();
            }
            break;
        case 'orden_oficial':
            if (formOrdenOficial.value) {
                form.institucion = formOrdenOficial.value.institucion.trim().toUpperCase();
            }
            break;
        case 'requerimiento_fiscal':
            if (formRequerimientoFiscal.value) {
                form.fiscal_apellidos_nombres = formRequerimientoFiscal.value.fiscal_apellidos_nombres.trim().toUpperCase();
                form.fiscal_de_materia = formRequerimientoFiscal.value.fiscal_de_materia.trim().toUpperCase();
                form.numero_de_caso = formRequerimientoFiscal.value.numero_de_caso.trim().toUpperCase();
                form.solicitante_apellidos_nombres = formRequerimientoFiscal.value.solicitante_apellidos_nombres.trim().toUpperCase();
                form.solicitante_identificacion = formRequerimientoFiscal.value.solicitante_identificacion.trim();
                
            }
            break;
    }

    form.post('/solicitudes', {
        forceFormData: true,
        onSuccess: () => {
        },
        onError: (errors) => {
        }
    });
};

</script>

<template>
    <AppLayout>
        <div class="p-4 sm:p-6">
            <Heading title="Crear Solicitud" />

            <form @submit.prevent="createSolicitud">
                <div class="space-y-6 mt-6">
                    <div class="space-y-2">
                        <RadioGroup v-model="detalleType" default-value="orden_judicial" class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <RadioGroupItem id="r1" value="orden_judicial" />
                                <Label for="r1">Orden Judicial</Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <RadioGroupItem id="r2" value="orden_oficial" />
                                <Label for="r2">Orden Oficial</Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <RadioGroupItem id="r3" value="requerimiento_fiscal" />
                                <Label for="r3">Requerimiento Fiscal</Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <div class="mt-6">
                        <FormOrdenJudicial v-if="detalleType === 'orden_judicial'" ref="formOrdenJudicial" :errors="form.errors" />
                        <FormOrdenOficial v-if="detalleType === 'orden_oficial'" ref="formOrdenOficial" :errors="form.errors" />
                        <FormRequerimientoFiscal v-if="detalleType === 'requerimiento_fiscal'" ref="formRequerimientoFiscal" :errors="form.errors" />
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="space-y-2">
                            <Label for="persona_buscada_nombre">Nombre de la Persona Buscada</Label>
                            <Input
                                id="persona_buscada_nombre"
                                v-model="form.persona_buscada_nombre"
                                type="text"
                                required
                                @input="toUpperCase"
                            />
                            <div v-if="form.errors.persona_buscada_nombre" class="text-sm text-red-500">
                                {{ form.errors.persona_buscada_nombre }}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="persona_buscada_identificacion">Identificación de la Persona Buscada</Label>
                            <Input
                                id="persona_buscada_identificacion"
                                v-model="form.persona_buscada_identificacion"
                                type="text"
                                required
                            />
                            <div v-if="form.errors.persona_buscada_identificacion" class="text-sm text-red-500">
                                {{ form.errors.persona_buscada_identificacion }}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="fecha_solicitud">Fecha de Solicitud</Label>
                            <Input
                                id="fecha_solicitud"
                                v-model="form.fecha_solicitud"
                                type="date"
                                required
                            />
                            <div v-if="form.errors.fecha_solicitud" class="text-sm text-red-500">
                                {{ form.errors.fecha_solicitud }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="space-y-2">
                            <Label for="pdf_file">Subir archivo PDF</Label>
                            <Input
                                id="pdf_file"
                                type="file"
                                accept=".pdf"
                                required
                                @change="handleFileChange"
                            />
                            <div v-if="fileError" class="text-sm text-red-500">
                                {{ fileError }}
                            </div>
                            <div v-if="form.errors.pdfFile" class="text-sm text-red-500">
                                {{ form.errors.pdfFile }}
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Búsqueda -->
                    <div class="flex justify-end pt-4">
                        <Button 
                            type="button" 
                            @click="searchPerson"
                            :disabled="isSearching"
                            variant="outline"
                        >
                            <Search class="mr-2 h-4 w-4" />
                            {{ isSearching ? 'Buscando...' : 'Buscar Persona' }}
                        </Button>
                    </div>

                    <!-- Resultados de Búsqueda -->
                    <div v-if="searchPerformed" class="mt-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Resultados de la Búsqueda</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Alert class="mb-4">
                                    <AlertDescription>
                                        {{ searchMessage }}
                                    </AlertDescription>
                                </Alert>

                                <div v-if="searchResults.length > 0" class="mt-4 border rounded-lg">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Nombre Completo</TableHead>
                                                <TableHead>Documento</TableHead>
                                                <TableHead>Nacionalidad</TableHead>
                                                <TableHead>Establecimiento</TableHead>
                                                <TableHead>Departamento</TableHead>
                                                <TableHead>Fecha Entrada</TableHead>
                                                <TableHead>Fecha Salida</TableHead>
                                                <TableHead>Nro. Cuarto</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="(result, index) in searchResults" :key="index">
                                                <TableCell>
                                                    {{ result.persona?.nombres }} {{ result.persona?.apellido_paterno }} {{ result.persona?.apellido_materno }}
                                                </TableCell>
                                                <TableCell>
                                                    {{ result.persona?.tipo_documento }}: {{ result.persona?.nro_documento }}
                                                </TableCell>
                                                <TableCell>
                                                    {{ result.persona?.nacionalidad?.pais || 'N/A' }}
                                                </TableCell>
                                                <TableCell>
                                                    {{ result.reserva?.establecimiento?.razon_social || 'N/A' }}
                                                </TableCell>
                                                <TableCell>
                                                    {{ result.reserva?.establecimiento?.sucursales?.[0]?.departamento?.nombre || 'N/A' }}
                                                </TableCell>
                                                <TableCell>
                                                    {{ formatDateTime(result.reserva?.fecha_entrada) }}
                                                </TableCell>
                                                <TableCell>
                                                    {{ formatDateTime(result.reserva?.fecha_salida) }}
                                                </TableCell>
                                                <TableCell>
                                                    {{ result.nro_cuarto || 'N/A' }}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div v-if="form.recentlySuccessful" class="mt-4 p-4 bg-green-100 text-green-800 rounded-md">
                        Solicitud creada correctamente
                    </div>

                    <!-- Botón de Crear Solicitud -->
                    <div v-if="searchPerformed" class="flex justify-end pt-4 space-x-4">
                         <Button type="submit" :disabled="form.processing">
                            <FileText class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Creando...' : 'Crear Solicitud' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
