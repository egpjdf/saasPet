<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    title?: string;
}>();

const title = props.title || 'Saaspet';
</script>

<template>
    <Head :title="title" />
    
    <div class="min-h-screen bg-gray-50">
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <router-link to="/" class="text-xl font-bold text-primary-600">
                            Saaspet
                        </router-link>
                    </div>
                    <nav class="flex items-center space-x-4">
                        <router-link to="/login" class="text-gray-600 hover:text-gray-900">
                            Entrar
                        </router-link>
                        <router-link to="/register" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700">
                            Começar Grátis
                        </router-link>
                    </nav>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <p class="text-center text-gray-500 text-sm">
                    © 2024 Saaspet. Todos os direitos reservados.
                </p>
            </div>
        </footer>
    </div>
</template>