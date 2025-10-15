
<script setup lang="ts">
import { useSearch } from '@/composables/useSearch'
import { usePersonaStore } from '@/stores/personaStore'
import type { Persona } from '@/types'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

const emit = defineEmits<{(e: 'persona-selected', persona: Persona): void }>()

const personaStore = usePersonaStore()

const { searchQuery, searchResults, loading, error } = useSearch<Persona>({
  searchFn: (query) => personaStore.searchPersonasByDocumento(query),
})

function selectPersona(persona: Persona) {
  emit('persona-selected', persona)
  searchResults.value = []
  searchQuery.value = ''
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Buscar Persona Titular</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="flex w-full max-w-sm items-center space-x-2">
        <Input
          v-model="searchQuery"
          type="text"
          placeholder="Número de documento"
          class="flex-grow"
        />
        <Button type="submit" :disabled="loading">
          <span v-if="!loading">Buscar</span>
          <span v-else>Buscando...</span>
        </Button>
      </div>

      <div v-if="error" class="mt-4 text-red-500">
        {{ error }}
      </div>

      <div v-if="searchResults.length > 0" class="mt-4 border-t pt-4">
        <ul class="space-y-2">
          <li
            v-for="persona in searchResults"
            :key="persona.id"
            class="flex cursor-pointer items-center justify-between rounded-md p-2 hover:bg-gray-100"
            @click="selectPersona(persona)"
          >
            <span>{{ persona.nombres }} {{ persona.apellido_paterno }} {{ persona.apellido_materno }}</span>
            <span class="text-sm text-gray-500">{{ persona.nro_documento }}</span>
          </li>
        </ul>
      </div>
    </CardContent>
  </Card>
</template>
