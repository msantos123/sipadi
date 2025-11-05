<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useCheckinStore, type PersonaState, type Grupo } from '@/stores/useCheckinStore'
import axios from 'axios'

// Importar componentes y tipos
import PersonaForm2 from '@/components/personas/PersonaForm2.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Loader2, X, UserPlus, BedDouble, Users, PlusCircle, ArrowRight, Trash2 } from 'lucide-vue-next'
import type { Nacionalidad, Municipio, Departamento, TipoCuarto } from '@/types'

// --- DEFINICIÓN DE PROPS Y EMITS ---
const props = defineProps<{
  nacionalidades: Nacionalidad[]
  departamentos: Departamento[]
  municipios: Municipio[]
  tipoCuartos: TipoCuarto[]
}>()

const emit = defineEmits(['next-step'])

// --- ESTADO DEL COMPONENTE ---
const checkinStore = useCheckinStore()

// Estado para la UI
const searchQuery = reactive({ nro_documento: '', nombres: '', apellido_paterno: '' })

interface SearchResultRow extends PersonaState {
  // Estado local para los inputs de cada fila de resultados
  new_nro_cuarto: string
  new_tipo_cuarto_id: string
  parentesco: string
  target_grupo_id: string
}
const searchResults = ref<SearchResultRow[]>([])

const isLoading = ref(false)
const showPersonaForm = ref(false)
const searchPerformed = ref(false)

// --- LÓGICA DE ACCIONES ---

async function handleSearch() {
  isLoading.value = true
  searchPerformed.value = true
  searchResults.value = []
  try {
    const response = await axios.get('/personas/search', { params: searchQuery })
    searchResults.value = response.data.map((p: PersonaState) => ({
      ...p,
      new_nro_cuarto: '',
      new_tipo_cuarto_id: '',
      parentesco: '',
      target_grupo_id: '',
    }))
  } catch (error) {
    console.error("Error al buscar personas:", error)
  } finally {
    isLoading.value = false
  }
}

function handleCreateGrupo(persona: SearchResultRow) {
  if (!persona.new_tipo_cuarto_id || !persona.new_nro_cuarto) {
    alert('Para crear un grupo, debe seleccionar el tipo y número de cuarto.')
    return
  }

  const nuevoGrupo: Grupo = {
    id: `grupo_${Date.now()}`,
    titular: persona,
    dependientes: [],
    tipo_cuarto_id: persona.new_tipo_cuarto_id,
    nro_cuarto: persona.new_nro_cuarto,
  }

  checkinStore.addGrupo(nuevoGrupo)
  // Eliminar la persona de los resultados para no volver a usarla
  searchResults.value = searchResults.value.filter(p => p.id !== persona.id)
}

function handleAddAcompanante(persona: SearchResultRow) {
  if (!persona.target_grupo_id) {
    alert('Seleccione el grupo al que desea agregar el acompañante.')
    return
  }
  if (!persona.parentesco) {
    alert('Seleccione un parentesco para el acompañante.')
    return
  }

  checkinStore.addAcompananteToGrupo({
      grupoId: persona.target_grupo_id,
      acompanante: { ...persona, parentesco: persona.parentesco }
  })
  // Eliminar la persona de los resultados
  searchResults.value = searchResults.value.filter(p => p.id !== persona.id)
}

function handlePersonaSaved(persona: PersonaState) {
  showPersonaForm.value = false
  // Añade la nueva persona a los resultados de búsqueda para que pueda ser utilizada
  searchResults.value.unshift({
    ...persona,
    new_nro_cuarto: '',
    new_tipo_cuarto_id: '',
    parentesco: '',
    target_grupo_id: '',
  })
}

// --- UTILIDADES ---
function resetSearch() {
  searchQuery.nro_documento = ''
  searchQuery.nombres = ''
  searchQuery.apellido_paterno = ''
  searchResults.value = []
  searchPerformed.value = false
}

function cancelPersonaForm() {
  showPersonaForm.value = false
}

</script>

