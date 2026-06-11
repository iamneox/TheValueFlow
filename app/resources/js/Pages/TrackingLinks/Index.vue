<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    links: { type: Object, required: true },
    offers: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
});

const form = useForm({
    offer_id: '',
    partner_id: '',
    traffic_source_id: '',
    aff_sub1: '',
    aff_sub2: '',
    aff_sub3: '',
    aff_sub4: '',
    aff_sub5: '',
});

const submit = () => {
    form.post('/tracking-links', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout title="Tracking Links">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Generar tracking link</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Oferta *</label>
                        <select v-model="form.offer_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="o in offers" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner *</label>
                        <select v-model="form.partner_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Traffic source</label>
                        <select v-model="form.traffic_source_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Ninguna</option>
                            <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }} ({{ s.partner?.name }})</option>
                        </select>
                    </div>
                    <div v-for="i in 5" :key="i">
                        <label class="block text-xs font-medium text-gray-700">aff_sub{{ i }}</label>
                        <input v-model="form[`aff_sub${i}`]" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Generar link
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Dominio</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">URL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="link in links.data" :key="link.id">
                            <td class="px-4 py-3 text-gray-900">{{ link.offer?.name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ link.partner?.name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ link.tracking_domain?.domain || '—' }}</td>
                            <td class="max-w-xs truncate px-4 py-3 font-mono text-xs text-indigo-600">{{ link.url }}</td>
                        </tr>
                        <tr v-if="!links.data?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay tracking links</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
