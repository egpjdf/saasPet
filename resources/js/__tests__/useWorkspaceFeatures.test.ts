import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useWorkspaceFeatures } from '@/Composables/useWorkspaceFeatures';

// Mock the store
vi.mock('@/Stores/workspaceSettings', () => ({
  useWorkspaceSettingsStore: () => ({
    features: [],
    availableFeatures: [],
    setFeatures: vi.fn(),
    setAvailableFeatures: vi.fn(),
    updateFeature: vi.fn(),
  }),
}));

// Mock the API
vi.mock('@/Services/api/workspaceFeatures', () => ({
  fetchWorkspaceFeatures: vi.fn(),
  toggleFeature: vi.fn(),
  fetchAvailableFeatures: vi.fn(),
}));

import { fetchWorkspaceFeatures, toggleFeature, fetchAvailableFeatures } from '@/Services/api/workspaceFeatures';

describe('useWorkspaceFeatures', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should load features', async () => {
    const mockFeatures = [
      { key: 'feature1', name: 'Feature 1', enabled: true, required_plan: 'free', dependencies: [], category: 'core' },
      { key: 'feature2', name: 'Feature 2', enabled: false, required_plan: 'starter', dependencies: ['feature1'], category: 'core' },
    ];
    
    (fetchWorkspaceFeatures as any).mockResolvedValue(mockFeatures);

    const { load, features } = useWorkspaceFeatures();
    await load();

    expect(fetchWorkspaceFeatures).toHaveBeenCalled();
    expect(features.value).toEqual(mockFeatures);
  });

  it('should toggle feature', async () => {
    const mockFeature = { key: 'feature1', name: 'Feature 1', enabled: false, required_plan: 'free', dependencies: [], category: 'core' };
    const toggledFeature = { ...mockFeature, enabled: true };
    
    (toggleFeature as any).mockResolvedValue(toggledFeature);

    const { toggle, features } = useWorkspaceFeatures();
    // Set initial features
    (features.value as any) = [mockFeature];
    
    await toggle({ feature_key: 'feature1', enabled: true });

    expect(toggleFeature).toHaveBeenCalledWith({ feature_key: 'feature1', enabled: true });
  });

  it('should compute features by category', () => {
    const { featuresByCategory } = useWorkspaceFeatures();
    
    // Manually set features for testing
    (featuresByCategory as any).value = {
      core: [
        { key: 'f1', name: 'F1', category: 'core', enabled: true, required_plan: 'free', dependencies: [], description: '' },
        { key: 'f2', name: 'F2', category: 'core', enabled: false, required_plan: 'starter', dependencies: [], description: '' },
      ],
      communication: [
        { key: 'f3', name: 'F3', category: 'communication', enabled: true, required_plan: 'free', dependencies: [], description: '' },
      ],
    };

    expect(featuresByCategory.value.core).toHaveLength(2);
    expect(featuresByCategory.value.communication).toHaveLength(1);
  });

  it('should check can enable feature with dependencies', () => {
    const { features, canEnableFeature } = useWorkspaceFeatures();
    
    // Set up features with dependencies
    (features.value as any) = [
      { key: 'feature1', name: 'Feature 1', enabled: true, required_plan: 'free', dependencies: [], category: 'core', description: '' },
      { key: 'feature2', name: 'Feature 2', enabled: false, required_plan: 'starter', dependencies: ['feature1'], category: 'core', description: '' },
      { key: 'feature3', name: 'Feature 3', enabled: false, required_plan: 'professional', dependencies: ['feature1', 'feature2'], category: 'core', description: '' },
    ];

    const feature2 = { key: 'feature2', name: 'Feature 2', enabled: false, required_plan: 'starter', dependencies: ['feature1'], category: 'core', description: '' };
    const feature3 = { key: 'feature3', name: 'Feature 3', enabled: false, required_plan: 'professional', dependencies: ['feature1', 'feature2'], category: 'core', description: '' };

    expect(canEnableFeature.value(feature2)).toBe(true); // feature1 is enabled
    expect(canEnableFeature.value(feature3)).toBe(false); // feature2 is not enabled
  });

  it('should get dependent features', () => {
    const { features, getDependentFeatures } = useWorkspaceFeatures();
    
    (features.value as any) = [
      { key: 'feature1', name: 'Feature 1', enabled: true, required_plan: 'free', dependencies: [], category: 'core', description: '' },
      { key: 'feature2', name: 'Feature 2', enabled: false, required_plan: 'starter', dependencies: ['feature1'], category: 'core', description: '' },
      { key: 'feature3', name: 'Feature 3', enabled: false, required_plan: 'professional', dependencies: ['feature1'], category: 'core', description: '' },
    ];

    const dependents = getDependentFeatures.value('feature1');
    expect(dependents).toHaveLength(2);
    expect(dependents.map((f: any) => f.key)).toEqual(['feature2', 'feature3']);
  });
});