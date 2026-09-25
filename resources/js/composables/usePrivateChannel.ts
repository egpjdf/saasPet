import { ref, onMounted, onUnmounted, computed } from 'vue';
import type { PrivateChannel } from 'laravel-echo';

interface Notification {
    id: string;
    type: string;
    title: string;
    message: string;
    data?: Record<string, any>;
    read_at?: string;
    created_at: string;
}

interface UsePrivateChannelReturn {
    channel: PrivateChannel | null;
    notifications: Notification[];
    unreadCount: number;
    subscribe: (channelName: string) => void;
    unsubscribe: () => void;
    markAsRead: (notificationId: string) => void;
    markAllAsRead: () => void;
    onNotification: (callback: (notification: Notification) => void) => void;
}

export function usePrivateChannel(): UsePrivateChannelReturn {
    const channel = ref<PrivateChannel | null>(null);
    const notifications = ref<Notification[]>([]);
    const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length);

    const subscribe = (channelName: string) => {
        if (typeof window === 'undefined' || !window.Echo) return;

        channel.value = window.Echo.private(channelName);

        channel.value.listen('.notification', (notification: Notification) => {
            notifications.value.unshift(notification);
        });

        channel.value.listenForWhisper('typing', (data: any) => {
            // Handle typing indicators
        });

        channel.value.subscribed(() => {
            console.log('Subscribed to private channel:', channelName);
        });

        channel.value.error((error: any) => {
            console.error('Private channel error:', error);
        });
    };

    const unsubscribe = () => {
        if (channel.value) {
            window.Echo.leave(channel.value.name);
            channel.value = null;
        }
    };

    const markAsRead = (notificationId: string) => {
        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
    };

    const markAllAsRead = () => {
        const now = new Date().toISOString();
        notifications.value.forEach(n => {
            if (!n.read_at) {
                n.read_at = now;
            }
        });
    };

    const onNotification = (callback: (notification: Notification) => void) => {
        if (channel.value) {
            channel.value.listen('.notification', callback);
        }
    };

    onUnmounted(() => {
        unsubscribe();
    });

    return {
        channel,
        notifications,
        unreadCount,
        subscribe,
        unsubscribe,
        markAsRead,
        markAllAsRead,
        onNotification,
    };
}