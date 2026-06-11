<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    domains: { type: Object, required: true },
    partners: { type: Array, default: () => [] },
    cloudflareConfigured: { type: Boolean, default: false },
    trackerIp: { type: String, default: '' },
});

const form = useForm({
    domain: '',
    partner_id: '',
    status: 'active',
    check_interval_hours: 24,
    bot_fight_mode: false,
    provision_cloudflare: true,
});

const submit = () => {
    form.post('/tracking-domains', {
        onSuccess: () => form.reset('domain', 'partner_id', 'bot_fight_mode'),
    });
};

const checkDomain = (id) => {
    useForm({}).post(`/tracking-domains/${id}/check`);
};

const reprovision = (id) => {
    useForm({}).post(`/tracking-domains/${id}/reprovision`);
};

const toggleBotFight = (domain) => {
    useForm({
        partner_id: domain.partner_id,
        status: domain.status,
        check_interval_hours: domain.check_interval_hours,
        bot_fight_mode: !domain.bot_fight_mode,
    }).put(`/tracking-domains/${domain.id}`);
};

const statusClass = (status) => ({
    active: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    blacklisted: 'bg-red-100 text-red-800',
}[status] ?? 'bg-gray-100 text-gray-800');
</script>

<template>
    <AppLayout title="Tracking Domains">
        <div class="space-y-6">
            <div
                v-if="!cloudflareConfigured"
                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
            >
                Cloudflare API no configurada. Añade <code class="font-mono">CLOUDFLARE_API_TOKEN</code> en el servidor para auto-provisionar DNS.
            </div>

            <div class="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                Los dominios de tracking se crean como registro <strong>A</strong> en Cloudflare apuntando a
                <code class="font-mono">{{ trackerIp }}</code> (AffGoServer) con <strong>proxy activo</strong> (certificado SSL vía Cloudflare).
            </div>

            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Nuevo dominio de tracking</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Subdominio / dominio *</label>
                        <input
                            v-model="form.domain"
                            required
                            placeholder="track.thevaluefactory.es"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Partner</label>
                        <select v-model="form.partner_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Global</option>
                            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Estado *</label>
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Activo</option>
                            <option value="paused">Pausado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Intervalo blacklist (h)</label>
                        <input v-model="form.check_interval_hours" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="flex flex-col gap-3 pt-5">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.provision_cloudflare" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            Crear DNS en Cloudflare (proxy ON)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.bot_fight_mode" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            Bot Fight Mode (zona CF)
                        </label>
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Crear y provisionar
                </button>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Dominio</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Partner</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Cloudflare</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Bot Fight</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Blacklist</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="domain in domains.data" :key="domain.id">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ domain.domain }}</div>
                                <div v-if="domain.cloudflare_sync_error" class="mt-1 text-xs text-red-600">{{ domain.cloudflare_sync_error }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ domain.partner?.name || 'Global' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="domain.cloudflare_record_id"
                                    class="inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800"
                                >
                                    Proxy ON
                                </span>
                                <span v-else class="text-xs text-gray-500">Sin sync</span>
                                <div v-if="domain.cloudflare_synced_at" class="mt-1 text-xs text-gray-500">
                                    {{ new Date(domain.cloudflare_synced_at).toLocaleString('es-ES') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    class="rounded-md px-2 py-1 text-xs font-medium ring-1"
                                    :class="domain.bot_fight_mode ? 'bg-indigo-100 text-indigo-800 ring-indigo-200' : 'bg-gray-100 text-gray-600 ring-gray-200'"
                                    @click="toggleBotFight(domain)"
                                >
                                    {{ domain.bot_fight_mode ? 'Activado' : 'Desactivado' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="domain.is_blacklisted ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                >
                                    {{ domain.is_blacklisted ? 'En blacklist' : 'OK' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button
                                    type="button"
                                    class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50"
                                    @click="reprovision(domain.id)"
                                >
                                    Re-sync CF
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50"
                                    @click="checkDomain(domain.id)"
                                >
                                    Blacklist
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!domains.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay dominios</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
