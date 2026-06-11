<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    postbacks: { type: Object, required: true },
    partners: { type: Array, default: () => [] },
    offers: { type: Array, default: () => [] },
});

const form = useForm({
    partner_id: '',
    offer_id: '',
    url: '',
    type: 'global',
    is_active: true,
});

const submit = () => {
    form.post('/postbacks', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout title="Postbacks">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo postback</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner *</label>
                        <select v-model="form.partner_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tipo *</label>
                        <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="global">Global</option>
                            <option value="offer">Por oferta</option>
                        </select>
                    </div>
                    <div v-if="form.type === 'offer'">
                        <label class="block text-xs font-medium text-gray-700">Oferta</label>
                        <select v-model="form.offer_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="o in offers" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="block text-xs font-medium text-gray-700">URL *</label>
                        <input v-model="form.url" type="url" required placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Activo
                    </label>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear postback
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">URL</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Activo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="postback in postbacks.data" :key="postback.id">
                            <td class="px-4 py-3 text-gray-900">{{ postback.partner?.name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ postback.type }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ postback.offer?.name || '—' }}</td>
                            <td class="max-w-xs truncate px-4 py-3 font-mono text-xs text-gray-600">{{ postback.url }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="postback.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                    {{ postback.is_active ? 'Sí' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!postbacks.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay postbacks</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
