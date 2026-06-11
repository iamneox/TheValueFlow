<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    partnerRequests: { type: Object, required: true },
    clientRequests: { type: Object, required: true },
});

const approvePartner = (id) => {
    useForm({}).post(`/signup/partner/${id}/approve`);
};

const approveClient = (id) => {
    useForm({}).post(`/signup/client/${id}/approve`);
};

const statusClass = (status) => ({
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
}[status] ?? 'bg-gray-100 text-gray-800');
</script>

<template>
    <AppLayout title="Signup Admin">
        <div class="space-y-8">
            <div>
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Solicitudes de partners</h2>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">País</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="req in partnerRequests.data" :key="req.id">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ req.name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ req.email }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ req.country || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(req.status)">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        v-if="req.status === 'pending'"
                                        type="button"
                                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                        @click="approvePartner(req.id)"
                                    >
                                        Aprobar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!partnerRequests.data?.length">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Sin solicitudes</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Solicitudes de anunciantes</h2>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Empresa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Contacto</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="req in clientRequests.data" :key="req.id">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ req.company }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ req.contact_name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ req.email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(req.status)">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        v-if="req.status === 'pending'"
                                        type="button"
                                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                        @click="approveClient(req.id)"
                                    >
                                        Aprobar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!clientRequests.data?.length">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Sin solicitudes</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
