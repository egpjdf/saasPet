<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Saaspet - Plataforma SaaS para Pet Shops" />

    <div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-green-50">
        <!-- Navigation -->
        <nav class="bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <Link href="/" class="text-2xl font-bold text-primary-600">Saaspet</Link>
                    <div class="flex items-center space-x-4">
                        <Link href="/login" class="text-gray-600 hover:text-gray-900 font-medium">Entrar</Link>
                        <Link href="/register" class="bg-primary-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-primary-700 transition-colors">
                            Começar Grátis
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-5xl sm:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                    Gestão completa para
                    <span class="text-primary-600"> Pet Shops</span>
                    e Clínicas Veterinárias
                </h1>
                <p class="text-xl text-gray-600 mb-10 max-w-3xl mx-auto">
                    Plataforma SaaS multi-tenant com isolamento total de dados, 
                    agendamentos, POS, estoque, IA integrada e muito mais.
                </p>
                <div class="flex items-center justify-center space-x-4">
                    <Link href="/register" class="bg-primary-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-primary-700 transition-colors shadow-lg">
                        Iniciar Teste Grátis 14 Dias
                    </Link>
                    <Link href="#features" class="bg-white text-gray-700 px-8 py-4 rounded-lg font-semibold text-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                        Ver Funcionalidades
                    </Link>
                </div>
            </div>

            <!-- Trust Indicators -->
            <div class="mt-20 flex items-center justify-center space-x-12 text-gray-400 text-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Isolamento Total de Dados (RLS)</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>LGPD Compliant</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>99.9% Uptime SLA</span>
                </div>
            </div>
        </main>

        <!-- Features -->
        <section id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Tudo que seu negócio pet precisa</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">Funcionalidades completas para gestão de pets, agendamentos, vendas, estoque e muito mais.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <FeatureCard 
                        icon="calendar" 
                        title="Agendamentos Inteligentes" 
                        description="Calendário visual, lembretes automáticos, lista de espera e agendamento online 24/7."
                    />
                    <FeatureCard 
                        icon="pets" 
                        title="Gestão de Pets & Tutores" 
                        description="Prontuário completo, histórico médico, vacinas, alertas de aniversário e muito mais."
                    />
                    <FeatureCard 
                        icon="pos" 
                        title="PDV & Vendas" 
                        description="Ponto de venda integrado, carrinho, múltiplas formas de pagamento, emissão de NFC-e."
                    />
                    <FeatureCard 
                        icon="inventory" 
                        title="Controle de Estoque" 
                        description="Gestão de produtos, alertas de estoque baixo, fornecedores, pedidos de compra automáticos."
                    />
                    <FeatureCard 
                        icon="ai" 
                        title="Inteligência Artificial" 
                        description="Recomendações personalizadas, previsão de demanda, chatbot para atendimento."
                    />
                    <FeatureCard 
                        icon="reports" 
                        title="Relatórios & BI" 
                        description="Dashboards em tempo real, relatórios agendados, exportação PDF/Excel, KPIs personalizados."
                    />
                </div>
            </div>
        </section>

        <!-- Multi-tenancy -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Arquitetura Multi-Tenant Segura</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">Isolamento total de dados em 3 níveis: Plataforma → Organização → Workspace</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Platform Admin</h3>
                        <p class="text-gray-600">Super admin com visão global, gestão de organizações, billing unificado, health checks.</p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Organization</h3>
                        <p class="text-gray-600">Franquias/Redes com múltiplas unidades, billing centralizado, configurações de marca, membros.</p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Workspace</h3>
                        <p class="text-gray-600">Unidades/Filiais operacionais com isolamento de dados, agendamentos, POS, estoque local.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 bg-primary-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Pronto para transformar seu negócio pet?</h2>
                <p class="text-xl text-primary-100 mb-8 max-w-2xl mx-auto">Junte-se a centenas de pet shops e clínicas que já usam o Saaspet. Teste grátis por 14 dias.</p>
                <Link href="/register" class="bg-white text-primary-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-primary-50 transition-colors inline-block">
                    Começar Agora
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-white font-bold text-xl mb-4">Saaspet</h3>
                        <p class="text-gray-500">Plataforma SaaS completa para gestão de pet shops e clínicas veterinárias.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Produto</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white">Funcionalidades</a></li>
                            <li><a href="#" class="hover:text-white">Preços</a></li>
                            <li><a href="#" class="hover:text-white">Integrações</a></li>
                            <li><a href="#" class="hover:text-white">API Docs</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Empresa</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white">Sobre</a></li>
                            <li><a href="#" class="hover:text-white">Blog</a></li>
                            <li><a href="#" class="hover:text-white">Carreiras</a></li>
                            <li><a href="#" class="hover:text-white">Contato</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white">Privacidade</a></li>
                            <li><a href="#" class="hover:text-white">Termos</a></li>
                            <li><a href="#" class="hover:text-white">LGPD</a></li>
                            <li><a href="#" class="hover:text-white">Cookies</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                    <p>© 2024 Saaspet. Todos os direitos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import FeatureCard from '@/Components/FeatureCard.vue';
</script>