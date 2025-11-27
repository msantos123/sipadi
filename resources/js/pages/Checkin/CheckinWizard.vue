<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useCheckinStore } from '@/stores/useCheckinStore'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { Nacionalidad, Municipio, Departamento, TipoCuarto } from '@/types'
import Swal from 'sweetalert2'

// Importa los componentes de cada paso
import Step1Reserva from './Step1Reserva.vue'
import Step2Titular from './Step2Titular.vue'
import Step3Resumen from './Step3Resumen.vue'

const checkinStore = useCheckinStore()
const page = usePage()

const currentStep = ref(1)
const isSubmitting = ref(false) // Estado para el envío
const stepComponentRef = ref<any>(null) // Referencia al componente del paso actual

// Guardamos el ID del usuario que registra en el store al montar el componente
onMounted(() => {
  const userId = page.props.auth?.user?.id
  if (userId) {
    checkinStore.reserva.usuario_registra_id = userId
  }
})

const props = defineProps({
  nacionalidades: Array as () => Nacionalidad[],
  departamentos: Array as () => Departamento[],
  municipios: Array as () => Municipio[],
  tipoCuartos: Array as () => TipoCuarto[],
})

const steps = [
  Step1Reserva,
  Step2Titular,
  Step3Resumen
]

const currentStepComponent = computed(() => steps[currentStep.value - 1])

async function nextStep() {
  if (!stepComponentRef.value) return

  // Llama a la función `validate` del componente hijo (si existe)
  const validateFunction = stepComponentRef.value.validate
  const isValid = validateFunction ? await validateFunction() : true

  if (isValid && currentStep.value < steps.length) {
    currentStep.value++
  }
}

function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

async function submitCheckin() {
  isSubmitting.value = true;
  const payload = {
    reserva: checkinStore.reserva,
    grupos: checkinStore.grupos
  };
  console.log('Enviando payload:', payload);

  try {
    const response = await axios.post('/checkin', payload);
    await Swal.fire({
      title: '¡Éxito!',
      text: response.data.message || 'Check-in registrado exitosamente.',
      icon: 'success',
      confirmButtonText: 'Aceptar'
    });
    checkinStore.reset();
    currentStep.value = 1;
  } catch (error: any) {
    if (error.response) {
      if (error.response.status === 422) {
        // Manejo de errores de validación
        const errors = error.response.data.errors;
        console.error('Errores de validación:', errors);
        // Construir un mensaje de error legible
        let errorMessage = '';
        for (const key in errors) {
            errorMessage += `- ${errors[key].join(', ')}\n`;
        }
        Swal.fire({
          title: 'Error de Validación',
          text: 'Por favor, corrige los siguientes errores:',
          html: `<pre style="text-align: left;">${errorMessage}</pre>`,
          icon: 'error',
          confirmButtonText: 'Entendido'
        });
      } else {
        // Otros errores del servidor
        console.error('Error del servidor:', error.response.data);
        Swal.fire({
          title: 'Error del Servidor',
          text: error.response.data.message || 'Ocurrió un error en el servidor.',
          icon: 'error',
          confirmButtonText: 'Cerrar'
        });
      }
    } else {
      // Errores de red u otros
      console.error('Error de red:', error);
      Swal.fire({
        title: 'Error de Red',
        text: 'Ocurrió un error de red. Por favor, revisa tu conexión e inténtalo de nuevo.',
        icon: 'error',
        confirmButtonText: 'Cerrar'
      });
    }
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
    <Head title="Registro de Huéspedes" />
    <AppLayout>
    <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
  <Card class="w-full max-w-4xl mx-auto">
    <CardHeader>
      <CardTitle>Registro de Huéspedes (Paso {{ currentStep }} de {{ steps.length }})</CardTitle>
    </CardHeader>
    <CardContent>
      <component :is="currentStepComponent" ref="stepComponentRef" :nacionalidades="nacionalidades" :departamentos="departamentos" :municipios="municipios" :tipoCuartos="tipoCuartos" />
    </CardContent>
    <CardFooter class="flex justify-between mt-6">
      <Button variant="outline" @click="prevStep" :disabled="currentStep === 1 || isSubmitting">
        Anterior
      </Button>
      <Button v-if="currentStep < steps.length" @click="nextStep" :disabled="isSubmitting">
        Siguiente
      </Button>
      <Button v-if="currentStep === steps.length" @click="submitCheckin" :disabled="isSubmitting">
        {{ isSubmitting ? 'Enviando...' : 'Finalizar Registro' }}
      </Button>
    </CardFooter>
  </Card>
    </div>
    </AppLayout>
</template>
