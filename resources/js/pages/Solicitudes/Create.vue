<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import FormOrdenJudicial from '@/pages/Solicitudes/Partials/FormOrdenJudicial.vue';
import FormOrdenOficial from '@/pages/Solicitudes/Partials/FormOrdenOficial.vue';
import FormRequerimientoFiscal from '@/pages/Solicitudes/Partials/FormRequerimientoFiscal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AlertError from '@/components/AlertError.vue';
import Swal from 'sweetalert2'

type DetalleType = 'orden_judicial' | 'orden_oficial' | 'requerimiento_fiscal';

const detalleType = ref<DetalleType>('orden_judicial');
const pdfFile = ref<File | null>(null);
const persona_buscada_nombre = ref('');
const persona_buscada_identificacion = ref('');
const fecha_solicitud = ref('');

const formOrdenJudicial = ref<InstanceType<typeof FormOrdenJudicial> | null>(null);
const formOrdenOficial = ref<InstanceType<typeof FormOrdenOficial> | null>(null);
const formRequerimientoFiscal = ref<InstanceType<typeof FormRequerimientoFiscal> | null>(null);

const solicitudId = ref<number | null>(null);
const solicitudMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);
const hasResults = ref<boolean>(false);
const isSubmitting = ref(false);
const searchResults = ref<any[]>([]);
const showResultsModal = ref(false);

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        pdfFile.value = target.files[0];
    }
};

const toUpperCase = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase();
};

const submit = async () => {
    if (!pdfFile.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Por favor, sube un archivo PDF.'
        });
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = null;
    solicitudMessage.value = null;

    const formData = new FormData();
    formData.append('detalleType', detalleType.value);
    formData.append('pdfFile', pdfFile.value);
    formData.append('persona_buscada_nombre', persona_buscada_nombre.value.trim().toUpperCase());
    formData.append('persona_buscada_identificacion', persona_buscada_identificacion.value.trim());
    formData.append('fecha_solicitud', fecha_solicitud.value);


    switch (detalleType.value) {
        case 'orden_judicial':
            if (formOrdenJudicial.value) {
                formData.append('nombre_juzgado_tribunal', formOrdenJudicial.value.nombre_juzgado_tribunal.trim().toUpperCase());
                formData.append('numero_orden_judicial', formOrdenJudicial.value.numero_orden_judicial.trim().toUpperCase());
            }
            break;
        case 'orden_oficial':
            if (formOrdenOficial.value) {
                formData.append('institucion', formOrdenOficial.value.institucion.trim().toUpperCase());
            }
            break;
        case 'requerimiento_fiscal':
            if (formRequerimientoFiscal.value) {
                formData.append('fiscal_apellidos_nombres', formRequerimientoFiscal.value.fiscal_apellidos_nombres.trim().toUpperCase());
                formData.append('fiscal_de_materia', formRequerimientoFiscal.value.fiscal_de_materia.trim().toUpperCase());
                formData.append('numero_de_caso', formRequerimientoFiscal.value.numero_de_caso.trim().toUpperCase());
                formData.append('solicitante_apellidos_nombres', formRequerimientoFiscal.value.solicitante_apellidos_nombres.trim().toUpperCase());
                formData.append('solicitante_identificacion', formRequerimientoFiscal.value.solicitante_identificacion.trim());
            }
            break;
    }

    try {
        const response = await axios.post('/solicitudes', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            solicitudId.value = response.data.solicitud_id;
            hasResults.value = response.data.has_results;
            searchResults.value = response.data.results || [];
            
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response.data.message,
                confirmButtonText: 'Aceptar'
            });
            
            if (hasResults.value) {
                showResultsModal.value = true;
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.data.message || 'Ocurrió un error desconocido.'
            });
        }
    } catch (error: any) {
        let errorMsg = 'No se pudo conectar con el servidor.';
        if (error.response && error.response.data && error.response.data.message) {
            errorMsg = error.response.data.message;
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMsg
        });
    } finally {
        isSubmitting.value = false;
    }
};

const download = () => {
    if (solicitudId.value) {
        window.open(`/solicitudes/${solicitudId.value}/download`, '_blank');
        
        // Cerrar modal y redirigir después de un breve delay
        setTimeout(() => {
            showResultsModal.value = false;
            router.visit('/solicitudes');
        }, 1000);
    }
};

</script>

<template>
    <AppLayout>
        <div class="p-4 sm:p-6">
            <Heading title="Crear Solicitud" />

            <form @submit.prevent="submit">
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
                        <FormOrdenJudicial v-if="detalleType === 'orden_judicial'" ref="formOrdenJudicial" />
                        <FormOrdenOficial v-if="detalleType === 'orden_oficial'" ref="formOrdenOficial" />
                        <FormRequerimientoFiscal v-if="detalleType === 'requerimiento_fiscal'" ref="formRequerimientoFiscal" />
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="space-y-2">
                            <Label for="persona_buscada_nombre">Nombre de la Persona Buscada</Label>
                            <Input
                                id="persona_buscada_nombre"
                                v-model="persona_buscada_nombre"
                                type="text"
                                @input="toUpperCase"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="persona_buscada_identificacion">Identificación de la Persona Buscada</Label>
                            <Input
                                id="persona_buscada_identificacion"
                                v-model="persona_buscada_identificacion"
                                type="text"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="fecha_solicitud">Fecha de Solicitud</Label>
                            <Input
                                id="fecha_solicitud"
                                v-model="fecha_solicitud"
                                type="date"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="space-y-2">
                            <Label for="pdf_file">Subir archivo PDF</Label>
                            <Input
                                id="pdf_file"
                                type="file"
                                accept=".pdf"
                                @change="handleFileChange"
                            />
                        </div>
                    </div>
                     <div v-if="errorMessage" class="mt-4">
                        <AlertError :message="errorMessage" />
                    </div>

                    <div v-if="solicitudMessage" class="mt-4 p-4 bg-green-100 text-green-800 rounded-md">
                        {{ solicitudMessage }}
                    </div>


                    <div class="flex justify-end pt-4 space-x-4">
                         <Button type="submit" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Guardando...' : 'Guardar Solicitud' }}
                        </Button>
                        <Button
                            v-if="solicitudId && hasResults"
                            type="button"
                            @click="download"
                        >
                            Descargar Excel
                        </Button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal de Resultados -->
        <Dialog :open="showResultsModal" @update:open="showResultsModal = false">
            <DialogContent class="max-w-6xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Resultados de la Búsqueda</DialogTitle>
                    <DialogDescription>
                        Se encontraron {{ searchResults.length }} estancia(s) para la persona buscada.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4">
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
                                    {{ result.reserva?.fecha_entrada || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    {{ result.reserva?.fecha_salida || 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    {{ result.nro_cuarto || 'N/A' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <DialogFooter class="mt-6">
                    <Button variant="outline" @click="showResultsModal = false">
                        Cerrar
                    </Button>
                    <Button @click="download">
                        Descargar Excel
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
