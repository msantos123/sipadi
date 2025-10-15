
import { ref, computed } from 'vue'

interface UseWizardOptions {
  steps: string[]
  initialStep?: number
}

export function useWizard({ steps, initialStep = 0 }: UseWizardOptions) {
  const currentStepIndex = ref(initialStep)

  const currentStep = computed(() => steps[currentStepIndex.value])

  const isFirstStep = computed(() => currentStepIndex.value === 0)
  const isLastStep = computed(() => currentStepIndex.value === steps.length - 1)

  const nextStep = () => {
    if (!isLastStep.value) {
      currentStepIndex.value++
    }
  }

  const prevStep = () => {
    if (!isFirstStep.value) {
      currentStepIndex.value--
    }
  }

  const goToStep = (stepIndex: number) => {
    if (stepIndex >= 0 && stepIndex < steps.length) {
      currentStepIndex.value = stepIndex
    }
  }

  const resetWizard = () => {
    currentStepIndex.value = initialStep
  }

  return {
    steps,
    currentStepIndex,
    currentStep,
    isFirstStep,
    isLastStep,
    nextStep,
    prevStep,
    goToStep,
    resetWizard,
  }
}
