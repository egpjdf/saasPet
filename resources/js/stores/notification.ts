import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message?: string;
  duration?: number;
  persistent?: boolean;
}

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<Notification[]>([]);

  let idCounter = 0;

  function generateId(): string {
    return `notification_${Date.now()}_${++idCounter}`;
  }

  function add(notification: Omit<Notification, 'id'>): string {
    const id = generateId();
    notifications.value.push({ ...notification, id });
    return id;
  }

  function remove(id: string) {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index !== -1) {
      notifications.value.splice(index, 1);
    }
  }

  function success(title: string, message?: string, duration = 5000) {
    return add({ type: 'success', title, message, duration });
  }

  function error(title: string, message?: string, duration = 8000) {
    return add({ type: 'error', title, message, duration });
  }

  function warning(title: string, message?: string, duration = 6000) {
    return add({ type: 'warning', title, message, duration });
  }

  function info(title: string, message?: string, duration = 5000) {
    return add({ type: 'info', title, message, duration });
  }

  function clear() {
    notifications.value = [];
  }

  return {
    notifications,
    add,
    remove,
    success,
    error,
    warning,
    info,
    clear,
  };
});