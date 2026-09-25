import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useWorkspaceIntegrations } from '@/Composables/useWorkspaceIntegrations';

// Mock the store
vi.mock('@/Stores/workspaceSettings', () => ({
  useWorkspaceSettingsStore: () => ({
    integrations: [],
    integrationTypes: [],
    setIntegrations: vi.fn(),
    addIntegration: vi.fn(),
    updateIntegration: vi.fn(),
    removeIntegration: vi.fn(),
    setIntegrationTypes: vi.fn(),
  }),
}));

// Mock the API
vi.mock('@/Services/api/workspaceIntegrations', () => ({
  fetchWorkspaceIntegrations: vi.fn(),
  fetchIntegration: vi.fn(),
  createIntegration: vi.fn(),
  updateIntegration: vi.fn(),
  deleteIntegration: vi.fn(),
  testIntegration: vi.fn(),
  testIntegrationConfig: vi.fn(),
  fetchIntegrationTypes: vi.fn(),
}));

import { 
  fetchWorkspaceIntegrations, 
  createIntegration, 
  updateIntegration, 
  deleteIntegration, 
  testIntegration,
  testIntegrationConfig,
  fetchIntegrationTypes 
} from '@/Services/api/workspaceIntegrations';

describe('useWorkspaceIntegrations', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should load integrations', async () => {
    const mockIntegrations = [
      { id: '1', type: 'whatsapp', name: 'WhatsApp', status: 'connected', config: {}, created_at: '', updated_at: '' },
      { id: '2', type: 'crm', name: 'CRM', status: 'disconnected', config: {}, created_at: '', updated_at: '' },
    ];
    
    (fetchWorkspaceIntegrations as any).mockResolvedValue(mockIntegrations);

    const { load, integrations } = useWorkspaceIntegrations();
    await load();

    expect(fetchWorkspaceIntegrations).toHaveBeenCalled();
    expect(integrations.value).toEqual(mockIntegrations);
  });

  it('should create integration', async () => {
    const newIntegration = { id: '3', type: 'erp', name: 'ERP', status: 'connected', config: {}, created_at: '', updated_at: '' };
    (createIntegration as any).mockResolvedValue(newIntegration);

    const { create, integrations } = useWorkspaceIntegrations();
    (integrations.value as any) = [];
    
    const result = await create({ type: 'erp', name: 'ERP', config: {} });

    expect(createIntegration).toHaveBeenCalled();
    expect(result).toEqual(newIntegration);
  });

  it('should update integration', async () => {
    const updatedIntegration = { id: '1', type: 'whatsapp', name: 'WhatsApp Updated', status: 'connected', config: { phone: '123' }, created_at: '', updated_at: '' };
    (updateIntegration as any).mockResolvedValue(updatedIntegration);

    const { update, integrations } = useWorkspaceIntegrations();
    (integrations.value as any) = [{ id: '1', type: 'whatsapp', name: 'WhatsApp', status: 'connected', config: {}, created_at: '', updated_at: '' }];
    
    const result = await update('1', { name: 'WhatsApp Updated', config: { phone: '123' } });

    expect(updateIntegration).toHaveBeenCalledWith('1', { name: 'WhatsApp Updated', config: { phone: '123' } });
    expect(result).toEqual(updatedIntegration);
  });

  it('should delete integration', async () => {
    (deleteIntegration as any).mockResolvedValue(undefined);

    const { remove, integrations } = useWorkspaceIntegrations();
    (integrations.value as any) = [
      { id: '1', type: 'whatsapp', name: 'WhatsApp', status: 'connected', config: {}, created_at: '', updated_at: '' },
      { id: '2', type: 'crm', name: 'CRM', status: 'disconnected', config: {}, created_at: '', updated_at: '' },
    ];
    
    await remove('1');

    expect(deleteIntegration).toHaveBeenCalledWith('1');
    expect(integrations.value).toHaveLength(1);
    expect(integrations.value[0].id).toBe('2');
  });

  it('should test existing integration', async () => {
    const testResult = { success: true, message: 'Connected', details: {} };
    (testIntegration as any).mockResolvedValue(testResult);

    const { test, integrations } = useWorkspaceIntegrations();
    (integrations.value as any) = [{ id: '1', type: 'whatsapp', name: 'WhatsApp', status: 'connected', config: {}, created_at: '', updated_at: '' }];
    
    const result = await test('1', { config: {} });

    expect(testIntegration).toHaveBeenCalledWith('1', { config: {} });
    expect(result).toEqual(testResult);
  });

  it('should test integration config without saving', async () => {
    const testResult = { success: true, message: 'Config valid', details: {} };
    (testIntegrationConfig as any).mockResolvedValue(testResult);

    const { testConfig } = useWorkspaceIntegrations();
    
    const result = await testConfig('whatsapp', { config: { phone: '123' } });

    expect(testIntegrationConfig).toHaveBeenCalledWith('whatsapp', { config: { phone: '123' } });
    expect(result).toEqual(testResult);
  });

  it('should filter integrations by status', () => {
    const { integrations, connectedIntegrations, disconnectedIntegrations, errorIntegrations } = useWorkspaceIntegrations();
    
    (integrations.value as any) = [
      { id: '1', type: 'whatsapp', name: 'WhatsApp', status: 'connected', config: {}, created_at: '', updated_at: '' },
      { id: '2', type: 'crm', name: 'CRM', status: 'disconnected', config: {}, created_at: '', updated_at: '' },
      { id: '3', type: 'erp', name: 'ERP', status: 'error', config: {}, created_at: '', updated_at: '' },
    ];

    expect(connectedIntegrations.value).toHaveLength(1);
    expect(disconnectedIntegrations.value).toHaveLength(1);
    expect(errorIntegrations.value).toHaveLength(1);
  });

  it('should load integration types', async () => {
    const mockTypes = [
      { type: 'whatsapp', name: 'WhatsApp', description: 'WhatsApp Business', config_schema: {} },
      { type: 'crm', name: 'CRM', description: 'CRM Integration', config_schema: {} },
    ];
    (fetchIntegrationTypes as any).mockResolvedValue(mockTypes);

    const { loadTypes, integrationTypes } = useWorkspaceIntegrations();
    await loadTypes();

    expect(fetchIntegrationTypes).toHaveBeenCalled();
    expect(integrationTypes.value).toEqual(mockTypes);
  });
});