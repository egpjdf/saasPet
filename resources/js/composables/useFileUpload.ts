import { ref, computed } from 'vue';

export interface FileUploadOptions {
  maxSize?: number;
  acceptedTypes?: string[];
  onProgress?: (progress: number) => void;
}

export interface UploadResult {
  url: string;
  path: string;
  preview: string;
}

export function useFileUpload(options: FileUploadOptions = {}) {
  const {
    maxSize = 5 * 1024 * 1024,
    acceptedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp', 'image/x-icon'],
    onProgress,
  } = options;

  const uploading = ref(false);
  const progress = ref(0);
  const error = ref<string | null>(null);
  const preview = ref<string | null>(null);
  const file = ref<File | null>(null);

  const isValid = computed(() => {
    if (!file.value) return false;
    if (file.value.size > maxSize) return false;
    if (!acceptedTypes.includes(file.value.type)) return false;
    return true;
  });

  const validationError = computed(() => {
    if (!file.value) return null;
    if (file.value.size > maxSize) return `Arquivo muito grande. Máximo ${formatBytes(maxSize)}.`;
    if (!acceptedTypes.includes(file.value.type)) return `Tipo de arquivo não suportado. Use: ${acceptedTypes.join(', ')}.`;
    return null;
  });

  function formatBytes(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      setFile(input.files[0]);
    }
  }

  function setFile(selectedFile: File) {
    file.value = selectedFile;
    error.value = null;

    if (validationError.value) {
      error.value = validationError.value;
      preview.value = null;
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      preview.value = e.target?.result as string;
    };
    reader.readAsDataURL(selectedFile);
  }

  function clearFile() {
    file.value = null;
    preview.value = null;
    error.value = null;
    progress.value = 0;
  }

  async function upload(type: 'logo' | 'logo_dark' | 'favicon'): Promise<UploadResult | null> {
    if (!file.value || !isValid.value) {
      error.value = validationError.value || 'Nenhum arquivo selecionado';
      return null;
    }

    uploading.value = true;
    progress.value = 0;
    error.value = null;

    try {
      const xhr = new XMLHttpRequest();

      return new Promise((resolve, reject) => {
        xhr.upload.addEventListener('progress', (event) => {
          if (event.lengthComputable) {
            const percent = Math.round((event.loaded / event.total) * 100);
            progress.value = percent;
            onProgress?.(percent);
          }
        });

        xhr.addEventListener('load', () => {
          uploading.value = false;
          if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText);
            const result: UploadResult = {
              url: response.data.url,
              path: response.data.path,
              preview: preview.value || '',
            };
            resolve(result);
          } else {
            const response = JSON.parse(xhr.responseText);
            error.value = response.message || 'Erro no upload';
            reject(new Error(error.value));
          }
        });

        xhr.addEventListener('error', () => {
          uploading.value = false;
          error.value = 'Erro na conexão';
          reject(new Error(error.value));
        });

        const formData = new FormData();
        formData.append('file', file.value!);
        formData.append('type', type);

        xhr.open('POST', '/api/organization/settings/branding/upload');
        const tenantStore = (window as any).__PINIA__?.stores?.tenant;
        if (tenantStore?.organization?.id) {
          xhr.setRequestHeader('X-Organization-ID', tenantStore.organization.id);
        }
        xhr.send(formData);
      });
    } catch (err: any) {
      uploading.value = false;
      error.value = err.response?.data?.message || 'Erro ao fazer upload';
      return null;
    }
  }

  return {
    uploading,
    progress,
    error,
    preview,
    file,
    isValid,
    validationError,
    handleFileSelect,
    setFile,
    clearFile,
    upload,
  };
}