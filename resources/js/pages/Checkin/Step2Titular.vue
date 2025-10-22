<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useCheckinStore, type PersonaState } from '@/stores/useCheckinStore'
import axios from 'axios'

// Importar componentes
import PersonaForm2 from '@/components/personas/PersonaForm2.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Loader2, X } from 'lucide-vue-next'
import type { Nacionalidad, Municipio, Departamento } from '@/types'

// Props para el formulario de nueva persona
const props = defineProps<{
  nacionalidades: Nacionalidad[]
  departamentos: Departamento[]
  municipios: Municipio[]
}>()

// Store de Pinia
const checkinStore = useCheckinStore()

// Estado local del componente
const searchQuery = reactive({ nro_documento: '', nombres: '', apellido_paterno: '' })
const searchResults = ref<(PersonaState & { parentesco: string })[]>([])
const isLoading = ref(false)
const showPersonaForm = ref(false)
const searchPerformed = ref(false) // Para saber si se ha hecho una búsqueda

// --- LÓGICA DE BÚSQUEDA ---
async function handleSearch() {
  isLoading.value = true
  searchPerformed.value = true
  searchResults.value = []
  try {
    const response = await axios.get('/personas/search', { params: searchQuery })
    searchResults.value = response.data.map((p: PersonaState) => ({ ...p, parentesco: '' }))
  } catch (error) {
    console.error("Error al buscar personas:", error)
  } finally {
    isLoading.value = false
  }
}

// --- LÓGICA DE SELECCIÓN (TITULAR Y DEPENDIENTES) ---
function handleSelectPersona(persona: PersonaState & { parentesco: string }) {
  if (!checkinStore.titular) {
    // Si no hay titular, la persona seleccionada es el titular
    checkinStore.setTitular(persona)
    console.log('Titular establecido:', JSON.parse(JSON.stringify(checkinStore.titular)))
  } else {
    // Si ya hay titular, es un acompañante
    if (!persona.parentesco) {
      alert('Por favor, seleccione un parentesco para el acompañante.')
      return
    }
    if (checkinStore.titular.id === persona.id) {
      alert('El titular no puede ser su propio acompañante.')
      return
    }
    if (checkinStore.dependientes.some(d => d.id === persona.id)) {
      alert('Esta persona ya ha sido agregada como acompañante.')
      return
    }
    checkinStore.addDependiente(persona)
    console.log('Acompañantes actuales:', JSON.parse(JSON.stringify(checkinStore.dependientes)))
  }
  // Limpiar búsqueda para la siguiente
  resetSearch()
}

function handleRemoveDependiente(personaId: number) {
  checkinStore.removeDependiente(personaId)
}

function handleChangeTitular() {
  // La acción reset de Pinia limpia todo el estado del store
  checkinStore.reset()
  resetSearch()
}

// --- LÓGICA DEL FORMULARIO DE NUEVA PERSONA ---
function handlePersonaSaved(persona: PersonaState) {
  showPersonaForm.value = false
  // Selecciona automáticamente la persona recién creada
  handleSelectPersona({ ...persona, parentesco: '' })
}

function cancelPersonaForm() {
  showPersonaForm.value = false
}

// --- UTILIDADES ---
function resetSearch() {
  searchQuery.nro_documento = ''
  searchQuery.nombres = ''
  searchQuery.apellido_paterno = ''
  searchResults.value = []
  searchPerformed.value = false
}

</script>

