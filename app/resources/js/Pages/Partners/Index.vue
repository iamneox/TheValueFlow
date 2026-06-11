<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    partners: { type: Object, required: true },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    country: '',
    status: 'active',
    payment_terms: 'net_30',
    notes: '',
});

const submit = () => {
    form.post('/partners', {
        onSuccess: () => form.reset(),
    });
};

const statusClass = (status) => ({
    active: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    pending: 'bg-gray-100 text-gray-800',
}[status] ?? 'bg-gray-100 text-gray-800');
</script>

<template>
    <AppLayout title="Partners">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo partner</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nombre *</label>
                        <input v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">País</label>
                        <input v-model="form.country" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Pago *</label>
                        <select v-model="form.payment_terms" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="net_10">Net 10</option>
                            <option value="net_20">Net 20</option>
                            <option value="net_30">Net 30</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Estado *</label>
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Activo</option>
                            <option value="paused">Pausado</option>
                            <option value="pending">Pendiente</option>
                        </select>
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear partner
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">País</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Pago</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="partner in partners.data" :key="partner.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ partner.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ partner.email || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ partner.country || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ partner.payment_terms }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(partner.status)">
                                    {{ partner.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!partners.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay partners</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
