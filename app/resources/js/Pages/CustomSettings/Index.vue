<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    payouts: { type: Object, required: true },
    caps: { type: Object, required: true },
    access: { type: Object, required: true },
    partners: { type: Array, default: () => [] },
    offers: { type: Array, default: () => [] },
});

const activeTab = ref('payouts');

const payoutForm = useForm({
    partner_id: '',
    offer_id: '',
    event_name: '',
    payout: 0,
});

const capForm = useForm({
    partner_id: '',
    offer_id: '',
    cap_type: 'daily',
    metric: 'clicks',
    value: 1,
});

const accessForm = useForm({
    offer_id: '',
    partner_id: '',
    access_type: 'whitelist',
});

const submitPayout = () => payoutForm.post('/custom-settings/payouts', { onSuccess: () => payoutForm.reset() });
const submitCap = () => capForm.post('/custom-settings/caps', { onSuccess: () => capForm.reset() });
const submitAccess = () => accessForm.post('/custom-settings/access', { onSuccess: () => accessForm.reset() });

const tabs = [
    { id: 'payouts', label: 'Custom Payouts' },
    { id: 'caps', label: 'Custom Caps' },
    { id: 'access', label: 'Partner Access' },
];

const fmt = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n ?? 0);
</script>

<template>
    <AppLayout title="Custom Settings">
        <div class="space-y-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-6">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="border-b-2 pb-3 text-sm font-medium transition-colors"
                        :class="activeTab === tab.id
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <div v-show="activeTab === 'payouts'" class="space-y-6">
                <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submitPayout">
                    <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo custom payout</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Partner *</label>
                            <select v-model="payoutForm.partner_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccionar…</option>
                                <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Oferta</label>
                            <select v-model="payoutForm.offer_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Global</option>
                                <option v-for="o in offers" :key="o.id" :value="o.id">{{ o.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Evento</label>
                            <input v-model="payoutForm.event_name" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Payout *</label>
                            <input v-model="payoutForm.payout" type="number" step="0.0001" min="0" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <button type="submit" :disabled="payoutForm.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                        Crear payout
                    </button>
                </form>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Evento</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Payout</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="row in payouts.data" :key="row.id">
                                <td class="px-4 py-3 text-gray-900">{{ row.partner?.name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.offer?.name || 'Global' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.event_name || '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">{{ fmt(row.payout) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="activeTab === 'caps'" class="space-y-6">
                <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submitCap">
                    <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo custom cap</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Partner *</label>
                            <select v-model="capForm.partner_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccionar…</option>
                                <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Oferta</label>
                            <select v-model="capForm.offer_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Global</option>
                                <option v-for="o in offers" :key="o.id" :value="o.id">{{ o.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Tipo cap *</label>
                            <select v-model="capForm.cap_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="daily">Diario</option>
                                <option value="monthly">Mensual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Métrica *</label>
                            <select v-model="capForm.metric" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="clicks">Clicks</option>
                                <option value="conversions">Conversiones</option>
                                <option value="revenue">Revenue</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Valor *</label>
                            <input v-model="capForm.value" type="number" min="1" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <button type="submit" :disabled="capForm.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                        Crear cap
                    </button>
                </form>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Métrica</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="row in caps.data" :key="row.id">
                                <td class="px-4 py-3 text-gray-900">{{ row.partner?.name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.offer?.name || 'Global' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.cap_type }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.metric }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">{{ row.value }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="activeTab === 'access'" class="space-y-6">
                <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submitAccess">
                    <h2 class="mb-4 text-sm font-semibold text-gray-900">Configurar acceso partner</h2>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Oferta *</label>
                            <select v-model="accessForm.offer_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccionar…</option>
                                <option v-for="o in offers" :key="o.id" :value="o.id">{{ o.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Partner *</label>
                            <select v-model="accessForm.partner_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccionar…</option>
                                <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Tipo acceso *</label>
                            <select v-model="accessForm.access_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="whitelist">Whitelist</option>
                                <option value="blacklist">Blacklist</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" :disabled="accessForm.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                        Guardar acceso
                    </button>
                </form>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Oferta</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Acceso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="row in access.data" :key="row.id">
                                <td class="px-4 py-3 text-gray-900">{{ row.offer?.name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ row.partner?.name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="row.access_type === 'whitelist' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ row.access_type }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
