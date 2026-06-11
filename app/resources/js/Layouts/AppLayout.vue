<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();

const navItems = [
    { name: 'Dashboard', href: '/dashboard', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Clients', href: '/clients', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Partners', href: '/partners', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Offers', href: '/offers', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Tracking Domains', href: '/tracking-domains', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Tracking Links', href: '/tracking-links', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Creatives', href: '/creatives', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Reports', href: '/reports', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Invoices', href: '/invoices', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Adjustments', href: '/adjustments', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Postbacks', href: '/postbacks', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Traffic Sources', href: '/traffic-sources', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Custom Settings', href: '/custom-settings', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Documents', href: '/documents', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Users', href: '/users', roles: ['super_admin'] },
    { name: 'Signup Admin', href: '/signup/admin', roles: ['super_admin', 'affiliate_manager', 'sales_manager'] },
    { name: 'Partner Portal', href: '/portal/partner', roles: ['partner'] },
    { name: 'Client Portal', href: '/portal/client', roles: ['client'] },
];

const visibleNav = computed(() => {
    const roles = page.props.auth?.user?.roles ?? [];
    return navItems.filter((item) => item.roles.some((role) => roles.includes(role)));
});

const isActive = (href) => page.url === href || page.url.startsWith(href + '/');
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head :title="title" />

        <div v-if="page.props.flash?.success" class="bg-green-50 border-b border-green-200 px-4 py-3 text-sm text-green-800">
            {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash?.error" class="bg-red-50 border-b border-red-200 px-4 py-3 text-sm text-red-800">
            {{ page.props.flash.error }}
        </div>

        <div class="flex">
            <aside class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-gray-200 bg-white">
                <div class="flex h-16 items-center border-b border-gray-200 px-6">
                    <Link href="/dashboard" class="text-lg font-bold text-indigo-600">TheValueFlow</Link>
                </div>
                <nav class="flex-1 overflow-y-auto px-3 py-4">
                    <ul class="space-y-1">
                        <li v-for="item in visibleNav" :key="item.href">
                            <Link
                                :href="item.href"
                                class="block rounded-md px-3 py-2 text-sm font-medium transition-colors"
                                :class="isActive(item.href)
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'"
                            >
                                {{ item.name }}
                            </Link>
                        </li>
                    </ul>
                </nav>
            </aside>

            <div class="flex flex-1 flex-col pl-64">
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6">
                    <h1 v-if="title" class="text-lg font-semibold text-gray-900">{{ title }}</h1>
                    <div v-else />
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">{{ page.props.auth?.user?.name }}</span>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Cerrar sesión
                        </Link>
                    </div>
                </header>

                <main class="flex-1 p-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
