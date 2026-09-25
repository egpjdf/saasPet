export interface OrganizationSettings {
  id: string;
  organization_id: string;
  branding: BrandingConfig;
  domain: DomainConfig;
  localization: LocalizationConfig;
  features: FeaturesConfig;
  email: EmailConfig;
  created_at: string;
  updated_at: string;
}

export interface BrandingConfig {
  logo_url: string | null;
  logo_dark_url: string | null;
  favicon_url: string | null;
  primary_color: string;
  secondary_color: string;
  accent_color: string;
  custom_css: string | null;
}

export interface DomainConfig {
  custom_domain: string | null;
  cname_status: 'pending' | 'valid' | 'invalid' | 'not_configured';
  cname_checked_at: string | null;
  ssl_status: 'valid' | 'expiring' | 'expired' | 'not_configured' | 'pending';
  ssl_expires_at: string | null;
  use_custom_domain: boolean;
  verification_token: string | null;
}

export interface LocalizationConfig {
  timezone: string;
  locale: string;
  currency: string;
  date_format: string;
  time_format: string;
  first_day_of_week: 0 | 1 | 6;
}

export interface FeaturesConfig {
  plan: string;
  enabled_features: string[];
  available_features: FeatureFlag[];
}

export interface FeatureFlag {
  key: string;
  name: string;
  description: string;
  enabled: boolean;
  required_plan: string;
  category: string;
  dependencies: string[];
}

export interface EmailConfig {
  provider: 'smtp' | 'resend' | 'sendgrid' | 'mailgun';
  smtp_host: string | null;
  smtp_port: number | null;
  smtp_username: string | null;
  smtp_password: string | null;
  smtp_encryption: 'tls' | 'ssl' | 'none' | null;
  from_email: string;
  from_name: string;
  reply_to_email: string | null;
  resend_api_key: string | null;
  sendgrid_api_key: string | null;
  mailgun_domain: string | null;
  mailgun_api_key: string | null;
  test_email_sent_at: string | null;
  test_email_status: 'success' | 'failed' | 'pending' | null;
  test_email_error: string | null;
}

export interface ApiResponse<T> {
  data: T;
  meta?: Record<string, any>;
}

export interface BrandingUpdateRequest {
  logo?: File;
  logo_dark?: File;
  favicon?: File;
  primary_color?: string;
  secondary_color?: string;
  accent_color?: string;
  custom_css?: string;
  remove_logo?: boolean;
  remove_logo_dark?: boolean;
  remove_favicon?: boolean;
}

export interface DomainUpdateRequest {
  custom_domain?: string;
  use_custom_domain?: boolean;
  verify_cname?: boolean;
}

export interface LocalizationUpdateRequest {
  timezone?: string;
  locale?: string;
  currency?: string;
  date_format?: string;
  time_format?: string;
  first_day_of_week?: 0 | 1 | 6;
}

export interface EmailUpdateRequest {
  provider?: 'smtp' | 'resend' | 'sendgrid' | 'mailgun';
  smtp_host?: string;
  smtp_port?: number;
  smtp_username?: string;
  smtp_password?: string;
  smtp_encryption?: 'tls' | 'ssl' | 'none';
  from_email?: string;
  from_name?: string;
  reply_to_email?: string;
  resend_api_key?: string;
  sendgrid_api_key?: string;
  mailgun_domain?: string;
  mailgun_api_key?: string;
  send_test_email?: boolean;
  test_email_to?: string;
}

export interface FileUploadResponse {
  url: string;
  path: string;
}

export interface CnameValidationResponse {
  status: 'valid' | 'invalid' | 'pending';
  message: string;
  expected_value: string;
  actual_value: string | null;
}

export interface SslStatusResponse {
  status: 'valid' | 'expiring' | 'expired' | 'not_configured' | 'pending';
  expires_at: string | null;
  days_until_expiry: number | null;
}

export interface TestEmailResponse {
  success: boolean;
  message: string;
  provider: string;
}