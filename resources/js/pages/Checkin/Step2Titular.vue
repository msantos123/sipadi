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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
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

// --- FUNCIONES DE VALIDACIÓN ---
// Validación para documento: solo números y letras (sin espacios ni caracteres especiales)
const sanitizeAlphanumeric = (value: string) => {
  return value.replace(/[^0-9A-Za-z]/g, '').toUpperCase()
}

// Validación para nombres y apellidos: solo letras, espacios y acentos
const sanitizeLettersOnly = (value: string) => {
  return value.replace(/[^A-Za-zÀ-ÿ\u00f1\u00d1\s]/g, '').toUpperCase()
}

// --- ESTADO DEL COMPONENTE ---
const checkinStore = useCheckinStore()

// Estado para la UI
const searchQuery = reactive({ nro_documento: '', nombres: '', apellido_paterno: '' })
const searchResults = ref<PersonaState[]>([])
const isLoading = ref(false)
const showPersonaForm = ref(false)
const searchPerformed = ref(false)

// Estado para selección y modal
const selectedPersona = ref<PersonaState | null>(null)
const showActionModal = ref(false)
const selectedAction = ref<'crear_grupo' | 'agregar_acompanante' | null>(null)

// Datos del formulario según la acción
const grupoForm = reactive({
  tipo_cuarto_id: '',
  nro_cuarto: ''
})

const acompananteForm = reactive({
  parentesco: '',
  grupo_id: ''
})

// --- LÓGICA DE ACCIONES ---

async function handleSearch() {
  isLoading.value = true
  searchPerformed.value = true
  searchResults.value = []
  try {
    const response = await axios.get('/personas/search', { params: searchQuery })
    searchResults.value = response.data
  } catch (error) {
    console.error("Error al buscar personas:", error)
  } finally {
    isLoading.value = false
  }
}

// Validar si una persona ya está en algún grupo
function isPersonaEnGrupos(personaId: number | string): { existe: boolean; tipo: 'titular' | 'dependiente' | null; grupo: Grupo | null } {
  for (const grupo of checkinStore.grupos) {
    // Verificar si es titular
    if (grupo.titular.id === personaId) {
      return { existe: true, tipo: 'titular', grupo }
    }
    // Verificar si es dependiente
    const esDependiente = grupo.dependientes.some(dep => dep.id === personaId)
    if (esDependiente) {
      return { existe: true, tipo: 'dependiente', grupo }
    }
  }
  return { existe: false, tipo: null, grupo: null }
}

function selectPersona(persona: PersonaState) {
  selectedPersona.value = persona
  selectedAction.value = null
  showActionModal.value = true
  // Resetear formularios
  grupoForm.tipo_cuarto_id = ''
  grupoForm.nro_cuarto = ''
  acompananteForm.parentesco = ''
  acompananteForm.grupo_id = ''
}

function handleCreateGrupo() {
  if (!selectedPersona.value || !grupoForm.tipo_cuarto_id || !grupoForm.nro_cuarto) {
    alert('Por favor, complete todos los campos.')
    return
  }

  // Validar si la persona ya está en algún grupo
  const validacion = isPersonaEnGrupos(selectedPersona.value.id!)
  if (validacion.existe) {
    const nombreCompleto = `${selectedPersona.value.nombres} ${selectedPersona.value.apellido_paterno}`
    const habitacion = validacion.grupo?.nro_cuarto
    
    if (validacion.tipo === 'titular') {
      alert(`${nombreCompleto} ya está registrado como TITULAR en la habitación ${habitacion}.\n\nNo puede agregarse nuevamente.`)
    } else {
      alert(`${nombreCompleto} ya está registrado como ACOMPAÑANTE en la habitación ${habitacion}.\n\nNo puede agregarse nuevamente.`)
    }
    return
  }

  const nuevoGrupo: Grupo = {
    id: `grupo_${Date.now()}`,
    titular: selectedPersona.value,
    dependientes: [],
    tipo_cuarto_id: grupoForm.tipo_cuarto_id,
    nro_cuarto: grupoForm.nro_cuarto,
  }

  checkinStore.addGrupo(nuevoGrupo)
  
  // Eliminar la persona de los resultados
  searchResults.value = searchResults.value.filter(p => p.id !== selectedPersona.value!.id)
  
  // Cerrar modal y resetear
  showActionModal.value = false
  selectedPersona.value = null
  selectedAction.value = null
}

