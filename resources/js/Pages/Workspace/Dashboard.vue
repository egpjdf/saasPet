<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps<{
    organization: any;
    workspace: any;
}>();
</script>

<template>
    <AppLayout :title="workspace.name">
        <template #default>
            <Head :title="workspace.name" />

            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ workspace.name }}</h1>
                    <p class="text-gray-600 mt-1">{{ workspace.slug }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 text-sm font-medium text-green-600 bg-green-50 rounded-full">
                        {{ workspace.status === 'active' ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-primary-100 rounded-lg">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Agendamentos Hoje</p>
                            <p class="text-2xl font-bold text-gray-900">8</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Pets Ativos</p>
                            <p class="text-2xl font-bold text-gray-900">156</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Vendas Hoje</p>
                            <p class="text-2xl font-bold text-gray-900">R$ 2.340</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Estoque Baixo</p>
                            <p class="text-2xl font-bold text-gray-900">3</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Atividade Recente</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Novo agendamento</p>
                                <p class="text-sm text-gray-500">Rex - Banho e Tosa - 14:30</p>
                            </div>
                        </div>
                        <span class="text-sm text-gray-500">Há 5 min</span>
                    </div>
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Venda realizada</p>
                                <p class="text-sm text-gray-500">Ração Premium 15kg - R$ 189,90</p>
                            </div>
                        </div>
                        <span class="text-sm text-gray-500">Há 15 min</span>
                    </div>
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Estoque baixo</p>
                                <p class="text-sm text-gray-500">Shampoo Neutro - 2 unidades restantes</p>
                            </div>
                        </div>
                        <span class="text-sm text-gray-500">Há 1 hora</span>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>