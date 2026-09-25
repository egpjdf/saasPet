import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { 
  WorkspaceSettings, 
  WorkspaceFeature, 
  WorkspaceLimit, 
  WorkspaceIntegration, 
  NotificationSettings,
  NotificationChannel,
  NotificationCategory,
  NotificationMatrixCell,
  WorkspaceWebhook,
  WebhookEvent,
  IntegrationType
} from '@/Types/workspaceSettings';

export const useWorkspaceSettingsStore = defineStore('workspaceSettings', () => {
  const settings = ref<WorkspaceSettings | null>(null);
  const features = ref<WorkspaceFeature[]>([]);
  const availableFeatures = ref<WorkspaceFeature[]>([]);
  const limits = ref<WorkspaceLimit[]>([]);
  const integrations = ref<WorkspaceIntegration[]>([]);
  const integrationTypes = ref<{ type: string; name: string; description: string; config_schema: Record<string, any> }[]>([]);
  const notifications = ref<NotificationSettings | null>(null);
  const notificationChannels = ref<NotificationChannel[]>([]);
  const notificationCategories = ref<NotificationCategory[]>([]);
  const notificationMatrix = ref<NotificationMatrixCell[]>([]);
  const digestFrequency = ref<'immediate' | 'hourly' | 'daily' | 'weekly' | 'never'>('immediate');
  const webhooks = ref<WorkspaceWebhook[]>([]);
  const webhookEvents = ref<WebhookEvent[]>([]);

  const isLoaded = computed(() => settings.value !== null);
  const limitsLoaded = computed(() => limits.value.length > 0);

  function setSettings(data: WorkspaceSettings) {
    settings.value = data;
    if (data.features) features.value = data.features;
    if (data.limits) limits.value = data.limits;
    if (data.integrations) integrations.value = data.integrations;
    if (data.notifications) {
      notifications.value = data.notifications;
      notificationMatrix.value = data.notifications.matrix || [];
      digestFrequency.value = data.notifications.digest_frequency || 'immediate';
    }
    if (data.webhooks) webhooks.value = data.webhooks;
  }

  function setFeatures(data: WorkspaceFeature[]) {
    features.value = data;
    if (settings.value) {
      settings.value.features = data;
    }
  }

  function setAvailableFeatures(data: WorkspaceFeature[]) {
    availableFeatures.value = data;
  }

  function updateFeature(feature: WorkspaceFeature) {
    const index = features.value.findIndex(f => f.key === feature.key);
    if (index !== -1) {
      features.value[index] = feature;
    }
    if (settings.value?.features) {
      const sIndex = settings.value.features.findIndex(f => f.key === feature.key);
      if (sIndex !== -1) {
        settings.value.features[sIndex] = feature;
      }
    }
  }

  function setLimits(data: WorkspaceLimit[]) {
    limits.value = data;
    if (settings.value) {
      settings.value.limits = data;
    }
  }

  function updateLimit(limit: WorkspaceLimit) {
    const index = limits.value.findIndex(l => l.key === limit.key);
    if (index !== -1) {
      limits.value[index] = limit;
    }
    if (settings.value?.limits) {
      const sIndex = settings.value.limits.findIndex(l => l.key === limit.key);
      if (sIndex !== -1) {
        settings.value.limits[sIndex] = limit;
      }
    }
  }

  function setPendingLimitValue(limitKey: string, value: number | null) {
    const limit = limits.value.find(l => l.key === limitKey);
    if (limit) {
      limit.new_value = value;
    }
  }

  function setIntegrations(data: WorkspaceIntegration[]) {
    integrations.value = data;
    if (settings.value) {
      settings.value.integrations = data;
    }
  }

  function addIntegration(integration: WorkspaceIntegration) {
    integrations.value.push(integration);
    if (settings.value?.integrations) {
      settings.value.integrations.push(integration);
    }
  }

  function updateIntegration(integration: WorkspaceIntegration) {
    const index = integrations.value.findIndex(i => i.id === integration.id);
    if (index !== -1) {
      integrations.value[index] = integration;
    }
    if (settings.value?.integrations) {
      const sIndex = settings.value.integrations.findIndex(i => i.id === integration.id);
      if (sIndex !== -1) {
        settings.value.integrations[sIndex] = integration;
      }
    }
  }

  function removeIntegration(integrationId: string) {
    integrations.value = integrations.value.filter(i => i.id !== integrationId);
    if (settings.value?.integrations) {
      settings.value.integrations = settings.value.integrations.filter(i => i.id !== integrationId);
    }
  }

  function setIntegrationTypes(data: { type: string; name: string; description: string; config_schema: Record<string, any> }[]) {
    integrationTypes.value = data;
  }

  function setNotifications(data: NotificationSettings) {
    notifications.value = data;
    notificationMatrix.value = data.matrix || [];
    digestFrequency.value = data.digest_frequency || 'immediate';
    if (settings.value) {
      settings.value.notifications = data;
    }
  }

  function updateNotificationMatrix(channelKey: string, categoryKey: string, enabled: boolean) {
    if (!notifications.value) return;
    
    const index = notificationMatrix.value.findIndex(
      m => m.channel_key === channelKey && m.category_key === categoryKey
    );
    
    if (index !== -1) {
      notificationMatrix.value[index].enabled = enabled;
    } else {
      notificationMatrix.value.push({ channel_key: channelKey, category_key: categoryKey, enabled });
    }
    
    notifications.value.matrix = [...notificationMatrix.value];
  }

  function setDigestFrequency(frequency: 'immediate' | 'hourly' | 'daily' | 'weekly' | 'never') {
    digestFrequency.value = frequency;
    if (notifications.value) {
      notifications.value.digest_frequency = frequency;
    }
  }

  function setNotificationChannels(data: NotificationChannel[]) {
    notificationChannels.value = data;
  }

  function setNotificationCategories(data: NotificationCategory[]) {
    notificationCategories.value = data;
  }

  function setWebhooks(data: WorkspaceWebhook[]) {
    webhooks.value = data;
    if (settings.value) {
      settings.value.webhooks = data;
    }
  }

  function addWebhook(webhook: WorkspaceWebhook) {
    webhooks.value.push(webhook);
    if (settings.value?.webhooks) {
      settings.value.webhooks.push(webhook);
    }
  }

  function updateWebhook(webhook: WorkspaceWebhook) {
    const index = webhooks.value.findIndex(w => w.id === webhook.id);
    if (index !== -1) {
      webhooks.value[index] = webhook;
    }
    if (settings.value?.webhooks) {
      const sIndex = settings.value.webhooks.findIndex(w => w.id === webhook.id);
      if (sIndex !== -1) {
        settings.value.webhooks[sIndex] = webhook;
      }
    }
  }

  function updateWebhookHmacSecret(webhookId: string, hmacSecret: string) {
    const webhook = webhooks.value.find(w => w.id === webhookId);
    if (webhook) {
      webhook.hmac_secret = hmacSecret;
    }
    if (settings.value?.webhooks) {
      const sWebhook = settings.value.webhooks.find(w => w.id === webhookId);
      if (sWebhook) {
        sWebhook.hmac_secret = hmacSecret;
      }
    }
  }

  function removeWebhook(webhookId: string) {
    webhooks.value = webhooks.value.filter(w => w.id !== webhookId);
    if (settings.value?.webhooks) {
      settings.value.webhooks = settings.value.webhooks.filter(w => w.id !== webhookId);
    }
  }

  function setWebhookEvents(data: WebhookEvent[]) {
    webhookEvents.value = data;
  }

  function clear() {
    settings.value = null;
    features.value = [];
    availableFeatures.value = [];
    limits.value = [];
    integrations.value = [];
    integrationTypes.value = [];
    notifications.value = null;
    notificationChannels.value = [];
    notificationCategories.value = [];
    notificationMatrix.value = [];
    digestFrequency.value = 'immediate';
    webhooks.value = [];
    webhookEvents.value = [];
  }

  return {
    settings,
    features,
    availableFeatures,
    limits,
    integrations,
    integrationTypes,
    notifications,
    notificationChannels,
    notificationCategories,
    notificationMatrix,
    digestFrequency,
    webhooks,
    webhookEvents,
    isLoaded,
    limitsLoaded,
    setSettings,
    setFeatures,
    setAvailableFeatures,
    updateFeature,
    setLimits,
    updateLimit,
    setPendingLimitValue,
    setIntegrations,
    addIntegration,
    updateIntegration,
    removeIntegration,
    setIntegrationTypes,
    setNotifications,
    updateNotificationMatrix,
    setDigestFrequency,
    setNotificationChannels,
    setNotificationCategories,
    setWebhooks,
    addWebhook,
    updateWebhook,
    updateWebhookHmacSecret,
    removeWebhook,
    setWebhookEvents,
    clear,
  };
});