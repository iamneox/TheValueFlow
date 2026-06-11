<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    clients: { type: Object, required: true },
});

const form = useForm({
    company: '',
    contact_name: '',
    email: '',
    phone: '',
    payment_terms: '',
    status: 'active',
    notes: '',
});

const submit = () => {
    form.post('/clients', {
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
    <AppLayout title="Anunciantes">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo anunciante</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Empresa *</label>
                        <input v-model="form.company" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.company" class="mt-1 text-xs text-red-600">{{ form.errors.company }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Contacto</label>
                        <input v-model="form.contact_name" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Teléfono</label>
                        <input v-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Estado *</label>
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Activo</option>
                            <option value="paused">Pausado</option>
                            <option value="pending">Pendiente</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Condiciones de pago</label>
                        <input v-model="form.payment_terms" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear anunciante
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Empresa</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Contacto</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="client in clients.data" :key="client.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ client.company }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ client.contact_name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ client.email || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(client.status)">
                                    {{ client.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!clients.data?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay anunciantes</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
