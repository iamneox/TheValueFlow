<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    invoice: { type: Object, required: true },
});

const editingLine = ref(null);

const lineForm = useForm({
    campaign_name: '',
    quantity: 0,
    payout: 0,
});

const addLineForm = useForm({
    campaign_name: '',
    quantity: 0,
    quantity_type: 'leads',
    payout: 0,
});

const startEdit = (line) => {
    editingLine.value = line.id;
    lineForm.campaign_name = line.campaign_name;
    lineForm.quantity = line.quantity;
    lineForm.payout = line.payout;
};

const saveLine = (lineId) => {
    lineForm.put(`/invoices/${props.invoice.id}/lines/${lineId}`, {
        onSuccess: () => { editingLine.value = null; },
    });
};

const addLine = () => {
    addLineForm.post(`/invoices/${props.invoice.id}/lines`, {
        onSuccess: () => addLineForm.reset(),
    });
};

const recalculate = () => {
    useForm({}).post(`/invoices/${props.invoice.id}/recalculate`);
};

const markSent = () => {
    useForm({}).post(`/invoices/${props.invoice.id}/sent`);
};

const markPaid = () => {
    useForm({}).post(`/invoices/${props.invoice.id}/paid`);
};

const deleteLine = (lineId) => {
    if (confirm('¿Eliminar esta línea?')) {
        useForm({}).delete(`/invoices/${props.invoice.id}/lines/${lineId}`);
    }
};

const fmt = (n) =>
    new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n ?? 0);
</script>

<template>
    <AppLayout :title="`Cierre ${invoice.number}`">
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link href="/invoices" class="text-sm text-indigo-600 hover:text-indigo-500">← Volver a cierres</Link>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">{{ invoice.number }}</h2>
                    <p class="text-sm text-gray-600">{{ invoice.partner?.name }} · {{ invoice.period_start }} — {{ invoice.period_end }}</p>
                    <p class="mt-1 text-lg font-semibold text-indigo-600">{{ fmt(invoice.total_amount) }}</p>
                    <span class="mt-2 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">{{ invoice.status }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50" @click="recalculate">
                        Recalcular
                    </button>
                    <a :href="`/invoices/${invoice.id}/pdf`" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Descargar PDF
                    </a>
                    <button v-if="invoice.status === 'draft'" type="button" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500" @click="markSent">
                        Marcar enviado
                    </button>
                    <button v-if="invoice.status === 'sent'" type="button" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500" @click="markPaid">
                        Marcar pagado
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Campaña</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Cantidad</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Payout</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Total</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="line in invoice.lines" :key="line.id">
                            <template v-if="editingLine === line.id">
                                <td class="px-4 py-2">
                                    <input v-model="lineForm.campaign_name" class="w-full rounded-md border-gray-300 text-sm" />
                                </td>
                                <td class="px-4 py-2">
                                    <input v-model="lineForm.quantity" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm text-right" />
                                </td>
                                <td class="px-4 py-2 text-gray-700">{{ line.quantity_type }}</td>
                                <td class="px-4 py-2">
                                    <input v-model="lineForm.payout" type="number" step="0.0001" min="0" class="w-full rounded-md border-gray-300 text-sm text-right" />
                                </td>
                                <td class="px-4 py-2 text-right text-gray-700">{{ fmt(line.total_payout) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" class="text-indigo-600 hover:text-indigo-500" @click="saveLine(line.id)">Guardar</button>
                                    <button type="button" class="ml-2 text-gray-500 hover:text-gray-700" @click="editingLine = null">Cancelar</button>
                                </td>
                            </template>
                            <template v-else>
                                <td class="px-4 py-3 text-gray-900">{{ line.campaign_name }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ line.quantity }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ line.quantity_type }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ fmt(line.payout) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ fmt(line.total_payout) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="text-indigo-600 hover:text-indigo-500" @click="startEdit(line)">Editar</button>
                                    <button type="button" class="ml-2 text-red-600 hover:text-red-500" @click="deleteLine(line.id)">Eliminar</button>
                                </td>
                            </template>
                        </tr>
                        <tr v-if="!invoice.lines?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Sin líneas</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <form class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="addLine">
                <h3 class="mb-4 text-sm font-semibold text-gray-900">Añadir línea manual</h3>
                <div class="grid gap-4 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Campaña *</label>
                        <input v-model="addLineForm.campaign_name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Cantidad *</label>
                        <input v-model="addLineForm.quantity" type="number" min="0" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tipo *</label>
                        <select v-model="addLineForm.quantity_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="leads">Leads</option>
                            <option value="clicks">Clicks</option>
                            <option value="impressions">Impresiones</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Payout *</label>
                        <input v-model="addLineForm.payout" type="number" step="0.0001" min="0" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <button type="submit" :disabled="addLineForm.processing" class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Añadir línea
                </button>
            </form>
        </div>
    </AppLayout>
</template>
