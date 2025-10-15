
import { ref } from 'vue'

// Interfaz para el estado del store base
export interface BaseStoreState<T> {
  items: T[]
  currentItem: T | null
  loading: boolean
  error: string | null
}

// Función para crear un store base
export function createBaseStore<T>() {
  const state = ref<BaseStoreState<T>>({
    items: [],
    currentItem: null,
    loading: false,
    error: null,
  })

  const setLoading = (loading: boolean) => {
    state.value.loading = loading
  }

  const setError = (error: string | null) => {
    state.value.error = error
  }

  const setItems = (items: T[]) => {
    state.value.items = items
  }

  const setCurrentItem = (item: T | null) => {
    state.value.currentItem = item
  }

  const addItem = (item: T) => {
    state.value.items.push(item)
  }

  const updateItem = (id: keyof T, updatedItem: T) => {
    const index = state.value.items.findIndex(item => item[id] === updatedItem[id])
    if (index !== -1) {
      state.value.items[index] = updatedItem
    }
  }

  const removeItem = (id: keyof T, itemId: any) => {
    state.value.items = state.value.items.filter(item => item[id] !== itemId)
  }

  return {
    state,
    setLoading,
    setError,
    setItems,
    setCurrentItem,
    addItem,
    updateItem,
    removeItem,
  }
}
