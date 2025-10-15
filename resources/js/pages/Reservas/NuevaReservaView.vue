
<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3';
import type { Persona, Nacionalidad, Municipio, Departamento } from '@/types'
import PersonaSearch from '@/components/personas/PersonaSearch.vue'
import PersonaForm from '@/components/personas/PersonaForm.vue'
import EstanciaForm from '@/components/estancias/EstanciaForm.vue';
import AppLayout from '@/layouts/AppLayout.vue'; // Asumiendo que tienes un layout principal
import { Button } from '@/components/ui/button';

const props = defineProps<{
  nacionalidades: Nacionalidad[]
  departamentos: Departamento[]
  municipios: Municipio[]
}>()

const titular = ref<Persona | null>(null)
const showPersonaForm = ref(false)

const handlePersonaSelected = (persona: Persona) => {
  titular.value = persona
  showPersonaForm.value = false
  // Aquí iniciaríamos el siguiente paso del wizard (Estancia)
}

const handlePersonaSaved = (persona: Persona) => {
  titular.value = persona
  showPersonaForm.value = false
  // Aquí iniciaríamos el siguiente paso del wizard (Estancia)
}
</script>

<template>
    <Head title="Crear Usuario" />
    <AppLayout>
    <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="text-2xl font-bold">Crear Nueva Reserva</h1>

        <div v-if="!titular">
            <PersonaSearch @persona-selected="handlePersonaSelected" />

            <div class="mt-4 text-center">
            <p>¿La persona no existe?</p>
            <Button variant="link" @click="showPersonaForm = true">
                Registrar Nueva Persona
            </Button>
            </div>

            <div v-if="showPersonaForm" class="mt-6">
            <PersonaForm
                :nacionalidades="nacionalidades"
                :departamentos="departamentos"
                :municipios="municipios"
                @persona-saved="handlePersonaSaved"
            />
            </div>
        </div>

        <div v-else>
            <div class="rounded-lg p-6 shadow-md">
            <h2 class="text-2xl font-semibold">Titular de la Reserva</h2>
            <p class="mt-2 text-lg">{{ titular.nombres }} {{ titular.apellido_paterno }} {{ titular.apellido_materno }}</p>
            <p class="text-gray-600">Documento: {{ titular.nro_documento }} {{ titular.complemento }} Pais: {{ titular.nacionalidad_id }}</p>
            <Button variant="outline" class="mt-4" @click="titular = null">
                Cambiar Titular
            </Button>

            <hr class="my-6" />

            <!-- El siguiente paso del wizard (ej. EstanciaWizard) iría aquí -->
                <div class="mt-6">
                    <EstanciaForm
                        :nacionalidades="nacionalidades"
                        :departamentos="departamentos"
                        :municipios="municipios"
                        @persona-saved="handlePersonaSaved"
                    />
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