function handleAddAcompanante() {
  if (!selectedPersona.value || !acompananteForm.grupo_id || !acompananteForm.parentesco) {
    alert('Por favor, complete todos los campos.')
    return
  }

  // Validar si la persona ya está en algún grupo
  const validacion = isPersonaEnGrupos(selectedPersona.value.id!)
  if (validacion.existe) {
    const nombreCompleto = `${selectedPersona.value.nombres} ${selectedPersona.value.apellido_paterno}`
    const habitacion = validacion.grupo?.nro_cuarto
    
    if (validacion.tipo === 'titular') {
      alert(`${nombreCompleto} ya está registrado como TITULAR en la habitación ${habitacion}.\n\nNo puede agregarse como acompañante.`)
    } else {
      alert(`${nombreCompleto} ya está registrado como ACOMPAÑANTE en la habitación ${habitacion}.\n\nNo puede agregarse nuevamente.`)
    }
    return
  }

  checkinStore.addAcompananteToGrupo({
    grupoId: acompananteForm.grupo_id,
    acompanante: { ...selectedPersona.value, parentesco: acompananteForm.parentesco }
  })
  
  // Eliminar la persona de los resultados
  searchResults.value = searchResults.value.filter(p => p.id !== selectedPersona.value!.id)
  
  // Cerrar modal y resetear
  showActionModal.value = false
  selectedPersona.value = null
  selectedAction.value = null
}

