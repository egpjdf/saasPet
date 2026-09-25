import axios from 'axios';
import type {
  OrganizationSettings,
  BrandingConfig,
  DomainConfig,
  LocalizationConfig,
  FeaturesConfig,
  EmailConfig,
  BrandingUpdateRequest,
  DomainUpdateRequest,
  LocalizationUpdateRequest,
  EmailUpdateRequest,
  FileUploadResponse,
  CnameValidationResponse,
  SslStatusResponse,
  TestEmailResponse,
  ApiResponse,
} from '@/Types/organizationSettings';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const tenantStore = useTenantStore();
  if (tenantStore.organization?.id) {
    config.headers['X-Organization-ID'] = tenantStore.organization.id;
  }
  return config;
});

function useTenantStore() {
  return (window as any).__PINIA__?.stores?.tenant || { organization: null, workspace: null };
}

export async function fetchOrganizationSettings(): Promise<OrganizationSettings> {
  const response = await api.get<ApiResponse<OrganizationSettings>>('/organization/settings');
  return response.data.data;
}

export async function updateOrganizationSettings(settings: Partial<OrganizationSettings>): Promise<OrganizationSettings> {
  const response = await api.put<ApiResponse<OrganizationSettings>>('/organization/settings', settings);
  return response.data.data;
}

export async function updateBranding(data: BrandingUpdateRequest): Promise<BrandingConfig> {
  const formData = new FormData();
  if (data.logo) formData.append('logo', data.logo);
  if (data.logo_dark) formData.append('logo_dark', data.logo_dark);
  if (data.favicon) formData.append('favicon', data.favicon);
  if (data.primary_color) formData.append('primary_color', data.primary_color);
  if (data.secondary_color) formData.append('secondary_color', data.secondary_color);
  if (data.accent_color) formData.append('accent_color', data.accent_color);
  if (data.custom_css) formData.append('custom_css', data.custom_css);
  if (data.remove_logo) formData.append('remove_logo', 'true');
  if (data.remove_logo_dark) formData.append('remove_logo_dark', 'true');
  if (data.remove_favicon) formData.append('remove_favicon', 'true');

  const response = await api.post<ApiResponse<BrandingConfig>>('/organization/settings/branding', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data.data;
}

export async function uploadBrandingFile(file: File, type: 'logo' | 'logo_dark' | 'favicon'): Promise<FileUploadResponse> {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', type);

  const response = await api.post<ApiResponse<FileUploadResponse>>('/organization/settings/branding/upload', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data.data;
}

export async function updateDomain(data: DomainUpdateRequest): Promise<DomainConfig> {
  const response = await api.put<ApiResponse<DomainConfig>>('/organization/settings/domain', data);
  return response.data.data;
}

export async function validateCname(domain: string): Promise<CnameValidationResponse> {
  const response = await api.post<ApiResponse<CnameValidationResponse>>('/organization/settings/domain/validate-cname', { domain });
  return response.data.data;
}

export async function checkSslStatus(domain: string): Promise<SslStatusResponse> {
  const response = await api.get<ApiResponse<SslStatusResponse>>(`/organization/settings/domain/ssl-status?domain=${encodeURIComponent(domain)}`);
  return response.data.data;
}

export async function updateLocalization(data: LocalizationUpdateRequest): Promise<LocalizationConfig> {
  const response = await api.put<ApiResponse<LocalizationConfig>>('/organization/settings/localization', data);
  return response.data.data;
}

export async function fetchAvailableFeatures(): Promise<FeaturesConfig> {
  const response = await api.get<ApiResponse<FeaturesConfig>>('/organization/settings/features');
  return response.data.data;
}

export async function updateEmail(data: EmailUpdateRequest): Promise<EmailConfig> {
  const response = await api.put<ApiResponse<EmailConfig>>('/organization/settings/email', data);
  return response.data.data;
}

export async function testEmail(config: EmailUpdateRequest & { test_email_to: string }): Promise<TestEmailResponse> {
  const response = await api.post<ApiResponse<TestEmailResponse>>('/organization/settings/email/test', config);
  return response.data.data;
}

export async function fetchTimezones(): Promise<string[]> {
  const response = await api.get<ApiResponse<string[]>>('/organization/settings/timezones');
  return response.data.data;
}

export async function fetchLocales(): Promise<{ code: string; name: string; native_name: string }[]> {
  const response = await api.get<ApiResponse<{ code: string; name: string; native_name: string }[]>>('/organization/settings/locales');
  return response.data.data;
}

export async function fetchCurrencies(): Promise<{ code: string; name: string; symbol: string }[]> {
  const response = await api.get<ApiResponse<{ code: string; name: string; symbol: string }[]>>('/organization/settings/currencies');
  return response.data.data;
}