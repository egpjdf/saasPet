import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useWorkspaceNotifications } from '@/Composables/useWorkspaceNotifications';

// Mock the store
vi.mock('@/Stores/workspaceSettings', () => ({
  useWorkspaceSettingsStore: () => ({
    notifications: null,
    notificationChannels: [],
    notificationCategories: [],
    notificationMatrix: [],
    digestFrequency: 'immediate',
    setNotifications: vi.fn(),
    setNotificationChannels: vi.fn(),
    setNotificationCategories: vi.fn(),
    updateNotificationMatrix: vi.fn(),
    setDigestFrequency: vi.fn(),
  }),
}));

// Mock the API
vi.mock('@/Services/api/workspaceNotifications', () => ({
  fetchNotificationSettings: vi.fn(),
  updateNotificationMatrix: vi.fn(),
  updateDigestFrequency: vi.fn(),
  fetchNotificationChannels: vi.fn(),
  fetchNotificationCategories: vi.fn(),
}));

import { 
  fetchNotificationSettings, 
  updateNotificationMatrix, 
  updateDigestFrequency,
  fetchNotificationChannels,
  fetchNotificationCategories 
} from '@/Services/api/workspaceNotifications';

describe('useWorkspaceNotifications', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('should load notification settings', async () => {
    const mockSettings = {
      channels: [{ key: 'email', name: 'Email', description: 'Email notifications', enabled: true }],
      categories: [{ key: 'orders', name: 'Pedidos', description: 'Order notifications' }],
      matrix: [{ channel_key: 'email', category_key: 'orders', enabled: true }],
      digest_frequency: 'daily',
    };
    
    (fetchNotificationSettings as any).mockResolvedValue(mockSettings);
    (fetchNotificationChannels as any).mockResolvedValue(mockSettings.channels);
    (fetchNotificationCategories as any).mockResolvedValue(mockSettings.categories);

    const { load, settings, channels, categories, digestFrequency } = useWorkspaceNotifications();
    await load();

    expect(fetchNotificationSettings).toHaveBeenCalled();
    expect(settings.value).toEqual(mockSettings);
    expect(channels.value).toEqual(mockSettings.channels);
    expect(categories.value).toEqual(mockSettings.categories);
    expect(digestFrequency.value).toBe('daily');
  });

  it('should update matrix with debounce', async () => {
    (updateNotificationMatrix as any).mockResolvedValue({});

    const { updateMatrixDebounced, matrixMap } = useWorkspaceNotifications();
    
    // Set initial matrix
    (matrixMap as any).value = { email: { orders: false } };
    
    updateMatrixDebounced({ channel_key: 'email', category_key: 'orders', enabled: true });
    
    // Should not call API immediately due to debounce
    expect(updateNotificationMatrix).not.toHaveBeenCalled();
    
    // Fast-forward timer
    vi.advanceTimersByTime(500);
    
    // Now should call API
    expect(updateNotificationMatrix).toHaveBeenCalledWith({ channel_key: 'email', category_key: 'orders', enabled: true });
  });

  it('should update digest frequency', async () => {
    (updateDigestFrequency as any).mockResolvedValue({});

    const { updateDigest, digestFrequency } = useWorkspaceNotifications();
    
    await updateDigest({ frequency: 'weekly' });

    expect(updateDigestFrequency).toHaveBeenCalledWith({ frequency: 'weekly' });
    expect(digestFrequency.value).toBe('weekly');
  });

  it('should get matrix value', () => {
    const { matrixMap, getMatrixValue } = useWorkspaceNotifications();
    
    (matrixMap.value as any) = {
      email: { orders: true, marketing: false },
      sms: { orders: false },
    };

    expect(getMatrixValue('email', 'orders')).toBe(true);
    expect(getMatrixValue('email', 'marketing')).toBe(false);
    expect(getMatrixValue('sms', 'orders')).toBe(false);
    expect(getMatrixValue('push', 'orders')).toBe(false); // non-existent
  });

  it('should set matrix value', () => {
    const { matrixMap, setMatrixValue } = useWorkspaceNotifications();
    
    (matrixMap.value as any) = { email: { orders: false } };
    
    setMatrixValue('email', 'orders', true);
    
    expect(matrixMap.value.email.orders).toBe(true);
  });

  it('should toggle matrix value', () => {
    const { matrixMap, toggleMatrixValue } = useWorkspaceNotifications();
    
    (matrixMap.value as any) = { email: { orders: false } };
    
    toggleMatrixValue('email', 'orders');
    expect(matrixMap.value.email.orders).toBe(true);
    
    toggleMatrixValue('email', 'orders');
    expect(matrixMap.value.email.orders).toBe(false);
  });
});