function handlePersonaSaved(persona: PersonaState) {
  showPersonaForm.value = false
  // Añade la nueva persona a los resultados de búsqueda
  searchResults.value.unshift(persona)
  searchPerformed.value = true
  // Seleccionar automáticamente a la persona recién creada
  selectPersona(persona)
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

function closeModal() {
  showActionModal.value = false
  selectedPersona.value = null
  selectedAction.value = null
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
          <Input 
            placeholder="Nro. Documento" 
            v-model="searchQuery.nro_documento" 
            @input="(e: Event) => searchQuery.nro_documento = sanitizeAlphanumeric((e.target as HTMLInputElement).value)" 
            @keyup.enter="handleSearch"
          />
          <Input 
            placeholder="Nombres" 
            v-model="searchQuery.nombres" 
            @input="(e: Event) => searchQuery.nombres = sanitizeLettersOnly((e.target as HTMLInputElement).value)" 
            @keyup.enter="handleSearch"
          />
          <Input 
            placeholder="Apellido Paterno" 
            v-model="searchQuery.apellido_paterno" 
            @input="(e: Event) => searchQuery.apellido_paterno = sanitizeLettersOnly((e.target as HTMLInputElement).value)" 
            @keyup.enter="handleSearch"
          />
        </div>
        <div class="flex gap-2">
          <Button @click="handleSearch" :disabled="isLoading">
            <Loader2 v-if="isLoading" class="w-4 h-4 mr-2 animate-spin" />
            Buscar
          </Button>
          <Button variant="outline" @click="resetSearch" v-if="searchPerformed">
            Limpiar
          </Button>
          <Button variant="link" @click="showPersonaForm = true">O registrar nueva persona</Button>
        </div>
      </CardContent>
    </Card>

    <!-- SECCIÓN 3: RESULTADOS DE BÚSQUEDA (CARDS) -->
    <div v-if="searchResults.length > 0" class="mt-6">
      <h3 class="text-lg font-medium mb-4">Resultados de la Búsqueda ({{ searchResults.length }})</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card 
          v-for="persona in searchResults" 
          :key="persona.id"
          class="cursor-pointer hover:shadow-lg transition-shadow"
          @click="selectPersona(persona)"
        >
          <CardHeader>
            <CardTitle class="text-base">{{ persona.nombres }} {{ persona.apellido_paterno }}</CardTitle>
            <CardDescription>
              <div class="text-sm">Doc: {{ persona.nro_documento }}</div>
              <div class="text-xs text-muted-foreground" v-if="persona.apellido_materno">
                {{ persona.apellido_materno }}
              </div>
            </CardDescription>
          </CardHeader>
        </Card>
      </div>
    </div>

    <!-- Mensaje si no hay resultados -->
    <div v-else-if="searchPerformed && !isLoading" class="mt-6 text-center py-12 border-2 border-dashed rounded-lg">
      <p class="text-gray-500">No se encontraron personas con los criterios de búsqueda.</p>
    </div>

    <!-- MODAL DE ACCIÓN -->
    <Dialog :open="showActionModal" @update:open="closeModal">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ selectedPersona?.nombres }} {{ selectedPersona?.apellido_paterno }}</DialogTitle>
          <DialogDescription>Doc: {{ selectedPersona?.nro_documento }}</DialogDescription>
        </DialogHeader>

        <!-- Selección de acción -->
        <div v-if="!selectedAction" class="space-y-3 py-4">
          <Button 
            @click="selectedAction = 'crear_grupo'" 
            variant="outline" 
            class="w-full h-auto py-4 flex flex-col items-center gap-2"
          >
            <BedDouble class="h-6 w-6" />
            <div class="text-center">
              <div class="font-semibold">Crear Grupo como Titular</div>
              <div class="text-xs text-muted-foreground">Asignar habitación y crear nuevo grupo</div>
            </div>
          </Button>
          
          <Button 
            @click="selectedAction = 'agregar_acompanante'" 
            variant="outline" 
            class="w-full h-auto py-4 flex flex-col items-center gap-2"
            :disabled="checkinStore.grupos.length === 0"
          >
            <Users class="h-6 w-6" />
            <div class="text-center">
              <div class="font-semibold">Agregar como Acompañante</div>
              <div class="text-xs text-muted-foreground">
                {{ checkinStore.grupos.length === 0 ? 'Debe crear un grupo primero' : 'Agregar a un grupo existente' }}
              </div>
            </div>
          </Button>
        </div>

        <!-- Formulario: Crear Grupo -->
        <div v-if="selectedAction === 'crear_grupo'" class="space-y-4 py-4">
          <div class="space-y-2">
            <Label for="tipo_cuarto">Tipo de Cuarto</Label>
            <Select v-model="grupoForm.tipo_cuarto_id">
              <SelectTrigger id="tipo_cuarto">
                <SelectValue placeholder="Seleccione tipo de cuarto" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="tipo in props.tipoCuartos" 
                  :key="tipo.id" 
                  :value="String(tipo.id)"
                >
                  {{ tipo.nombre }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground" v-if="props.tipoCuartos.length === 0">
              No hay tipos de cuarto disponibles
            </p>
          </div>

          <div class="space-y-2">
            <Label for="nro_cuarto">Número de Habitación</Label>
            <Input 
              id="nro_cuarto" 
              v-model="grupoForm.nro_cuarto" 
              placeholder="Ej: 101" 
            />
          </div>

          <DialogFooter>
            <Button variant="outline" @click="selectedAction = null">Atrás</Button>
            <Button @click="handleCreateGrupo">Crear Grupo</Button>
          </DialogFooter>
        </div>

        <!-- Formulario: Agregar Acompañante -->
        <div v-if="selectedAction === 'agregar_acompanante'" class="space-y-4 py-4">
          <div class="space-y-2">
            <Label for="parentesco">Parentesco</Label>
            <Select v-model="acompananteForm.parentesco">
              <SelectTrigger id="parentesco">
                <SelectValue placeholder="Seleccione parentesco" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="hijo">Hijo/a</SelectItem>
                <SelectItem value="sobrino">Sobrino/a</SelectItem>
                <SelectItem value="hermano">Hermano/a</SelectItem>
                <SelectItem value="nieto">Nieto/a</SelectItem>
                <SelectItem value="apoderado">Apoderado</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="grupo">Seleccionar Grupo</Label>
            <Select v-model="acompananteForm.grupo_id">
              <SelectTrigger id="grupo">
                <SelectValue placeholder="Seleccione grupo" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="g in checkinStore.grupos" :key="g.id" :value="g.id">
                  Hab. {{ g.nro_cuarto }} ({{ g.titular.nombres }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <DialogFooter>
            <Button variant="outline" @click="selectedAction = null">Atrás</Button>
            <Button @click="handleAddAcompanante">Agregar</Button>
          </DialogFooter>
        </div>

        <!-- Botón cancelar si no hay acción seleccionada -->
        <DialogFooter v-if="!selectedAction">
          <Button variant="outline" @click="closeModal">Cancelar</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Formulario para nueva persona -->
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
