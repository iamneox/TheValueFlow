<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    adjustments: { type: Object, required: true },
    offers: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
});

const form = useForm({
    offer_id: '',
    partner_id: '',
    metric: 'clicks',
    value: 0,
    transaction_id: '',
    method: 'manual',
    reason: '',
});

const submit = () => {
    form.post('/adjustments', {
        onSuccess: () => form.reset(),
    });
};

const fmt = (n) => new Intl.NumberFormat('es-ES', { maximumFractionDigits: 4 }).format(n ?? 0);
</script>

<template>
    <AppLayout title="Adjustments">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo ajuste</h2>
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
                        <label class="block text-xs font-medium text-gray-700">Métrica *</label>
                        <select v-model="form.metric" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="clicks">Clicks</option>
                            <option value="leads">Leads</option>
                            <option value="revenue">Revenue</option>
                            <option value="payout">Payout</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Valor *</label>
                        <input v-model="form.value" type="number" step="0.0001" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Método *</label>
                        <select v-model="form.method" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="manual">Manual</option>
                            <option value="transaction_id">Transaction ID</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Transaction ID</label>
                        <input v-model="form.transaction_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Motivo</label>
                        <input v-model="form.reason" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear ajuste
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Métrica</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Valor</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Método</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="adj in adjustments.data" :key="adj.id">
                            <td class="px-4 py-3 text-gray-900">{{ adj.offer?.name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ adj.partner?.name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ adj.metric }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">{{ fmt(adj.value) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ adj.method }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ new Date(adj.created_at).toLocaleDateString('es-ES') }}</td>
                        </tr>
                        <tr v-if="!adjustments.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay ajustes</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
