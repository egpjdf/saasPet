import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useWorkspaceLimits } from '@/Composables/useWorkspaceLimits';

// Mock the store
vi.mock('@/Stores/workspaceSettings', () => ({
  useWorkspaceSettingsStore: () => ({
    limits: [],
    limitsLoaded: false,
    setLimits: vi.fn(),
    updateLimit: vi.fn(),
    setPendingLimitValue: vi.fn(),
  }),
}));

// Mock the API
vi.mock('@/Services/api/workspaceLimits', () => ({
  fetchWorkspaceLimits: vi.fn(),
  updateLimit: vi.fn(),
  resetLimit: vi.fn(),
}));

import { fetchWorkspaceLimits, updateLimit, resetLimit } from '@/Services/api/workspaceLimits';

describe('useWorkspaceLimits', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should load limits', async () => {
    const mockLimits = [
      { key: 'limit1', name: 'Limit 1', base_plan_limit: 100, current_override: null, new_value: null, unit: 'items', is_unlimited: false, description: '' },
      { key: 'limit2', name: 'Limit 2', base_plan_limit: 1000, current_override: 2000, new_value: null, unit: 'requests', is_unlimited: false, description: '' },
    ];
    
    (fetchWorkspaceLimits as any).mockResolvedValue(mockLimits);

    const { load, limits } = useWorkspaceLimits();
    await load();

    expect(fetchWorkspaceLimits).toHaveBeenCalled();
    expect(limits.value).toEqual(mockLimits);
  });

  it('should update limit with validation', async () => {
    const mockLimit = { key: 'limit1', name: 'Limit 1', base_plan_limit: 100, current_override: null, new_value: 150, unit: 'items', is_unlimited: false, description: '' };
    const updatedLimit = { ...mockLimit, current_override: 150, new_value: null };
    
    (updateLimit as any).mockResolvedValue(updatedLimit);

    const { update, limits } = useWorkspaceLimits();
    (limits.value as any) = [mockLimit];
    
    await update({ limit_key: 'limit1', new_value: 150 });

    expect(updateLimit).toHaveBeenCalledWith({ limit_key: 'limit1', new_value: 150 });
  });

  it('should reject values below base plan limit', async () => {
    const mockLimit = { key: 'limit1', name: 'Limit 1', base_plan_limit: 100, current_override: null, new_value: null, unit: 'items', is_unlimited: false, description: '' };
    
    const { update, limits } = useWorkspaceLimits();
    (limits.value as any) = [mockLimit];
    
    await expect(update({ limit_key: 'limit1', new_value: 50 })).rejects.toThrow('Valor mínimo: 100 items');
    expect(updateLimit).not.toHaveBeenCalled();
  });

  it('should reset limit', async () => {
    const mockLimit = { key: 'limit1', name: 'Limit 1', base_plan_limit: 100, current_override: 200, new_value: null, unit: 'items', is_unlimited: false, description: '' };
    const resetLimitResult = { ...mockLimit, current_override: null };
    
    (resetLimit as any).mockResolvedValue(resetLimitResult);

    const { reset, limits } = useWorkspaceLimits();
    (limits.value as any) = [mockLimit];
    
    await reset('limit1');

    expect(resetLimit).toHaveBeenCalledWith('limit1');
  });

  it('should compute limits with validation', () => {
    const { limits, limitsWithValidation } = useWorkspaceLimits();
    
    (limits.value as any) = [
      { key: 'limit1', name: 'Limit 1', base_plan_limit: 100, current_override: null, new_value: 150, unit: 'items', is_unlimited: false, description: '' },
      { key: 'limit2', name: 'Limit 2', base_plan_limit: 1000, current_override: 2000, new_value: 500, unit: 'requests', is_unlimited: false, description: '' },
      { key: 'limit3', name: 'Limit 3', base_plan_limit: 50, current_override: null, new_value: null, unit: 'users', is_unlimited: true, description: '' },
    ];

    const validated = limitsWithValidation.value;
    
    expect(validated[0].isValid).toBe(true); // 150 >= 100
    expect(validated[1].isValid).toBe(false); // 500 < 1000
    expect(validated[2].isValid).toBe(true); // unlimited
    expect(validated[1].validationMessage).toBe('Valor mínimo: 1000 requests');
    expect(validated[2].maxValue).toBe(999999999);
  });

  it('should detect pending changes', () => {
    const { limits, hasPendingChanges } = useWorkspaceLimits();
    
    (limits.value as any) = [
      { key: 'limit1', base_plan_limit: 100, current_override: null, new_value: 150 },
      { key: 'limit2', base_plan_limit: 1000, current_override: 2000, new_value: null },
    ];

    expect(hasPendingChanges.value).toBe(true);
  });

  it('should set and get pending values', () => {
    const { limits, setPendingValue, getPendingValue } = useWorkspaceLimits();
    
    (limits.value as any) = [
      { key: 'limit1', base_plan_limit: 100, current_override: null, new_value: null },
    ];

    setPendingValue('limit1', 200);
    expect(getPendingValue('limit1')).toBe(200);
    expect(limits.value[0].new_value).toBe(200);
  });
});