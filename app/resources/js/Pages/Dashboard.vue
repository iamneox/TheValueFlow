<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    todayRevenue: { type: Number, default: 0 },
    todayPayout: { type: Number, default: 0 },
    topOffers: { type: Array, default: () => [] },
    topPartners: { type: Array, default: () => [] },
    currentMonth: { type: Object, default: () => ({}) },
    previousMonth: { type: Object, default: () => ({}) },
});

const fmt = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n ?? 0);

const fmtNum = (n) => new Intl.NumberFormat('es-ES').format(n ?? 0);

const pctChange = (current, previous) => {
    if (!previous) return current ? 100 : 0;
    return ((current - previous) / previous) * 100;
};
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Revenue hoy</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmt(todayRevenue) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Payout hoy</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmt(todayPayout) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Revenue mes</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmt(currentMonth.revenue) }}</p>
                    <p class="mt-1 text-xs" :class="pctChange(currentMonth.revenue, previousMonth.revenue) >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ pctChange(currentMonth.revenue, previousMonth.revenue).toFixed(1) }}% vs mes anterior
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Payout mes</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmt(currentMonth.payout) }}</p>
                    <p class="mt-1 text-xs" :class="pctChange(currentMonth.payout, previousMonth.payout) >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ pctChange(currentMonth.payout, previousMonth.payout).toFixed(1) }}% vs mes anterior
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="font-semibold text-gray-900">Top ofertas (mes)</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Payout</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Conv.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="offer in topOffers" :key="offer.id">
                                    <td class="px-4 py-3 text-gray-900">{{ offer.name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ fmt(offer.revenue) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ fmt(offer.payout) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ fmtNum(offer.conversions) }}</td>
                                </tr>
                                <tr v-if="!topOffers.length">
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin datos</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="font-semibold text-gray-900">Top partners (mes)</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Payout</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Conv.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="partner in topPartners" :key="partner.id">
                                    <td class="px-4 py-3 text-gray-900">{{ partner.name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ fmt(partner.revenue) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ fmt(partner.payout) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ fmtNum(partner.conversions) }}</td>
                                </tr>
                                <tr v-if="!topPartners.length">
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin datos</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">Comparativa mensual</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Métrica</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Mes actual</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Mes anterior</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Variación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="row in [
                                { label: 'Impresiones', key: 'impressions', money: false },
                                { label: 'Clicks', key: 'gross_clicks', money: false },
                                { label: 'Conversiones', key: 'conversions', money: false },
                                { label: 'Revenue', key: 'revenue', money: true },
                                { label: 'Payout', key: 'payout', money: true },
                                { label: 'Margen', key: 'margin', money: true },
                            ]" :key="row.key">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ row.label }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ row.money ? fmt(currentMonth[row.key]) : fmtNum(currentMonth[row.key]) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ row.money ? fmt(previousMonth[row.key]) : fmtNum(previousMonth[row.key]) }}
                                </td>
                                <td class="px-4 py-3 text-right" :class="pctChange(currentMonth[row.key], previousMonth[row.key]) >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ pctChange(currentMonth[row.key], previousMonth[row.key]).toFixed(1) }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
