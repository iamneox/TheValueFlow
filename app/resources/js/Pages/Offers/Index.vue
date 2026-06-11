<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    offers: { type: Object, required: true },
    clients: { type: Array, default: () => [] },
    domains: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    client_id: '',
    type: 'CPA',
    category: '',
    country: '',
    revenue: 0,
    payout: 0,
    status: 'pending',
    tracking_domain_id: '',
});

const submit = () => {
    form.post('/offers', {
        onSuccess: () => form.reset(),
    });
};

const fmt = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n ?? 0);
</script>

<template>
    <AppLayout title="Ofertas">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nueva oferta</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nombre *</label>
                        <input v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Anunciante *</label>
                        <select v-model="form.client_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tipo *</label>
                        <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="CPL">CPL</option>
                            <option value="CPC">CPC</option>
                            <option value="CPM">CPM</option>
                            <option value="CPA">CPA</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Estado *</label>
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Activo</option>
                            <option value="pending">Pendiente</option>
                            <option value="paused">Pausado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Revenue *</label>
                        <input v-model="form.revenue" type="number" step="0.0001" min="0" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Payout *</label>
                        <input v-model="form.payout" type="number" step="0.0001" min="0" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Dominio tracking</label>
                        <select v-model="form.tracking_domain_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Ninguno</option>
                            <option v-for="d in domains" :key="d.id" :value="d.id">{{ d.domain }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">País</label>
                        <input v-model="form.country" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear oferta
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Anunciante</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Payout</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="offer in offers.data" :key="offer.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ offer.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ offer.client?.company || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ offer.type }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ fmt(offer.revenue) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ fmt(offer.payout) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ offer.status }}</td>
                        </tr>
                        <tr v-if="!offers.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay ofertas</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
