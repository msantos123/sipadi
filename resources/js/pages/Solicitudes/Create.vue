<script setup lang="ts">
import { ref } from 'vue';
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

type DetalleType = 'orden_judicial' | 'orden_oficial' | 'requerimiento_fiscal';

const detalleType = ref<DetalleType>('orden_judicial');
const pdfFile = ref<File | null>(null);
const persona_buscada_nombre = ref('');
const persona_buscada_identificacion = ref('');
const fecha_solicitud = ref('');

const formOrdenJudicial = ref<InstanceType<typeof FormOrdenJudicial> | null>(null);
const formOrdenOficial = ref<InstanceType<typeof FormOrdenOficial> | null>(null);
const formRequerimientoFiscal = ref<InstanceType<typeof FormRequerimientoFiscal> | null>(null);

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        pdfFile.value = target.files[0];
    }
};

const submit = () => {
    if (!pdfFile.value) {
        alert('Por favor, sube un archivo PDF.');
        return;
    }

    const data: Record<string, any> = {
        detalleType: detalleType.value,
        pdfFile: pdfFile.value,
        persona_buscada_nombre: persona_buscada_nombre.value,
        persona_buscada_identificacion: persona_buscada_identificacion.value,
        fecha_solicitud: fecha_solicitud.value,
    };

    switch (detalleType.value) {
        case 'orden_judicial':
            if (formOrdenJudicial.value) {
                data.nombre_juzgado_tribunal = formOrdenJudicial.value.nombre_juzgado_tribunal;
                data.numero_orden_judicial = formOrdenJudicial.value.numero_orden_judicial;
            }
            break;
        case 'orden_oficial':
            if (formOrdenOficial.value) {
                data.institucion = formOrdenOficial.value.institucion;
            }
            break;
        case 'requerimiento_fiscal':
            if (formRequerimientoFiscal.value) {
                data.fiscal_apellidos_nombres = formRequerimientoFiscal.value.fiscal_apellidos_nombres;
                data.fiscal_de_materia = formRequerimientoFiscal.value.fiscal_de_materia;
                data.numero_de_caso = formRequerimientoFiscal.value.numero_de_caso;
                data.solicitante_apellidos_nombres = formRequerimientoFiscal.value.solicitante_apellidos_nombres;
                data.solicitante_identificacion = formRequerimientoFiscal.value.solicitante_identificacion;
            }
            break;
    }

    router.post('/solicitudes', data);
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

                    <div class="flex justify-end pt-4">
                        <Button type="submit">
                            Guardar Solicitud
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
