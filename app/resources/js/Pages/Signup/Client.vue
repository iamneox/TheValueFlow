<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const form = useForm({
    company: '',
    contact_name: '',
    email: '',
    phone: '',
    terms_accepted: false,
});

const submit = () => {
    form.post('/signup/client');
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <Head title="Registro Anunciante" />

        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-indigo-600">TheValueFlow</h1>
                <p class="mt-2 text-sm text-gray-600">Solicitud de registro como anunciante</p>
            </div>

            <div v-if="page.props.flash?.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                {{ page.props.flash.success }}
            </div>

            <form class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm" @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Empresa *</label>
                        <input v-model="form.company" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contacto *</label>
                        <input v-model="form.contact_name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input v-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input v-model="form.terms_accepted" type="checkbox" required class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Acepto los términos y condiciones *
                    </label>
                    <p v-if="form.errors.terms_accepted" class="text-sm text-red-600">{{ form.errors.terms_accepted }}</p>
                </div>
                <button type="submit" :disabled="form.processing" class="mt-6 w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    Enviar solicitud
                </button>
            </form>
        </div>
    </div>
</template>
