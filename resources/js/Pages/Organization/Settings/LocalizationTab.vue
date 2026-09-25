<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useOrganizationSettings } from '@/Composables/useOrganizationSettings';
import { fetchTimezones, fetchLocales, fetchCurrencies } from '@/Services/api/organizationSettings';
import { useNotificationStore } from '@/Stores/notification';

const {
  localization,
  loading,
  updateLocalizationConfig,
  error: settingsError,
  clearError,
} = useOrganizationSettings();

const notificationStore = useNotificationStore();

const timezones = ref<string[]>([]);
const locales = ref<{ code: string; name: string; native_name: string }[]>([]);
const currencies = ref<{ code: string; name: string; symbol: string }[]>([]);

const timezone = ref('');
const locale = ref('');
const currency = ref('');
const dateFormat = ref('');
const timeFormat = ref('24h');
const firstDayOfWeek = ref<0 | 1 | 6>(0);

const dateFormatOptions = [
  { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY (31/12/2024)' },
  { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY (12/31/2024)' },
  { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD (2024-12-31)' },
  { value: 'DD.MM.YYYY', label: 'DD.MM.YYYY (31.12.2024)' },
];

const timeFormatOptions = [
  { value: '24h', label: '24 horas (14:30)' },
  { value: '12h', label: '12 horas (2:30 PM)' },
];

const firstDayOptions = [
  { value: 0, label: 'Domingo' },
  { value: 1, label: 'Segunda-feira' },
  { value: 6, label: 'Sábado' },
];

const saving = ref(false);

onMounted(async () => {
  try {
    const [tz, loc, cur] = await Promise.all([
      fetchTimezones(),
      fetchLocales(),
      fetchCurrencies(),
    ]);
    timezones.value = tz;
    locales.value = loc;
    currencies.value = cur;
  } catch {
    notificationStore.error('Erro ao carregar opções de localização');
  }

  if (localization.value) {
    timezone.value = localization.value.timezone || 'America/Sao_Paulo';
    locale.value = localization.value.locale || 'pt-BR';
    currency.value = localization.value.currency || 'BRL';
    dateFormat.value = localization.value.date_format || 'DD/MM/YYYY';
    timeFormat.value = localization.value.time_format || '24h';
    firstDayOfWeek.value = localization.value.first_day_of_week ?? 0;
  }
});

const hasChanges = computed(() => {
  if (!localization.value) return true;
  return (
    timezone.value !== localization.value.timezone ||
    locale.value !== localization.value.locale ||
    currency.value !== localization.value.currency ||
    dateFormat.value !== localization.value.date_format ||
    timeFormat.value !== localization.value.time_format ||
    firstDayOfWeek.value !== localization.value.first_day_of_week
  );
});

const currentDate = computed(() => new Date());
const datePreview = computed(() => {
  const d = currentDate.value;
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();

  switch (dateFormat.value) {
    case 'DD/MM/YYYY': return `${day}/${month}/${year}`;
    case 'MM/DD/YYYY': return `${month}/${day}/${year}`;
    case 'YYYY-MM-DD': return `${year}-${month}-${day}`;
    case 'DD.MM.YYYY': return `${day}.${month}.${year}`;
    default: return `${day}/${month}/${year}`;
  }
});

const timePreview = computed(() => {
  const d = currentDate.value;
  const hours = d.getHours();
  const minutes = String(d.getMinutes()).padStart(2, '0');

  if (timeFormat.value === '12h') {
    const period = hours >= 12 ? 'PM' : 'AM';
    const h12 = hours % 12 || 12;
    return `${h12}:${minutes} ${period}`;
  }
  return `${String(hours).padStart(2, '0')}:${minutes}`;
});

const currencyPreview = computed(() => {
  const cur = currencies.value.find(c => c.code === currency.value);
  if (!cur) return 'R$ 1.234,56';
  return `${cur.symbol} 1.234,56`;
});

async function saveLocalization() {
  saving.value = true;
  clearError();

  try {
    const data = {
      timezone: timezone.value,
      locale: locale.value,
      currency: currency.value,
      date_format: dateFormat.value,
      time_format: timeFormat.value,
      first_day_of_week: firstDayOfWeek.value,
    };
    await updateLocalizationConfig(data);
    notificationStore.success('Configurações de localização salvas');
  } catch (err: any) {
    notificationStore.error(err.response?.data?.message || 'Erro ao salvar localização');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="space-y-8 max-w-3xl">
    <div>
      <h2 class="text-xl font-semibold text-gray-900">Localização e Formatos</h2>
      <p class="text-gray-600 mt-1">Configure fuso horário, idioma, moeda e formatos de data/hora</p>
    </div>

    <div v-if="settingsError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center justify-between">
      <span>{{ settingsError }}</span>
      <button @click="clearError" class="text-red-500 hover:text-red-700">✕</button>
    </div>

    <!-- Timezone & Locale Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
        </svg>
        <span>Região e Idioma</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Timezone -->
        <div>
          <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Fuso Horário</label>
          <select
            id="timezone"
            v-model="timezone"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-gray-900 bg-white"
            aria-label="Selecionar fuso horário"
          >
            <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
          </select>
          <p class="mt-1 text-sm text-gray-500">Define o horário padrão para agendamentos e relatórios</p>
        </div>

        <!-- Locale -->
        <div>
          <label for="locale" class="block text-sm font-medium text-gray-700 mb-1">Idioma</label>
          <select
            id="locale"
            v-model="locale"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-gray-900 bg-white"
            aria-label="Selecionar idioma"
          >
            <option v-for="loc in locales" :key="loc.code" :value="loc.code">{{ loc.native_name }} ({{ loc.name }})</option>
          </select>
          <p class="mt-1 text-sm text-gray-500">Idioma da interface e mensagens do sistema</p>
        </div>
      </div>
    </section>

    <!-- Currency Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Moeda</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Moeda Padrão</label>
          <select
            id="currency"
            v-model="currency"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-gray-900 bg-white"
            aria-label="Selecionar moeda"
          >
            <option v-for="cur in currencies" :key="cur.code" :value="cur.code">{{ cur.code }} - {{ cur.name }} ({{ cur.symbol }})</option>
          </select>
          <p class="mt-1 text-sm text-gray-500">Moeda usada em valores financeiros, relatórios e faturas</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pré-visualização</label>
          <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-lg font-mono font-semibold text-gray-900">
            {{ currencyPreview }}
          </div>
        </div>
      </div>
    </section>

    <!-- Date & Time Formats Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>Formatos de Data e Hora</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Date Format -->
        <div>
          <label for="date-format" class="block text-sm font-medium text-gray-700 mb-1">Formato de Data</label>
          <select
            id="date-format"
            v-model="dateFormat"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-gray-900 bg-white"
            aria-label="Selecionar formato de data"
          >
            <option v-for="opt in dateFormatOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>

        <!-- Time Format -->
        <div>
          <label for="time-format" class="block text-sm font-medium text-gray-700 mb-1">Formato de Hora</label>
          <select
            id="time-format"
            v-model="timeFormat"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-gray-900 bg-white"
            aria-label="Selecionar formato de hora"
          >
            <option v-for="opt in timeFormatOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>

        <!-- First Day of Week -->
        <div>
          <label for="first-day" class="block text-sm font-medium text-gray-700 mb-1">Primeiro Dia da Semana</label>
          <select
            id="first-day"
            v-model="firstDayOfWeek"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-gray-900 bg-white"
            aria-label="Selecionar primeiro dia da semana"
          >
            <option v-for="opt in firstDayOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
      </div>

      <!-- Preview -->
      <div class="pt-4 border-t border-gray-100">
        <h4 class="text-sm font-medium text-gray-700 mb-3">Pré-visualização</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
          <div class="text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Data</p>
            <p class="text-2xl font-mono font-semibold text-gray-900">{{ datePreview }}</p>
          </div>
          <div class="text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Hora</p>
            <p class="text-2xl font-mono font-semibold text-gray-900">{{ timePreview }}</p>
          </div>
          <div class="text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Moeda</p>
            <p class="text-2xl font-mono font-semibold text-gray-900">{{ currencyPreview }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Save Button -->
    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button
        type="button"
        @click="saveLocalization"
        :disabled="saving || loading.value || !hasChanges"
        class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
      >
        <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span>{{ saving ? 'Salvando...' : 'Salvar Alterações' }}</span>
      </button>
    </div>
  </div>
</template>