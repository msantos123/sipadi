<script setup lang="ts">
import { defineProps, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';

const props = defineProps({
    form: Object,
    permissions: Array,
    isEdit: Boolean,
    submitRoute: String,
});

const localForm = useForm({
    name: props.form?.name || '',
    permissions: props.form?.permissions || [],
});

// Sync props with local state when they change (for edit mode)
watch(() => props.form, (newForm) => {
    if (newForm) {
        localForm.name = newForm.name;
        localForm.permissions = newForm.permissions;
    }
}, { deep: true, immediate: true });

const submit = () => {
    if (props.isEdit) {
        localForm.put(props.submitRoute, {
            preserveScroll: true,
        });
    } else {
        localForm.post(props.submitRoute, {
            preserveScroll: true,
        });
    }
};

</script>

<template>
    <form @submit.prevent="submit">
        <div class="shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 bg-white sm:p-6">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Rol</label>
                        <input v-model="localForm.name" type="text" name="name" id="name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        <InputError :message="localForm.errors.name" class="mt-2" />
                    </div>

                    <div class="col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Permisos</label>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div v-for="permission in permissions" :key="permission.id" class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input :id="`permission-${permission.id}`" :value="permission" v-model="localForm.permissions" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label :for="`permission-${permission.id}`" class="font-medium text-gray-700">{{ permission.name }}</label>
                                </div>
                            </div>
                        </div>
                        <InputError :message="localForm.errors.permissions" class="mt-2" />
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                <button type="submit" :disabled="localForm.processing" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Guardar
                </button>
            </div>
        </div>
    </form>
</template>
