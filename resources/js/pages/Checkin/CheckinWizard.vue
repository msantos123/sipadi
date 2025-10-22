<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useCheckinStore } from '@/stores/useCheckinStore'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { Nacionalidad, Municipio, Departamento } from '@/types'

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
  municipios: Array as () => Municipio[]
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
    titular: checkinStore.titular,
    dependientes: checkinStore.dependientes
  };
  console.log('Enviando payload:', payload);

  try {
    const response = await axios.post('/checkin', payload);
    alert(response.data.message || 'Check-in registrado exitosamente.');
    checkinStore.reset();
    currentStep.value = 1;
  } catch (error: any) {
    if (error.response) {
      if (error.response.status === 422) {
        // Manejo de errores de validación
        const errors = error.response.data.errors;
        console.error('Errores de validación:', errors);
        // Construir un mensaje de error legible
        let errorMessage = 'Por favor, corrige los siguientes errores:\n';
        for (const key in errors) {
            errorMessage += `- ${errors[key].join(', ')}\n`;
        }
        alert(errorMessage);
      } else {
        // Otros errores del servidor
        console.error('Error del servidor:', error.response.data);
        alert(error.response.data.message || 'Ocurrió un error en el servidor.');
      }
    } else {
      // Errores de red u otros
      console.error('Error de red:', error);
      alert('Ocurrió un error de red. Por favor, revisa tu conexión e inténtalo de nuevo.');
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
      <component :is="currentStepComponent" ref="stepComponentRef" :nacionalidades="nacionalidades" :departamentos="departamentos" :municipios="municipios" />
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