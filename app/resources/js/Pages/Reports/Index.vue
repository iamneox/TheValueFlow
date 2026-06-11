<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    byOffer: { type: Array, default: () => [] },
    byPartner: { type: Array, default: () => [] },
    offers: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});

const form = useForm({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    offer_id: props.filters.offer_id ?? '',
    partner_id: props.filters.partner_id ?? '',
    client_id: props.filters.client_id ?? '',
    country: props.filters.country ?? '',
    device: props.filters.device ?? '',
    os: props.filters.os ?? '',
    browser: props.filters.browser ?? '',
    city: props.filters.city ?? '',
});

const submit = () => {
    form.get('/reports', { preserveState: true });
};

const exportUrl = (format) => {
    const params = new URLSearchParams({ ...form.data(), format });
    return `/reports/export?${params.toString()}`;
};

const fmt = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n ?? 0);

const fmtNum = (n) => new Intl.NumberFormat('es-ES').format(n ?? 0);
</script>

<template>
    <AppLayout title="Reports">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Filtros</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Desde</label>
                        <input v-model="form.from" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Hasta</label>
                        <input v-model="form.to" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Oferta</label>
                        <select v-model="form.offer_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todas</option>
                            <option v-for="o in offers" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner</label>
                        <select v-model="form.partner_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Anunciante</label>
                        <select v-model="form.client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">País</label>
                        <input v-model="form.country" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Device</label>
                        <input v-model="form.device" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">OS</label>
                        <input v-model="form.os" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                        Aplicar filtros
                    </button>
                    <a :href="exportUrl('csv')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Export CSV</a>
                    <a :href="exportUrl('xlsx')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Export XLSX</a>
                    <a :href="exportUrl('pdf')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Export PDF</a>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Impresiones</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ fmtNum(summary.impressions) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Clicks</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ fmtNum(summary.gross_clicks) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Conversiones</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ fmtNum(summary.conversions) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Revenue</p>
                    <p class="mt-1 text-xl font-bold text-indigo-600">{{ fmt(summary.revenue) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Payout</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ fmt(summary.payout) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Margen</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ fmt(summary.margin) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">EPC</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ fmt(summary.epc) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">CR</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ (summary.cr ?? 0).toFixed(2) }}%</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">Por oferta</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Offer ID</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Revenue</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Payout</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="row in byOffer" :key="row.offer_id">
                                <td class="px-4 py-2 text-gray-900">{{ row.offer_id }}</td>
                                <td class="px-4 py-2 text-right text-gray-700">{{ fmt(row.revenue) }}</td>
                                <td class="px-4 py-2 text-right text-gray-700">{{ fmt(row.payout) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">Por partner</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Partner ID</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Revenue</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Payout</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="row in byPartner" :key="row.partner_id">
                                <td class="px-4 py-2 text-gray-900">{{ row.partner_id }}</td>
                                <td class="px-4 py-2 text-right text-gray-700">{{ fmt(row.revenue) }}</td>
                                <td class="px-4 py-2 text-right text-gray-700">{{ fmt(row.payout) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
