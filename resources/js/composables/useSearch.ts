
import { ref, watch } from 'vue'

interface UseSearchOptions<T> {
  searchFn: (query: string) => Promise<T[]>
  debounceMs?: number
}

export function useSearch<T>({ searchFn, debounceMs = 500 }: UseSearchOptions<T>) {
  const searchQuery = ref('')
  const searchResults = ref<T[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  let debounceTimer: number | undefined

  watch(searchQuery, (newQuery) => {
    clearTimeout(debounceTimer)
    if (newQuery.trim() === '') {
      searchResults.value = []
      return
    }

    debounceTimer = setTimeout(async () => {
      loading.value = true
      error.value = null
      try {
        searchResults.value = await searchFn(newQuery)
      } catch (e) {
        error.value = 'La búsqueda ha fallado.'
        searchResults.value = []
      } finally {
        loading.value = false
      }
    }, debounceMs)
  })

  return {
    searchQuery,
    searchResults,
    loading,
    error,
  }
}
