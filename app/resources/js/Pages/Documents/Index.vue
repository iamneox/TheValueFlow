<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    documents: { type: Object, required: true },
    partners: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});

const form = useForm({
    entity_type: 'partner',
    entity_id: '',
    name: '',
    type: '',
    file: null,
});

const entities = computed(() =>
    form.entity_type === 'partner' ? props.partners : props.clients
);

const submit = () => {
    form.post('/documents', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            form.entity_type = 'partner';
        },
    });
};

const onFileChange = (e) => {
    form.file = e.target.files[0] ?? null;
};
</script>

<template>
    <AppLayout title="Documents">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Subir documento</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tipo entidad *</label>
                        <select v-model="form.entity_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="partner">Partner</option>
                            <option value="client">Anunciante</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Entidad *</label>
                        <select v-model="form.entity_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="e in entities" :key="e.id" :value="e.id">
                                {{ form.entity_type === 'partner' ? e.name : e.company }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nombre *</label>
                        <input v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tipo</label>
                        <input v-model="form.type" placeholder="contrato, KYC…" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Archivo *</label>
                        <input type="file" required class="mt-1 block w-full text-sm text-gray-500" @change="onFileChange" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Subir documento
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Descargar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="doc in documents.data" :key="doc.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ doc.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ doc.type || '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ new Date(doc.created_at).toLocaleDateString('es-ES') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a :href="`/documents/${doc.id}/download`" class="text-indigo-600 hover:text-indigo-500">Descargar</a>
                            </td>
                        </tr>
                        <tr v-if="!documents.data?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay documentos</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
