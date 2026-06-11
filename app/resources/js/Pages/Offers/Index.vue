<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    offers: { type: Object, required: true },
    clients: { type: Array, default: () => [] },
    domains: { type: Array, default: () => [] },
});

const PAYMENT_TYPE_OPTIONS = ['CPL', 'CPC', 'CPM', 'CPA'];

const form = useForm({
    name: '',
    client_id: '',
    payment_types: [{ type: 'CPA', revenue: 0, payout: 0 }],
    category: '',
    country: '',
    status: 'pending',
    tracking_domain_id: '',
});

const usedTypes = () => form.payment_types.map((row) => row.type);

const availableType = () => PAYMENT_TYPE_OPTIONS.find((type) => !usedTypes().includes(type));

const addPaymentType = () => {
    const next = availableType();
    if (!next) {
        return;
    }
    form.payment_types.push({ type: next, revenue: 0, payout: 0 });
};

const removePaymentType = (index) => {
    if (form.payment_types.length <= 1) {
        return;
    }
    form.payment_types.splice(index, 1);
};

const submit = () => {
    form.post('/offers', {
        onSuccess: () => {
            form.reset();
            form.payment_types = [{ type: 'CPA', revenue: 0, payout: 0 }];
        },
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
                        <label class="block text-xs font-medium text-gray-700">Estado *</label>
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Activo</option>
                            <option value="pending">Pendiente</option>
                            <option value="paused">Pausado</option>
                        </select>
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

                <div class="mt-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Tipos de pago</h3>
                        <button
                            type="button"
                            class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50 disabled:opacity-50"
                            :disabled="!availableType()"
                            @click="addPaymentType"
                        >
                            + Añadir tipo
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(row, index) in form.payment_types"
                            :key="index"
                            class="grid gap-3 rounded-lg border border-gray-100 bg-gray-50 p-4 sm:grid-cols-4"
                        >
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Tipo *</label>
                                <select
                                    v-model="row.type"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="type in PAYMENT_TYPE_OPTIONS"
                                        :key="type"
                                        :value="type"
                                        :disabled="usedTypes().includes(type) && row.type !== type"
                                    >
                                        {{ type }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Revenue *</label>
                                <input
                                    v-model="row.revenue"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Payout *</label>
                                <input
                                    v-model="row.payout"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div class="flex items-end">
                                <button
                                    type="button"
                                    class="rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 disabled:opacity-40"
                                    :disabled="form.payment_types.length <= 1"
                                    @click="removePaymentType(index)"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-if="form.errors.payment_types" class="mt-2 text-xs text-red-600">{{ form.errors.payment_types }}</p>
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
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tipos de pago</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="offer in offers.data" :key="offer.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ offer.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ offer.client?.company || '—' }}</td>
                            <td class="px-4 py-3">
                                <div v-if="offer.payment_types?.length" class="space-y-1">
                                    <div
                                        v-for="pt in offer.payment_types"
                                        :key="pt.id"
                                        class="flex flex-wrap items-center gap-2 text-xs"
                                    >
                                        <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 font-medium text-indigo-800">{{ pt.type }}</span>
                                        <span class="text-gray-600">Rev {{ fmt(pt.revenue) }}</span>
                                        <span class="text-gray-600">Pay {{ fmt(pt.payout) }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-gray-500">
                                    <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 font-medium text-indigo-800">{{ offer.type }}</span>
                                    <span class="ml-2 text-gray-600">Rev {{ fmt(offer.revenue) }} · Pay {{ fmt(offer.payout) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ offer.status }}</td>
                        </tr>
                        <tr v-if="!offers.data?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay ofertas</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
