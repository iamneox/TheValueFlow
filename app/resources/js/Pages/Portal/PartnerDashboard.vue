<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    summary: { type: Object, default: () => ({}) },
    offers: { type: Array, default: () => [] },
});

const fmt = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n ?? 0);

const fmtNum = (n) => new Intl.NumberFormat('es-ES').format(n ?? 0);
</script>

<template>
    <AppLayout title="Partner Portal">
        <div class="space-y-6">
            <p class="text-sm text-gray-600">Resumen del mes en curso</p>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Clicks</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmtNum(summary.gross_clicks) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Conversiones</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmtNum(summary.conversions) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Payout</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-600">{{ fmt(summary.payout) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">EPC</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmt(summary.epc) }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">Mis ofertas activas</h2>
                </div>
                <ul class="divide-y divide-gray-200">
                    <li v-for="offer in offers" :key="offer.id" class="px-5 py-3 text-sm text-gray-900">
                        {{ offer.name }}
                    </li>
                    <li v-if="!offers.length" class="px-5 py-8 text-center text-sm text-gray-500">
                        No tienes ofertas asignadas
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
