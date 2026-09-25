<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import { useWebSocketStore } from '@/stores/websocket';
import { onMounted } from 'vue';

defineProps<{
    title?: string;
}>();

const tenantStore = useTenantStore();
const notificationStore = useNotificationStore();
const wsStore = useWebSocketStore();

const title = props.title || 'Saaspet';

const user = tenantStore.user;
const organization = tenantStore.organization;
const workspace = tenantStore.workspace;

onMounted(() => {
    // Initialize WebSocket connection
    if (typeof window !== 'undefined' && window.Echo) {
        wsStore.setConnected(true);
    }
});

const handleLogout = () => {
    // Use Inertia to POST to logout route
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
};
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo & Tenant Info -->
                    <div class="flex items-center space-x-4">
                        <Link :href="tenantStore.isPlatformAdmin ? '/admin' : '/'" class="text-xl font-bold text-primary-600">
                            Saaspet
                        </Link>

                        <template v-if="tenantStore.hasOrganization">
                            <span class="hidden sm:block px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full">
                                {{ organization?.name }} ({{ organization?.slug }})
                            </span>
                        </template>

                        <template v-if="tenantStore.hasWorkspace">
                            <span class="hidden sm:block px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded-full">
                                {{ workspace?.name }} ({{ workspace?.slug }})
                            </span>
                        </template>

                        <template v-if="tenantStore.isPlatformAdmin">
                            <span class="px-3 py-1 text-xs font-medium text-purple-600 bg-purple-50 rounded-full">
                                Platform Admin
                            </span>
                        </template>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button 
                                class="relative p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                                @click="notificationStore.markAllAsRead"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span v-if="notificationStore.unreadCount > 0" 
                                    class="absolute -top-1 -right-1 w-5 h-5 text-xs text-white bg-red-500 rounded-full flex items-center justify-center">
                                    {{ notificationStore.unreadCount > 9 ? '9+' : notificationStore.unreadCount }}
                                </span>
                            </button>
                        </div>

                        <!-- Connection Status -->
                        <div class="flex items-center space-x-1 text-xs text-gray-500">
                            <span :class="wsStore.isConnected ? 'text-green-500' : 'text-red-500'" class="w-2 h-2 rounded-full"></span>
                            <span>{{ wsStore.isConnected ? 'Conectado' : 'Desconectado' }}</span>
                        </div>

                        <!-- User Menu -->
                        <div class="relative" v-if="user">
                            <button 
                                class="flex items-center space-x-2 p-2 text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100"
                                @click="showDropdown = !showDropdown"
                            >
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-primary-700">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <span class="hidden sm:block text-sm font-medium">{{ user.name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-show="showDropdown" 
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                    <p class="text-sm text-gray-500">{{ user.email }}</p>
                                    <p class="text-xs text-gray-400 capitalize">{{ user.role }}</p>
                                </div>
                                <Link href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Configurações
                                </Link>
                                <Link href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Perfil
                                </Link>
                                <hr class="my-1 border-gray-100" />
                                <button 
                                    @click="handleLogout"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                >
                                    Sair
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Flash Messages -->
            <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
                {{ $page.props.flash.error }}
            </div>

            <slot />
        </main>
    </div>
</template>