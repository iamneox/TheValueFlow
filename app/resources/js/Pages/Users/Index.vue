<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    users: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    partner_id: '',
    client_id: '',
    is_active: true,
});

const submit = () => {
    form.post('/users', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout title="Users">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo usuario</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nombre *</label>
                        <input v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Email *</label>
                        <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Contraseña *</label>
                        <input v-model="form.password" type="password" required minlength="8" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Rol *</label>
                        <select v-model="form.role" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner</label>
                        <select v-model="form.partner_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Ninguno</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Anunciante</label>
                        <select v-model="form.client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Ninguno</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company }}</option>
                        </select>
                    </div>
                    <label class="flex items-end gap-2 pb-2 text-sm text-gray-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Activo
                    </label>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear usuario
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Rol</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span v-for="role in user.roles" :key="role.id ?? role" class="mr-1 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">
                                    {{ role.name ?? role }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!users.data?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay usuarios</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
