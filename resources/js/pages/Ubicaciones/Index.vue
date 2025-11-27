<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CirclePlus, Pencil, Trash2, Search } from 'lucide-vue-next';

interface Nacionalidad {
    id: number;
    pais: string;
    gentilicio: string;
    codigo_nacionalidad?: string;
}

interface Departamento {
    id: number;
    nombre: string;
    sigla?: string;
}

interface Municipio {
    id: number;
    nombre_municipio: string;
    departamento_id: number;
    departamento?: Departamento;
    codigo_municipio?: string;
}

interface Props {
    nacionalidades: Nacionalidad[];
    departamentos: Departamento[];
    municipios: Municipio[];
}

const props = defineProps<Props>();

// Estados para búsqueda
const searchNacionalidad = ref('');
const searchDepartamento = ref('');
const searchMunicipio = ref('');

// Estados para modales
const showNacionalidadModal = ref(false);
const showDepartamentoModal = ref(false);
const showMunicipioModal = ref(false);

// Estados para edición
const editingNacionalidad = ref<Nacionalidad | null>(null);
const editingDepartamento = ref<Departamento | null>(null);
const editingMunicipio = ref<Municipio | null>(null);

// Formularios
const nacionalidadForm = useForm({
    pais: '',
    gentilicio: '',
    codigo_nacionalidad: '',
});

const departamentoForm = useForm({
    nombre: '',
    sigla: '',
});

const municipioForm = useForm({
    nombre_municipio: '',
    departamento_id: '',
    codigo_municipio: '',
});

// Computed para filtrar
const filteredNacionalidades = computed(() => {
    const search = searchNacionalidad.value.toLowerCase();
    return props.nacionalidades.filter(n => 
        n.pais.toLowerCase().includes(search) || 
        n.gentilicio.toLowerCase().includes(search)
    );
});

const filteredDepartamentos = computed(() => {
    const search = searchDepartamento.value.toLowerCase();
    return props.departamentos.filter(d => 
        d.nombre.toLowerCase().includes(search)
    );
});

const filteredMunicipios = computed(() => {
    const search = searchMunicipio.value.toLowerCase();
    return props.municipios.filter(m => 
        m.nombre_municipio.toLowerCase().includes(search) ||
        (m.departamento?.nombre?.toLowerCase().includes(search) ?? false)
    );
});

// ========== VALIDACIONES ==========

// Solo letras y espacios
const validateLettersOnly = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
};

// Solo mayúsculas
const validateUppercase = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase();
};

// Solo mayúsculas y letras (para municipios)
const validateUppercaseLetters = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase().replace(/[^A-ZÁÉÍÓÚÑ\s]/g, '');
};

// Código de nacionalidad: 3 letras mayúsculas
const validateCodigoNacionalidad = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase().replace(/[^A-Z]/g, '').substring(0, 3);
};

// Sigla departamento: 2 letras mayúsculas
const validateSigla = (event: Event) => {
    const input = event.target as HTMLInputElement;
    input.value = input.value.trim().toUpperCase().replace(/[^A-Z]/g, '').substring(0, 2);
};

// ========== NACIONALIDADES ==========

const openNacionalidadModal = (nacionalidad?: Nacionalidad) => {
    if (nacionalidad) {
        editingNacionalidad.value = nacionalidad;
        nacionalidadForm.pais = nacionalidad.pais;
        nacionalidadForm.gentilicio = nacionalidad.gentilicio;
        nacionalidadForm.codigo_nacionalidad = nacionalidad.codigo_nacionalidad || '';
    } else {
        editingNacionalidad.value = null;
        nacionalidadForm.reset();
    }
    showNacionalidadModal.value = true;
};

