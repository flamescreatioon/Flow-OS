import { useCallback, useSyncExternalStore } from 'react';

const STORAGE_KEY = 'flowos.sidebarCollapsed';
const CHANGE_EVENT = 'flowos:sidebar-collapsed-changed';

function subscribe(onChange: () => void): () => void {
  // 'storage' covers other tabs; the local event covers same-tab writes.
  window.addEventListener('storage', onChange);
  window.addEventListener(CHANGE_EVENT, onChange);
  return () => {
    window.removeEventListener('storage', onChange);
    window.removeEventListener(CHANGE_EVENT, onChange);
  };
}

function getSnapshot(): boolean {
  try {
    return window.localStorage.getItem(STORAGE_KEY) === 'true';
  } catch {
    return false;
  }
}

function getServerSnapshot(): boolean {
  return false;
}

/**
 * Collapsed state of the app sidebar. Hydration-safe: the server and the
 * first client render both start expanded, then the stored preference
 * (a non-sensitive UI choice) is applied via useSyncExternalStore.
 */
export function useSidebarCollapsed(): {
  collapsed: boolean;
  toggleCollapsed: () => void;
} {
  const collapsed = useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);

  const toggleCollapsed = useCallback(() => {
    try {
      window.localStorage.setItem(STORAGE_KEY, String(!getSnapshot()));
      window.dispatchEvent(new Event(CHANGE_EVENT));
    } catch {
      // Preference persistence failures keep the current rail state.
    }
  }, []);

  return { collapsed, toggleCollapsed };
}
