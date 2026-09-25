// Vitest Setup File
import { vi } from 'vitest';

// Mock window.__PINIA__
Object.defineProperty(window, '__PINIA__', {
  value: {
    stores: {
      tenant: {
        organization: { id: 'org-1', slug: 'my-org', name: 'My Org' },
        workspace: { id: 'ws-1', slug: 'my-workspace', name: 'My Workspace' },
      },
    },
  },
  writable: true,
});

// Mock navigator.clipboard
Object.assign(navigator, {
  clipboard: {
    writeText: vi.fn().mockResolvedValue(undefined),
  },
});

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}));

// Suppress console errors in tests
const originalError = console.error;
console.error = (...args) => {
  if (args[0]?.includes?.('Warning: ReactDOM.render is no longer supported')) {
    return;
  }
  originalError.call(console, ...args);
};