const saveNacionalidad = () => {
    // Limpiar y forzar mayúsculas en código
    nacionalidadForm.pais = nacionalidadForm.pais.trim();
    nacionalidadForm.gentilicio = nacionalidadForm.gentilicio.trim();
    nacionalidadForm.codigo_nacionalidad = nacionalidadForm.codigo_nacionalidad.trim().toUpperCase();
    
    if (editingNacionalidad.value) {
        nacionalidadForm.put(`/ubicaciones/nacionalidades/${editingNacionalidad.value.id}`, {
            onSuccess: () => {
                showNacionalidadModal.value = false;
                Swal.fire('¡Éxito!', 'Nacionalidad actualizada', 'success');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                const errorMessages = Object.values(errors).flat().join('\n');
                Swal.fire('Error', errorMessages || 'No se pudo actualizar la nacionalidad', 'error');
            }
        });
    } else {
        nacionalidadForm.post('/ubicaciones/nacionalidades', {
            onSuccess: () => {
                showNacionalidadModal.value = false;
                Swal.fire('¡Éxito!', 'Nacionalidad creada', 'success');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                const errorMessages = Object.values(errors).flat().join('\n');
                Swal.fire('Error', errorMessages || 'No se pudo crear la nacionalidad', 'error');
            }
        });
    }
};

const deleteNacionalidad = (id: number) => {
    Swal.fire({
        title: '¿Eliminar nacionalidad?',
        text: 'Esta acción no se puede revertir',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/ubicaciones/nacionalidades/${id}`, {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'Nacionalidad eliminada', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'No se pudo eliminar', 'error');
                }
            });
        }
    });
};

// ========== DEPARTAMENTOS ==========

const openDepartamentoModal = (departamento?: Departamento) => {
    if (departamento) {
        editingDepartamento.value = departamento;
        departamentoForm.nombre = departamento.nombre;
        departamentoForm.sigla = departamento.sigla || '';
    } else {
        editingDepartamento.value = null;
        departamentoForm.reset();
    }
    showDepartamentoModal.value = true;
};

const saveDepartamento = () => {
    // Limpiar espacios en blanco y forzar mayúsculas
    departamentoForm.nombre = departamentoForm.nombre.trim();
    departamentoForm.sigla = departamentoForm.sigla.trim().toUpperCase();
    
    console.log('Enviando departamento:', {
        nombre: departamentoForm.nombre,
        sigla: departamentoForm.sigla,
        sigla_length: departamentoForm.sigla.length
    });
    
    if (editingDepartamento.value) {
        departamentoForm.put(`/ubicaciones/departamentos/${editingDepartamento.value.id}`, {
            onSuccess: () => {
                showDepartamentoModal.value = false;
                Swal.fire('¡Éxito!', 'Departamento actualizado', 'success');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                const errorMessages = Object.values(errors).flat().join('\n');
                Swal.fire('Error', errorMessages || 'No se pudo actualizar el departamento', 'error');
            }
        });
    } else {
        departamentoForm.post('/ubicaciones/departamentos', {
            onSuccess: () => {
                showDepartamentoModal.value = false;
                Swal.fire('¡Éxito!', 'Departamento creado', 'success');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                const errorMessages = Object.values(errors).flat().join('\n');
                Swal.fire('Error', errorMessages || 'No se pudo crear el departamento', 'error');
            }
        });
    }
};

const deleteDepartamento = (id: number) => {
    Swal.fire({
        title: '¿Eliminar departamento?',
        text: 'Esta acción no se puede revertir',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/ubicaciones/departamentos/${id}`, {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'Departamento eliminado', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'No se pudo eliminar', 'error');
                }
            });
        }
    });
};

// ========== MUNICIPIOS ==========

const openMunicipioModal = (municipio?: Municipio) => {
    if (municipio) {
        editingMunicipio.value = municipio;
        municipioForm.nombre_municipio = municipio.nombre_municipio;
        municipioForm.departamento_id = municipio.departamento_id.toString();
        municipioForm.codigo_municipio = municipio.codigo_municipio || '';
    } else {
        editingMunicipio.value = null;
        municipioForm.reset();
    }
    showMunicipioModal.value = true;
};