<template>
  <div>
    <h2 class="text-xl font-semibold mb-4">Paso 2: Huéspedes y Habitaciones</h2>

    <!-- SECCIÓN 1: GRUPOS CREADOS -->
    <div class="space-y-6">
        <h3 v-if="checkinStore.grupos.length > 0" class="text-lg font-medium">Grupos/Habitaciones Creadas</h3>
        <Card v-for="grupo in checkinStore.grupos" :key="grupo.id" class="bg-gray-50">
            <CardHeader>
                <CardTitle class="flex justify-between items-center">
                    <span>Habitación: {{ grupo.nro_cuarto }}</span>
                    <Button variant="destructive" size="sm" @click="checkinStore.removeGrupo(grupo.id)">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Eliminar Grupo
                    </Button>
                </CardTitle>
                <CardDescription>Titular: <span class="font-semibold">{{ grupo.titular.nombres }} {{ grupo.titular.apellido_paterno }}</span></CardDescription>
            </CardHeader>
            <CardContent>
                <h4 class="font-semibold mb-2">Acompañantes:</h4>
                <ul v-if="grupo.dependientes.length > 0" class="space-y-2">
                    <li v-for="dep in grupo.dependientes" :key="dep.id" class="flex items-center justify-between p-2 rounded-md bg-white">
                        <span>{{ dep.nombres }} {{ dep.apellido_paterno }} ({{ dep.parentesco }})</span>
                        <Button variant="ghost" size="icon" @click="checkinStore.removeAcompananteFromGrupo({ grupoId: grupo.id, acompananteId: dep.id! })">
                            <X class="h-4 w-4" />
                        </Button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">No hay acompañantes en este grupo.</p>
            </CardContent>
        </Card>
    </div>

    <!-- SECCIÓN 2: BUSCADOR -->
    <Card class="my-8">
      <CardHeader>
        <CardTitle class="flex items-center"><UserPlus class="mr-2 h-5 w-5" /> Buscar o Registrar Personas</CardTitle>
        <CardDescription>Busca personas para crear grupos o añadirlas como acompañantes.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <Input placeholder="Nro. Documento" v-model="searchQuery.nro_documento" @keyup.enter="handleSearch"/>
          <Input placeholder="Nombres" v-model="searchQuery.nombres" @keyup.enter="handleSearch"/>
          <Input placeholder="Apellido Paterno" v-model="searchQuery.apellido_paterno" @keyup.enter="handleSearch"/>
        </div>
        <Button @click="handleSearch" :disabled="isLoading">
          <Loader2 v-if="isLoading" class="w-4 h-4 mr-2 animate-spin" />
          Buscar
        </Button>
        <Button variant="link" @click="showPersonaForm = true">O registrar nueva persona</Button>
      </CardContent>
    </Card>

    <!-- SECCIÓN 3: RESULTADOS DE BÚSQUEDA -->
    <div v-if="searchResults.length > 0" class="mt-6">
      <h3 class="text-lg font-medium">Resultados de la Búsqueda</h3>
      <Table class="mt-4">
        <TableHeader><TableRow><TableHead>Nombre</TableHead><TableHead class="w-[40%]">Crear Grupo (como Titular)</TableHead><TableHead class="w-[40%]">Agregar (como Acompañante)</TableHead></TableRow></TableHeader>
        <TableBody>
          <TableRow v-for="p in searchResults" :key="p.id">
            <TableCell class="font-medium">{{ p.nombres }} {{ p.apellido_paterno }}<br><span class="text-xs text-muted-foreground">{{ p.nro_documento }}</span></TableCell>

            <!-- Acciones para crear grupo -->
            <TableCell>
                <div class="flex items-center space-x-2">
                    <Select v-model="p.new_tipo_cuarto_id">
                        <SelectTrigger><SelectValue placeholder="Tipo Cuarto" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="tipo in tipoCuartos" :key="tipo.id" :value="String(tipo.id)">{{ tipo.nombre }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input v-model="p.new_nro_cuarto" placeholder="Nro Cuarto" class="w-28" />
                    <Button size="sm" @click="handleCreateGrupo(p)">Crear</Button>
                </div>
            </TableCell>

            <!-- Acciones para agregar como acompañante -->
            <TableCell>
                <div class="flex items-center space-x-2">
                    <Select v-model="p.parentesco">
                        <SelectTrigger><SelectValue placeholder="Parentesco" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="hijo">Hijo/a</SelectItem>
                            <SelectItem value="sobrino">Sobrino/a</SelectItem>
                            <SelectItem value="hermano">Hermano/a</SelectItem>
                            <SelectItem value="nieto">Nieto/a</SelectItem>
                            <SelectItem value="apoderado">Apoderado</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="p.target_grupo_id" :disabled="checkinStore.grupos.length === 0">
                        <SelectTrigger><SelectValue placeholder="Seleccionar Grupo" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="g in checkinStore.grupos" :key="g.id" :value="g.id">Hab. {{ g.nro_cuarto }} ({{ g.titular.nombres }})</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button size="sm" @click="handleAddAcompanante(p)" :disabled="checkinStore.grupos.length === 0">Agregar</Button>
                </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Formulario para nueva persona (modal o similar) -->
    <div v-if="showPersonaForm" class="mt-6">
      <PersonaForm2
        :nacionalidades="nacionalidades"
        :departamentos="departamentos"
        :municipios="municipios"
        @persona-saved="handlePersonaSaved"
        @cancel="cancelPersonaForm"
      />
    </div>
  </div>
</template>