<template>
  <div>
    <!-- ** VISTA CUANDO YA HAY UN TITULAR SELECCIONADO ** -->
    <div v-if="checkinStore.titular">
      <Card class="border-green-200 bg-green-50">
        <CardHeader>
          <CardTitle>Huésped Titular</CardTitle>
          <div class="flex justify-between items-start">
            <CardDescription>
              {{ checkinStore.titular.nombres }} {{ checkinStore.titular.apellido_paterno }} ({{ checkinStore.titular.nro_documento }})
            </CardDescription>
            <Button variant="outline" size="sm" @click="handleChangeTitular">Cambiar Titular</Button>
          </div>
        </CardHeader>
      </Card>

      <!-- Lista de Acompañantes -->
      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Acompañantes</CardTitle>
        </CardHeader>
        <CardContent>
          <ul v-if="checkinStore.dependientes.length > 0" class="space-y-2">
            <li v-for="dep in checkinStore.dependientes" :key="dep.id" class="flex items-center justify-between p-2 rounded-md bg-gray-50">
              <span>{{ dep.nombres }} {{ dep.apellido_paterno }} ({{ dep.nro_documento }})</span>
              <Button variant="ghost" size="icon" @click="handleRemoveDependiente(dep.id!)">
                <X class="h-4 w-4" />
              </Button>
            </li>
          </ul>
          <p v-else class="text-sm text-muted-foreground">Aún no se han agregado acompañantes.</p>

          <!-- Buscador de Acompañantes -->
          <div class="mt-6 pt-6 border-t">
            <p class="font-semibold mb-4">Buscar y Agregar Acompañante</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <Input placeholder="Nro. Documento" v-model="searchQuery.nro_documento" @keyup.enter="handleSearch"/>
              <Input placeholder="Nombres" v-model="searchQuery.nombres" @keyup.enter="handleSearch"/>
              <Input placeholder="Apellido Paterno" v-model="searchQuery.apellido_paterno" @keyup.enter="handleSearch"/>
            </div>
            <Button @click="handleSearch" :disabled="isLoading">
              <Loader2 v-if="isLoading" class="w-4 h-4 mr-2 animate-spin" />
              Buscar Acompañante
            </Button>
          </div>

          <!-- TABLA DE RESULTADOS (PARA ACOMPAÑANTES) -->
          <div v-if="searchResults.length > 0" class="mt-6">
            <p class="font-semibold">Resultados de la búsqueda:</p>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Nro. Documento</TableHead>
                  <TableHead>Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="p in searchResults" :key="p.id">
                  <TableCell>{{ p.nombres }} {{ p.apellido_paterno }} {{ p.apellido_materno }}</TableCell>
                  <TableCell>{{ p.nro_documento }}</TableCell>
                  <TableCell class="flex items-center space-x-2">
                    <Select v-if="checkinStore.titular" v-model="p.parentesco">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="Parentesco" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="hijo">Hijo/a</SelectItem>
                            <SelectItem value="sobrino">Sobrino/a</SelectItem>
                            <SelectItem value="nieto">Nieto/a</SelectItem>
                            <SelectItem value="apoderado">Apoderado</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button size="sm" @click="handleSelectPersona(p)">
                      {{ checkinStore.titular ? 'Agregar Acompañante' : 'Seleccionar como Titular' }}
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Mensaje de no resultados (para acompañantes) -->
            <div v-if="!isLoading && searchResults.length === 0 && searchPerformed" class="text-center mt-6">
                <p class="text-muted-foreground">No se encontraron resultados para la búsqueda.</p>
            </div>

                    <div class="mt-4 text-center">
                <p>¿La persona no existe?</p>
                <Button variant="link" @click="showPersonaForm = true">
                    Registrar Nueva Persona
                </Button>
            </div>

            <div v-if="showPersonaForm" class="mt-6">
            <PersonaForm2
                :nacionalidades="nacionalidades"
                :departamentos="departamentos"
                :municipios="municipios"
                @persona-saved="handlePersonaSaved"
                @cancel="cancelPersonaForm"
            />
            </div>
        </CardContent>
      </Card>
    </div>

    <!-- ** VISTA INICIAL PARA BUSCAR AL TITULAR ** -->
    <Card v-else>
      <CardHeader>
        <CardTitle>Paso 2: Buscar o Registrar Huésped Titular</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <Input placeholder="Nro. Documento" v-model="searchQuery.nro_documento" @keyup.enter="handleSearch"/>
          <Input placeholder="Nombres" v-model="searchQuery.nombres" @keyup.enter="handleSearch"/>
          <Input placeholder="Apellido Paterno" v-model="searchQuery.apellido_paterno" @keyup.enter="handleSearch"/>
        </div>
        <Button @click="handleSearch" :disabled="isLoading">
          <Loader2 v-if="isLoading" class="w-4 h-4 mr-2 animate-spin" />
          Buscar Titular
        </Button>



        <!-- TABLA DE RESULTADOS (PARA TITULAR) -->
        <div v-if="searchResults.length > 0" class="mt-6">
          <p class="font-semibold">Resultados de la búsqueda:</p>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nombre</TableHead>
                <TableHead>Nro. Documento</TableHead>
                <TableHead>Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="p in searchResults" :key="p.id">
                <TableCell>{{ p.nombres }} {{ p.apellido_paterno }} {{ p.apellido_materno }}</TableCell>
                <TableCell>{{ p.nro_documento }}</TableCell>
                <TableCell class="flex items-center space-x-2">
                    <Select v-if="checkinStore.titular" v-model="p.parentesco">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="Parentesco" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="hijo">Hijo/a</SelectItem>
                            <SelectItem value="sobrino">Sobrino/a</SelectItem>
                            <SelectItem value="nieto">Nieto/a</SelectItem>
                            <SelectItem value="apoderado">Apoderado</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button size="sm" @click="handleSelectPersona(p)">
                    {{ checkinStore.titular ? 'Agregar Acompañante' : 'Seleccionar como Titular' }}
                    </Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Mensaje de no resultados -->
        <div v-if="!isLoading && searchResults.length === 0 && searchPerformed" class="text-center mt-6">
            <p class="text-muted-foreground">No se encontraron resultados para la búsqueda.</p>
        </div>

        <div class="mt-4 text-center">
            <p>¿La persona no existe?</p>
            <Button variant="link" @click="showPersonaForm = true">
                Registrar Nueva Persona
            </Button>
        </div>

        <div v-if="showPersonaForm" class="mt-6">
          <PersonaForm2
              :nacionalidades="nacionalidades"
              :departamentos="departamentos"
              :municipios="municipios"
              @persona-saved="handlePersonaSaved"
              @cancel="cancelPersonaForm"
          />
        </div>
      </CardContent>
    </Card>

    <!-- ** TABLA DE RESULTADOS (COMÚN PARA AMBAS VISTAS) ** -->


  </div>
</template>
