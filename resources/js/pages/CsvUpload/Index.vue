<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Terminal, Download } from 'lucide-vue-next'

const props = defineProps({
  uploadResult: {
    type: Object,
    default: null,
  },
  csvFormatMessage: {
    type: String,
    default: '',
  },
});

const form = useForm({
  csv_file: null as File | null,
});

const submit = () => {
  form.post('/csv-upload');
};

const tableHeaders = computed(() => {
  if (props.uploadResult?.data?.length > 0) {
    return Object.keys(props.uploadResult.data[0]);
  }
  return [];
});
</script>

<template>
  <AppLayout>
    <div class="p-4 sm:p-6">
      <Heading title="Carga Masiva de Huéspedes (CSV)" description="Sube un archivo CSV para registrar múltiples check-ins de una sola vez." />

      <div class="mt-6">
        <a href="/storage/sipadi.csv" download="sipadi.csv">
          <Button type="button" variant="secondary">
            <Download class="mr-2 h-4 w-4" />
            Descargar Plantilla CSV
          </Button>
        </a>
      </div>

      <!-- Mensaje de formato -->
      <Alert class="mt-6 max-w-4xl">
        <Terminal class="h-4 w-4" />
        <AlertTitle>Formato del CSV</AlertTitle>
        <AlertDescription>
          {{ csvFormatMessage }}
          <br>
          <strong>tipo_documento:</strong> El tipo de documento debera ser "ci" o "pasaporte".
          <strong>complemento:</strong> El campo complemento no es obligatorio.
          <strong>sexo:</strong> El campo sexo debera ser "M" para masculino, "F" para femenino o "O" para otros.
          <strong>estado_civil:</strong> El campo estado civil deberá ser "soltero", "casado", "divorciado", "viudo" o "union_libre".
          <strong>codigo_nacionalidad, departamento_sigla, codigo_municipio:</strong> Para consultar los códigos de nacionalidad, departamento o municipio <a href="/storage/codigos.pdf" target="_blank" rel="noopener noreferrer" class="font-bold underline text-blue-600 hover:text-blue-800">Click Aquí</a>
          <strong>tipo_cuarto:</strong> El campo tipo_cuarto deberá ser los mismos que tiene registrado en Habitaciones.
          <strong>Nota:</strong> Para bolivianos, usa `departamento_sigla` y `codigo_municipio`. Para extranjeros, usa `ciudad_origen`.
        </AlertDescription>
      </Alert>

      <form @submit.prevent="submit" class="mt-6 max-w-2xl">
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="csv_file">Archivo CSV</Label>
            <Input
              id="csv_file"
              type="file"
              @input="(event: Event) => {
                const target = event.target as HTMLInputElement;
                if (target.files) {
                  form.csv_file = target.files[0];
                }
              }"
            />
            <div v-if="form.errors.csv_file" class="text-sm text-red-600 mt-1">
              {{ form.errors.csv_file }}
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Procesando...' : 'Subir y Procesar Archivo' }}
            </Button>
          </div>
        </div>
      </form>

      <!-- Resultados de la carga -->
      <div v-if="uploadResult" class="mt-8">
        <Alert :variant="uploadResult.success ? 'default' : 'destructive'">
          <AlertTitle>{{ uploadResult.success ? 'Proceso Completado' : 'Proceso con Errores' }}</AlertTitle>
          <AlertDescription>
            {{ uploadResult.message }} Filas procesadas con éxito: {{ uploadResult.processed_rows || 0 }}.
          </AlertDescription>
        </Alert>

        <!-- Errores de validación -->
        <div v-if="uploadResult.errors && uploadResult.errors.length > 0" class="mt-4">
          <h3 class="font-bold text-lg text-red-700">Errores Encontrados:</h3>
          <ul class="list-disc pl-5 mt-2 text-sm text-red-600">
            <li v-for="(error, index) in uploadResult.errors" :key="index">
              Fila {{ error.row }}: {{ error.message }}
            </li>
          </ul>
        </div>

        <!-- Información sobre personas existentes -->
        <div v-if="uploadResult.existing_persons && uploadResult.existing_persons.length > 0" class="mt-4">
          <h3 class="font-bold text-lg text-blue-700">Información Adicional:</h3>
          <ul class="list-disc pl-5 mt-2 text-sm text-blue-600">
            <li v-for="(info, index) in uploadResult.existing_persons" :key="`info-${index}`">
              Fila {{ info.row }}: {{ info.message }}
            </li>
          </ul>
        </div>

        <!-- Tabla con datos procesados -->
        <div v-if="uploadResult.data && uploadResult.data.length > 0" class="mt-6">
          <h3 class="font-bold text-lg mb-2">Datos Procesados Correctamente</h3>
          <div class="border rounded-md mt-4">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead v-for="header in tableHeaders" :key="header">
                    {{ header }}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(row, rowIndex) in uploadResult.data" :key="rowIndex">
                  <TableCell v-for="header in tableHeaders" :key="`${rowIndex}-${header}`">
                    {{ row[header] }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
