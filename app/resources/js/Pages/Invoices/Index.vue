<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    invoices: { type: Object, required: true },
    partners: { type: Array, default: () => [] },
});

const form = useForm({
    partner_id: '',
    period_start: '',
    period_end: '',
});

const submit = () => {
    form.post('/invoices');
};

const fmt = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n ?? 0);

const statusClass = (status) => ({
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
}[status] ?? 'bg-gray-100 text-gray-800');
</script>

<template>
    <AppLayout title="Cierres / Invoices">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Generar cierre</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner *</label>
                        <select v-model="form.partner_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Inicio periodo *</label>
                        <input v-model="form.period_start" type="date" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Fin periodo *</label>
                        <input v-model="form.period_end" type="date" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Generar cierre
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Número</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Periodo</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Total</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ invoice.number }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ invoice.partner?.name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ invoice.period_start }} — {{ invoice.period_end }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">{{ fmt(invoice.total_amount) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(invoice.status)">
                                    {{ invoice.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/invoices/${invoice.id}`" class="text-indigo-600 hover:text-indigo-500">Ver</Link>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay cierres</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
