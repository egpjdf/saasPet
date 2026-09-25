<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps<{
    organization: any;
}>();
</script>

<template>
    <AppLayout :title="organization.name">
        <template #default>
            <Head :title="organization.name" />

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ organization.name }}</h1>
                <p class="text-gray-600 mt-1">{{ organization.slug }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Workspaces Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Workspaces</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">Matriz</p>
                                <p class="text-sm text-gray-500">matriz</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium text-green-600 bg-green-50 rounded-full">Ativo</span>
                        </div>
                    </div>
                    <button class="mt-4 w-full text-primary-600 hover:text-primary-700 text-sm font-medium">
                        + Novo Workspace
                    </button>
                </div>

                <!-- Stats Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Estatísticas</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Usuários</span>
                            <span class="font-semibold text-gray-900">12</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pets Cadastrados</span>
                            <span class="font-semibold text-gray-900">342</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Agendamentos Hoje</span>
                            <span class="font-semibold text-gray-900">8</span>
                        </div>
                    </div>
                </div>

                <!-- Plan Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Plano Atual</h2>
                    <div class="space-y-3">
                        <div class="p-3 bg-primary-50 rounded-lg">
                            <p class="font-medium text-primary-900">Pro</p>
                            <p class="text-sm text-primary-700">R$ 299/mês</p>
                        </div>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p>✓ Usuários ilimitados</p>
                            <p>✓ Workspaces ilimitados</p>
                            <p>✓ API Access</p>
                            <p>✓ Suporte prioritário</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>