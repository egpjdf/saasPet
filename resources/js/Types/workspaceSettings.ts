export interface WorkspaceSettings {
  id: string;
  workspace_id: string;
  organization_id: string;
  features: WorkspaceFeature[];
  limits: WorkspaceLimit[];
  integrations: WorkspaceIntegration[];
  notifications: NotificationSettings;
  webhooks: WorkspaceWebhook[];
  created_at: string;
  updated_at: string;
}

export interface WorkspaceFeature {
  key: string;
  name: string;
  description: string;
  enabled: boolean;
  required_plan: string;
  dependencies: string[];
  category: 'core' | 'communication' | 'analytics' | 'integrations' | 'automation';
}

export interface WorkspaceLimit {
  key: string;
  name: string;
  description: string;
  base_plan_limit: number;
  current_override: number | null;
  new_value: number | null;
  unit: string;
  is_unlimited: boolean;
}

export interface WorkspaceIntegration {
  id: string;
  type: IntegrationType;
  name: string;
  status: 'connected' | 'disconnected' | 'error';
  config: Record<string, any>;
  last_sync_at: string | null;
  error_message: string | null;
  created_at: string;
  updated_at: string;
}

export type IntegrationType = 'whatsapp' | 'crm' | 'erp';

export interface WhatsAppIntegrationConfig {
  phone_number_id: string;
  access_token: string;
  webhook_url: string;
  verify_token: string;
}

export interface CrmIntegrationConfig {
  provider: 'hubspot' | 'pipedrive' | 'salesforce';
  auth_type: 'oauth' | 'api_key';
  credentials: Record<string, string>;
  field_mapping: Record<string, string>;
  sync_settings: {
    sync_contacts: boolean;
    sync_deals: boolean;
    sync_activities: boolean;
    sync_frequency: 'realtime' | 'hourly' | 'daily';
  };
}

export interface ErpIntegrationConfig {
  provider: 'tiny' | 'bling' | 'omie';
  credentials: Record<string, string>;
  sync_settings: {
    sync_products: boolean;
    sync_orders: boolean;
    sync_customers: boolean;
    sync_inventory: boolean;
    sync_frequency: 'realtime' | 'hourly' | 'daily';
  };
}

export interface NotificationSettings {
  channels: NotificationChannel[];
  categories: NotificationCategory[];
  matrix: NotificationMatrixCell[];
  digest_frequency: 'immediate' | 'hourly' | 'daily' | 'weekly' | 'never';
}

export interface NotificationChannel {
  key: string;
  name: string;
  description: string;
  enabled: boolean;
}

export interface NotificationCategory {
  key: string;
  name: string;
  description: string;
}

export interface NotificationMatrixCell {
  channel_key: string;
  category_key: string;
  enabled: boolean;
}

export interface WorkspaceWebhook {
  id: string;
  url: string;
  events: string[];
  hmac_secret: string | null;
  status: 'active' | 'inactive' | 'failed';
  last_triggered_at: string | null;
  last_response_code: number | null;
  last_response_body: string | null;
  created_at: string;
  updated_at: string;
}

export interface WebhookEvent {
  key: string;
  name: string;
  description: string;
  category: string;
}

export interface ApiResponse<T> {
  data: T;
  meta?: Record<string, any>;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface FeatureToggleRequest {
  feature_key: string;
  enabled: boolean;
}

export interface LimitUpdateRequest {
  limit_key: string;
  new_value: number;
}

export interface IntegrationCreateRequest {
  type: IntegrationType;
  name: string;
  config: Record<string, any>;
}

export interface IntegrationUpdateRequest {
  name?: string;
  config?: Record<string, any>;
}

export interface IntegrationTestRequest {
  config: Record<string, any>;
}

export interface IntegrationTestResponse {
  success: boolean;
  message: string;
  details?: Record<string, any>;
}

export interface NotificationMatrixUpdateRequest {
  channel_key: string;
  category_key: string;
  enabled: boolean;
}

export interface DigestFrequencyUpdateRequest {
  frequency: 'immediate' | 'hourly' | 'daily' | 'weekly' | 'never';
}

export interface WebhookCreateRequest {
  url: string;
  events: string[];
}

export interface WebhookUpdateRequest {
  url?: string;
  events?: string[];
  status?: 'active' | 'inactive';
}

export interface WebhookTestRequest {
  url: string;
  events: string[];
  payload?: Record<string, any>;
}

export interface WebhookTestResponse {
  success: boolean;
  status_code: number;
  response_body: string;
  response_time_ms: number;
}