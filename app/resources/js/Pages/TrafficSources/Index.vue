<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    sources: { type: Object, required: true },
    partners: { type: Array, default: () => [] },
});

const form = useForm({
    partner_id: '',
    name: '',
    is_blocked: false,
});

const submit = () => {
    form.post('/traffic-sources', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout title="Traffic Sources">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nueva traffic source</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner *</label>
                        <select v-model="form.partner_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nombre *</label>
                        <input v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <label class="flex items-end gap-2 pb-2 text-sm text-gray-700">
                        <input v-model="form.is_blocked" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Bloqueada
                    </label>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear source
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Source ID</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="source in sources.data" :key="source.id">
                            <td class="px-4 py-3 font-mono text-xs text-indigo-600">{{ source.source_id }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ source.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ source.partner?.name || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="source.is_blocked ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                                    {{ source.is_blocked ? 'Bloqueada' : 'Activa' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!sources.data?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay traffic sources</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
