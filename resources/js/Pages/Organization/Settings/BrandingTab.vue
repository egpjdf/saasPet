<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useOrganizationSettings } from '@/Composables/useOrganizationSettings';
import { useFileUpload } from '@/Composables/useFileUpload';
import { useNotificationStore } from '@/Stores/notification';

const {
  branding,
  loading,
  updateBrandingConfig,
  error: settingsError,
  clearError,
} = useOrganizationSettings();

const notificationStore = useNotificationStore();

const primaryColor = ref('#3B82F6');
const secondaryColor = ref('#64748B');
const accentColor = ref('#8B5CF6');
const customCss = ref('');

const logoUpload = useFileUpload({ maxSize: 2 * 1024 * 1024, acceptedTypes: ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'] });
const logoDarkUpload = useFileUpload({ maxSize: 2 * 1024 * 1024, acceptedTypes: ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'] });
const faviconUpload = useFileUpload({ maxSize: 512 * 1024, acceptedTypes: ['image/png', 'image/x-icon', 'image/svg+xml'] });

const showColorPicker = ref<{ primary: boolean; secondary: boolean; accent: boolean }>({
  primary: false,
  secondary: false,
  accent: false,
});

const saving = ref(false);

onMounted(() => {
  if (branding.value) {
    primaryColor.value = branding.value.primary_color || '#3B82F6';
    secondaryColor.value = branding.value.secondary_color || '#64748B';
    accentColor.value = branding.value.accent_color || '#8B5CF6';
    customCss.value = branding.value.custom_css || '';
  }
});

const hasChanges = computed(() => {
  if (!branding.value) return false;
  return (
    logoUpload.file.value !== null ||
    logoDarkUpload.file.value !== null ||
    faviconUpload.file.value !== null ||
    primaryColor.value !== branding.value.primary_color ||
    secondaryColor.value !== branding.value.secondary_color ||
    accentColor.value !== branding.value.accent_color ||
    customCss.value !== (branding.value.custom_css || '')
  );
});

async function removeLogo(type: 'logo' | 'logo_dark' | 'favicon') {
  const confirmMsg = type === 'logo' ? 'Remover logo?' : type === 'logo_dark' ? 'Remover logo do modo escuro?' : 'Remover favicon?';
  if (!confirm(confirmMsg)) return;

  saving.value = true;
  try {
    const data = {
      ...branding.value,
      [`${type}_url`]: null,
      [`remove_${type}`]: true,
    } as any;
    await updateBrandingConfig(data);
    notificationStore.success(`${type === 'logo' ? 'Logo' : type === 'logo_dark' ? 'Logo (modo escuro)' : 'Favicon'} removido com sucesso`);
  } catch {
    notificationStore.error('Erro ao remover');
  } finally {
    saving.value = false;
  }
}

async function saveBranding() {
  saving.value = true;
  clearError();

  try {
    const data = {
      primary_color: primaryColor.value,
      secondary_color: secondaryColor.value,
      accent_color: accentColor.value,
      custom_css: customCss.value || null,
    };
    await updateBrandingConfig(data);
    notificationStore.success('Identidade visual salva com sucesso');
  } catch (err: any) {
    notificationStore.error(err.response?.data?.message || 'Erro ao salvar identidade visual');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="space-y-8 max-w-4xl">
    <div>
      <h2 class="text-xl font-semibold text-gray-900">Identidade Visual</h2>
      <p class="text-gray-600 mt-1">Personalize a aparência da sua organização</p>
    </div>

    <div v-if="settingsError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center justify-between">
      <span>{{ settingsError }}</span>
      <button @click="clearError" class="text-red-500 hover:text-red-700">✕</button>
    </div>

    <!-- Logo Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
        </svg>
        <span>Logotipo</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Logo Light -->
        <div class="space-y-3">
          <label class="block text-sm font-medium text-gray-700">Logo (Modo Claro)</label>
          <div class="relative">
            <div class="aspect-video w-full max-w-xs bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden">
              <img
                v-if="logoUpload.preview || branding?.logo_url"
                :src="logoUpload.preview || branding?.logo_url"
                alt="Logo preview"
                class="max-h-32 max-w-full object-contain"
              />
              <div v-else class="text-center text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-2 text-sm">Nenhuma logo</p>
              </div>
            </div>
            <input
              type="file"
              @change="logoUpload.handleFileSelect"
              accept="image/png,image/jpeg,image/svg+xml,image/webp"
              class="sr-only"
              id="logo-upload"
              :disabled="logoUpload.uploading"
              aria-label="Upload da logo modo claro"
            />
            <div class="mt-3 space-y-2">
              <label class="cursor-pointer">
                <input type="file" @change="logoUpload.handleFileSelect" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="sr-only" :disabled="logoUpload.uploading" />
                <button
                  type="button"
                  class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium flex items-center justify-center space-x-2"
                  :disabled="logoUpload.uploading || !logoUpload.isValid"
                >
                  <svg v-if="logoUpload.uploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span v-if="logoUpload.uploading">Enviando... {{ logoUpload.progress }}%</span>
                  <span v-else>Escolher arquivo</span>
                </button>
              </label>
              <p v-if="logoUpload.validationError" class="text-sm text-red-600">{{ logoUpload.validationError }}</p>
            </div>
          </div>
        </div>

        <!-- Logo Dark -->
        <div class="space-y-3">
          <label class="block text-sm font-medium text-gray-700">Logo (Modo Escuro)</label>
          <div class="relative">
            <div class="aspect-video w-full max-w-xs bg-gray-800 rounded-lg border-2 border-dashed border-gray-600 flex items-center justify-center relative overflow-hidden">
              <img
                v-if="logoDarkUpload.preview || branding?.logo_dark_url"
                :src="logoDarkUpload.preview || branding?.logo_dark_url"
                alt="Logo dark preview"
                class="max-h-32 max-w-full object-contain"
              />
              <div v-else class="text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-2 text-sm">Opcional</p>
              </div>
            </div>
            <div class="mt-3 space-y-2">
              <label class="cursor-pointer">
                <input type="file" @change="logoDarkUpload.handleFileSelect" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="sr-only" :disabled="logoDarkUpload.uploading" />
                <button
                  type="button"
                  class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium flex items-center justify-center space-x-2"
                  :disabled="logoDarkUpload.uploading || !logoDarkUpload.isValid"
                >
                  <svg v-if="logoDarkUpload.uploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span v-if="logoDarkUpload.uploading">Enviando... {{ logoDarkUpload.progress }}%</span>
                  <span v-else>Escolher arquivo</span>
                </button>
              </label>
              <p v-if="logoDarkUpload.validationError" class="text-sm text-red-600">{{ logoDarkUpload.validationError }}</p>
            </div>
          </div>
        </div>

        <!-- Favicon -->
        <div class="space-y-3">
          <label class="block text-sm font-medium text-gray-700">Favicon</label>
          <div class="relative">
            <div class="w-24 h-24 mx-auto bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden">
              <img
                v-if="faviconUpload.preview || branding?.favicon_url"
                :src="faviconUpload.preview || branding?.favicon_url"
                alt="Favicon preview"
                class="w-16 h-16 object-contain"
              />
              <div v-else class="text-center text-gray-400">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-1 text-xs">32x32 ou 64x64</p>
              </div>
            </div>
            <div class="mt-3 space-y-2">
              <label class="cursor-pointer">
                <input type="file" @change="faviconUpload.handleFileSelect" accept="image/png,image/x-icon,image/svg+xml" class="sr-only" :disabled="faviconUpload.uploading" />
                <button
                  type="button"
                  class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium flex items-center justify-center space-x-2"
                  :disabled="faviconUpload.uploading || !faviconUpload.isValid"
                >
                  <svg v-if="faviconUpload.uploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span v-if="faviconUpload.uploading">Enviando... {{ faviconUpload.progress }}%</span>
                  <span v-else>Escolher arquivo</span>
                </button>
              </label>
              <p v-if="faviconUpload.validationError" class="text-sm text-red-600">{{ faviconUpload.validationError }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Remove buttons -->
      <div class="pt-4 border-t border-gray-100 flex flex-wrap gap-3">
        <button
          v-if="branding?.logo_url"
          type="button"
          @click="removeLogo('logo')"
          class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 border border-red-300 rounded-lg transition-colors"
          :disabled="saving"
        >
          Remover Logo
        </button>
        <button
          v-if="branding?.logo_dark_url"
          type="button"
          @click="removeLogo('logo_dark')"
          class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 border border-red-300 rounded-lg transition-colors"
          :disabled="saving"
        >
          Remover Logo (Escuro)
        </button>
        <button
          v-if="branding?.favicon_url"
          type="button"
          @click="removeLogo('favicon')"
          class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 border border-red-300 rounded-lg transition-colors"
          :disabled="saving"
        >
          Remover Favicon
        </button>
      </div>
    </section>

    <!-- Colors Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
        </svg>
        <span>Cores da Marca</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Primary Color -->
        <div class="space-y-3">
          <label class="block text-sm font-medium text-gray-700">Cor Primária</label>
          <div class="relative">
            <input
              type="color"
              v-model="primaryColor"
              class="w-full h-12 rounded-lg border border-gray-300 cursor-pointer appearance-none"
              @change="showColorPicker.primary = false"
              aria-label="Cor primária"
            />
            <button
              type="button"
              @click="showColorPicker.primary = !showColorPicker.primary"
              class="absolute top-0 right-0 h-full w-12 bg-gray-100 rounded-r-lg flex items-center justify-center border-l border-gray-300 hover:bg-gray-200"
              aria-label="Abrir seletor de cor"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
            </button>
          </div>
          <input
            type="text"
            v-model="primaryColor"
            @input="showColorPicker.primary = false"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center uppercase"
            placeholder="#3B82F6"
            aria-label="Código hexadecimal da cor primária"
          />
          <p class="text-xs text-gray-500">Usada em botões primários, links e elementos de destaque</p>
        </div>

        <!-- Secondary Color -->
        <div class="space-y-3">
          <label class="block text-sm font-medium text-gray-700">Cor Secundária</label>
          <div class="relative">
            <input
              type="color"
              v-model="secondaryColor"
              class="w-full h-12 rounded-lg border border-gray-300 cursor-pointer appearance-none"
              @change="showColorPicker.secondary = false"
              aria-label="Cor secundária"
            />
            <button
              type="button"
              @click="showColorPicker.secondary = !showColorPicker.secondary"
              class="absolute top-0 right-0 h-full w-12 bg-gray-100 rounded-r-lg flex items-center justify-center border-l border-gray-300 hover:bg-gray-200"
              aria-label="Abrir seletor de cor"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
            </button>
          </div>
          <input
            type="text"
            v-model="secondaryColor"
            @input="showColorPicker.secondary = false"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center uppercase"
            placeholder="#64748B"
            aria-label="Código hexadecimal da cor secundária"
          />
          <p class="text-xs text-gray-500">Usada em elementos de apoio, bordas e textos secundários</p>
        </div>

        <!-- Accent Color -->
        <div class="space-y-3">
          <label class="block text-sm font-medium text-gray-700">Cor de Destaque</label>
          <div class="relative">
            <input
              type="color"
              v-model="accentColor"
              class="w-full h-12 rounded-lg border border-gray-300 cursor-pointer appearance-none"
              @change="showColorPicker.accent = false"
              aria-label="Cor de destaque"
            />
            <button
              type="button"
              @click="showColorPicker.accent = !showColorPicker.accent"
              class="absolute top-0 right-0 h-full w-12 bg-gray-100 rounded-r-lg flex items-center justify-center border-l border-gray-300 hover:bg-gray-200"
              aria-label="Abrir seletor de cor"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
            </button>
          </div>
          <input
            type="text"
            v-model="accentColor"
            @input="showColorPicker.accent = false"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center uppercase"
            placeholder="#8B5CF6"
            aria-label="Código hexadecimal da cor de destaque"
          />
          <p class="text-xs text-gray-500">Usada em badges, notificações e elementos de chamada</p>
        </div>
      </div>

      <!-- Color Preview -->
      <div class="pt-4 border-t border-gray-100">
        <h4 class="text-sm font-medium text-gray-700 mb-3">Pré-visualização</h4>
        <div class="flex flex-wrap items-center gap-4">
          <button class="px-4 py-2 rounded-lg text-white font-medium text-sm" :style="{ backgroundColor: primaryColor }">
            Botão Primário
          </button>
          <button class="px-4 py-2 rounded-lg border-2 font-medium text-sm" :style="{ borderColor: secondaryColor, color: secondaryColor }">
            Botão Secundário
          </button>
          <span class="px-3 py-1 rounded-full text-xs font-medium text-white" :style="{ backgroundColor: accentColor }">
            Badge Destaque
          </span>
          <a href="#" class="text-sm font-medium" :style="{ color: primaryColor }">Link primário</a>
        </div>
      </div>
    </section>

    <!-- Custom CSS Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
        </svg>
        <span>CSS Personalizado (Avançado)</span>
      </h3>
      <p class="text-sm text-gray-600">Adicione CSS personalizado para sobrescrever estilos padrão. Use com cautela.</p>
      <textarea
        v-model="customCss"
        rows="8"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm resize-y focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        placeholder="/* Exemplo: */&#10;.btn-primary { border-radius: 8px; }&#10;.card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }"
        aria-label="CSS personalizado"
      ></textarea>
    </section>

    <!-- Save Button -->
    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button
        type="button"
        @click="saveBranding"
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