const saveMunicipio = () => {
    // Limpiar y forzar mayúsculas en nombre
    municipioForm.nombre_municipio = municipioForm.nombre_municipio.trim().toUpperCase();
    municipioForm.codigo_municipio = municipioForm.codigo_municipio.trim();
    
    if (editingMunicipio.value) {
        municipioForm.put(`/ubicaciones/municipios/${editingMunicipio.value.id}`, {
            onSuccess: () => {
                showMunicipioModal.value = false;
                Swal.fire('¡Éxito!', 'Municipio actualizado', 'success');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                const errorMessages = Object.values(errors).flat().join('\n');
                Swal.fire('Error', errorMessages || 'No se pudo actualizar el municipio', 'error');
            }
        });
    } else {
        municipioForm.post('/ubicaciones/municipios', {
            onSuccess: () => {
                showMunicipioModal.value = false;
                Swal.fire('¡Éxito!', 'Municipio creado', 'success');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                const errorMessages = Object.values(errors).flat().join('\n');
                Swal.fire('Error', errorMessages || 'No se pudo crear el municipio', 'error');
            }
        });
    }
};

const deleteMunicipio = (id: number) => {
    Swal.fire({
        title: '¿Eliminar municipio?',
        text: 'Esta acción no se puede revertir',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/ubicaciones/municipios/${id}`, {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'Municipio eliminado', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'No se pudo eliminar', 'error');
                }
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Gestión de Ubicaciones</h1>
            </div>

            <Tabs default-value="nacionalidades" class="w-full">
                <TabsList class="grid w-full grid-cols-3">
                    <TabsTrigger value="nacionalidades">Nacionalidades</TabsTrigger>
                    <TabsTrigger value="departamentos">Departamentos</TabsTrigger>
                    <TabsTrigger value="municipios">Municipios</TabsTrigger>
                </TabsList>

                <!-- TAB NACIONALIDADES -->
                <TabsContent value="nacionalidades" class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="relative w-full max-w-sm">
                            <Input v-model="searchNacionalidad" placeholder="Buscar nacionalidad..." class="pl-10" />
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        </div>
                        <Button @click="openNacionalidadModal()" class="bg-indigo-500 hover:bg-indigo-700">
                            <CirclePlus class="mr-2 h-4 w-4" />
                            Nueva Nacionalidad
                        </Button>
                    </div>

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>País</TableHead>
                                    <TableHead>Gentilicio</TableHead>
                                    <TableHead>Código</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="filteredNacionalidades.length === 0">
                                    <TableCell colspan="5" class="text-center">No hay nacionalidades</TableCell>
                                </TableRow>
                                <TableRow v-for="nac in filteredNacionalidades" :key="nac.id">
                                    <TableCell>{{ nac.id }}</TableCell>
                                    <TableCell>{{ nac.pais }}</TableCell>
                                    <TableCell>{{ nac.gentilicio }}</TableCell>
                                    <TableCell>{{ nac.codigo_nacionalidad || 'N/A' }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button @click="openNacionalidadModal(nac)" size="sm" variant="outline">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button @click="deleteNacionalidad(nac.id)" size="sm" variant="destructive">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>

                <!-- TAB DEPARTAMENTOS -->
                <TabsContent value="departamentos" class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="relative w-full max-w-sm">
                            <Input v-model="searchDepartamento" placeholder="Buscar departamento..." class="pl-10" />
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        </div>
                        <Button @click="openDepartamentoModal()" class="bg-indigo-500 hover:bg-indigo-700">
                            <CirclePlus class="mr-2 h-4 w-4" />
                            Nuevo Departamento
                        </Button>
                    </div>

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="filteredDepartamentos.length === 0">
                                    <TableCell colspan="3" class="text-center">No hay departamentos</TableCell>
                                </TableRow>
                                <TableRow v-for="dep in filteredDepartamentos" :key="dep.id">
                                    <TableCell>{{ dep.id }}</TableCell>
                                    <TableCell>{{ dep.nombre }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button @click="openDepartamentoModal(dep)" size="sm" variant="outline">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button @click="deleteDepartamento(dep.id)" size="sm" variant="destructive">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>

                <!-- TAB MUNICIPIOS -->
                <TabsContent value="municipios" class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="relative w-full max-w-sm">
                            <Input v-model="searchMunicipio" placeholder="Buscar municipio..." class="pl-10" />
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        </div>
                        <Button @click="openMunicipioModal()" class="bg-indigo-500 hover:bg-indigo-700">
                            <CirclePlus class="mr-2 h-4 w-4" />
                            Nuevo Municipio
                        </Button>
                    </div>

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Departamento</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="filteredMunicipios.length === 0">
                                    <TableCell colspan="4" class="text-center">No hay municipios</TableCell>
                                </TableRow>
                                <TableRow v-for="mun in filteredMunicipios" :key="mun.id">
                                    <TableCell>{{ mun.id }}</TableCell>
                                    <TableCell>{{ mun.nombre_municipio }}</TableCell>
                                    <TableCell>{{ mun.departamento?.nombre || 'N/A' }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button @click="openMunicipioModal(mun)" size="sm" variant="outline">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button @click="deleteMunicipio(mun.id)" size="sm" variant="destructive">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <!-- MODAL NACIONALIDAD -->
        <Dialog v-model:open="showNacionalidadModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingNacionalidad ? 'Editar' : 'Nueva' }} Nacionalidad</DialogTitle>
                    <DialogDescription>Complete los datos de la nacionalidad</DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="pais">País</Label>
                        <Input id="pais" v-model="nacionalidadForm.pais" @input="validateLettersOnly" />
                        <span class="text-xs text-muted-foreground">Solo letras</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="gentilicio">Gentilicio</Label>
                        <Input id="gentilicio" v-model="nacionalidadForm.gentilicio" @input="validateLettersOnly" />
                        <span class="text-xs text-muted-foreground">Solo letras</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="codigo">Código <span class="text-red-500">*</span></Label>
                        <Input id="codigo" v-model="nacionalidadForm.codigo_nacionalidad" @input="validateCodigoNacionalidad" maxlength="3" placeholder="Ej: BOL" />
                        <span class="text-xs text-muted-foreground">3 letras mayúsculas (Ej: BOL, ARG, BRA)</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showNacionalidadModal = false">Cancelar</Button>
                    <Button @click="saveNacionalidad" :disabled="nacionalidadForm.processing">Guardar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL DEPARTAMENTO -->
        <Dialog v-model:open="showDepartamentoModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingDepartamento ? 'Editar' : 'Nuevo' }} Departamento</DialogTitle>
                    <DialogDescription>Complete los datos del departamento</DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="dep-nombre">Nombre</Label>
                        <Input id="dep-nombre" v-model="departamentoForm.nombre" @input="validateLettersOnly" />
                        <span class="text-xs text-muted-foreground">Solo letras</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="dep-sigla">Sigla <span class="text-red-500">*</span></Label>
                        <Input id="dep-sigla" v-model="departamentoForm.sigla" @input="validateSigla" maxlength="2" placeholder="Ej: LP" />
                        <span class="text-xs text-muted-foreground">2 letras mayúsculas (Ej: LP, CB, SC)</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showDepartamentoModal = false">Cancelar</Button>
                    <Button @click="saveDepartamento" :disabled="departamentoForm.processing">Guardar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODAL MUNICIPIO -->
        <Dialog v-model:open="showMunicipioModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingMunicipio ? 'Editar' : 'Nuevo' }} Municipio</DialogTitle>
                    <DialogDescription>Complete los datos del municipio</DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="mun-nombre">Nombre</Label>
                        <Input id="mun-nombre" v-model="municipioForm.nombre_municipio" @input="validateUppercaseLetters" />
                        <span class="text-xs text-muted-foreground">Solo letras mayúsculas</span>
                    </div>
                    <div class="grid gap-2">
                        <Label for="mun-codigo">Código <span class="text-red-500">*</span></Label>
                        <Input id="mun-codigo" v-model="municipioForm.codigo_municipio" maxlength="10" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="mun-dep">Departamento</Label>
                        <select
                            id="mun-dep"
                            v-model="municipioForm.departamento_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Seleccionar departamento</option>
                            <option v-for="dep in departamentos" :key="dep.id" :value="dep.id">
                                {{ dep.nombre }}
                            </option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showMunicipioModal = false">Cancelar</Button>
                    <Button @click="saveMunicipio" :disabled="municipioForm.processing">Guardar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
