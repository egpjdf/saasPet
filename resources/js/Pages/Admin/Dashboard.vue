<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout :title="'Platform Admin'">
        <template #default>
            <Head title="Platform Admin - Saaspet" />

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 flex items-center space-x-2">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Platform Admin Dashboard</span>
                </h1>
                <p class="text-gray-600 mt-1">Visão global da plataforma</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <StatCard title="Organizações" value="24" icon="building" color="blue" />
                <StatCard title="Workspaces" value="156" icon="cube" color="green" />
                <StatCard title="Usuários" value="1.234" icon="users" color="yellow" />
                <StatCard title="Receita MRR" value="R$ 45.600" icon="currency" color="purple" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Organizations Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Organizações Recentes</h2>
                        <a href="#" class="text-sm text-primary-600 hover:text-primary-700">Ver todas</a>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <OrganizationRow name="Demo Petshop" slug="demo-petshop" workspaces={3} users={12} status="active" plan="Pro" />
                        <OrganizationRow name="PetShop Central" slug="petshop-central" workspaces={5} users={28} status="active" plan="Enterprise" />
                        <OrganizationRow name="Vet Clinic Network" slug="vet-clinic" workspaces={12} users={89} status="active" plan="Enterprise" />
                        <OrganizationRow name="Banho & Tosa Express" slug="banho-tosa" workspaces={1} users={4} status="trial" plan="Starter" />
                    </div>
                </div>

                <!-- System Health -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">System Health</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <HealthCheck name="PostgreSQL" status="healthy" latency="12ms" />
                        <HealthCheck name="Redis" status="healthy" latency="3ms" />
                        <HealthCheck name="Reverb (WebSocket)" status="healthy" latency="5ms" />
                        <HealthCheck name="MinIO Storage" status="healthy" latency="8ms" />
                        <HealthCheck name="Queue Workers" status="healthy" latency="-" detail="4 workers active" />
                        <HealthCheck name="Scheduler" status="healthy" latency="-" detail="Last run: 2 min ago" />
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>

<script setup lang="ts">
import StatCard from '@/Components/StatCard.vue';
import OrganizationRow from '@/Components/OrganizationRow.vue';
import HealthCheck from '@/Components/HealthCheck.vue';
</script>