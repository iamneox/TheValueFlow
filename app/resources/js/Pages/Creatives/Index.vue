<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    creatives: { type: Object, required: true },
    offers: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
});

const form = useForm({
    offer_id: '',
    type: 'banner',
    name: '',
    html_content: '',
    subject: '',
    sender_name: '',
    mandatory_mentions: '',
    status: 'active',
    file: null,
});

const kitForms = ref({});

const submit = () => {
    form.post('/creatives', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            form.status = 'active';
            form.type = 'banner';
        },
    });
};

const getKitForm = (creativeId) => {
    if (!kitForms.value[creativeId]) {
        kitForms.value[creativeId] = useForm({
            partner_id: '',
            traffic_source_id: '',
        });
    }
    return kitForms.value[creativeId];
};

const getCsrfToken = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
};

const downloadKit = (creativeId) => {
    const kit = getKitForm(creativeId);
    if (!kit.partner_id) return;

    const formEl = document.createElement('form');
    formEl.method = 'POST';
    formEl.action = `/creatives/${creativeId}/kit`;

    const fields = { _token: getCsrfToken(), partner_id: kit.partner_id, traffic_source_id: kit.traffic_source_id };
    Object.entries(fields).forEach(([name, value]) => {
        if (!value) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        formEl.appendChild(input);
    });

    document.body.appendChild(formEl);
    formEl.submit();
    document.body.removeChild(formEl);
};

const onFileChange = (e) => {
    form.file = e.target.files[0] ?? null;
};
</script>

<template>
    <AppLayout title="Creatives">
        <div class="space-y-6">
            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Subir creative</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Oferta *</label>
                        <select v-model="form.offer_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccionar…</option>
                            <option v-for="o in offers" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tipo *</label>
                        <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="banner">Banner</option>
                            <option value="image">Image</option>
                            <option value="html">HTML</option>
                            <option value="email">Email</option>
                            <option value="zip">ZIP</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nombre *</label>
                        <input v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Estado *</label>
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Activo</option>
                            <option value="paused">Pausado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Archivo</label>
                        <input type="file" class="mt-1 block w-full text-sm text-gray-500" @change="onFileChange" />
                    </div>
                    <div v-if="form.type === 'email'" class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Asunto</label>
                        <input v-model="form.subject" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div v-if="form.type === 'html' || form.type === 'email'" class="sm:col-span-2 lg:col-span-4">
                        <label class="block text-xs font-medium text-gray-700">HTML</label>
                        <textarea v-model="form.html_content" rows="4" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Subir creative
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Kit download</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="creative in creatives.data" :key="creative.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ creative.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ creative.offer?.name || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ creative.type }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ creative.status }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <select
                                        v-model="getKitForm(creative.id).partner_id"
                                        class="rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Partner…</option>
                                        <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>
                                    <select
                                        v-model="getKitForm(creative.id).traffic_source_id"
                                        class="rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Source…</option>
                                        <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <button
                                        type="button"
                                        class="rounded-md bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-500"
                                        @click="downloadKit(creative.id)"
                                    >
                                        Descargar kit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!creatives.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay creatives</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
