import { ref, onMounted, onUnmounted, computed } from 'vue';
import type { PresenceChannel } from 'laravel-echo';

interface UserPresence {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    role: string;
    last_active: string;
}

interface UsePresenceReturn {
    users: UserPresence[];
    currentUser: UserPresence | null;
    join: (channelName: string) => void;
    leave: () => void;
    isJoined: boolean;
    here: (callback: (users: UserPresence[]) => void) => void;
    joining: (callback: (user: UserPresence) => void) => void;
    leaving: (callback: (user: UserPresence) => void) => void;
}

export function usePresence(): UsePresenceReturn {
    const users = ref<UserPresence[]>([]);
    const currentUser = ref<UserPresence | null>(null);
    const isJoined = ref(false);
    let channel: PresenceChannel | null = null;

    const join = (channelName: string) => {
        if (typeof window === 'undefined' || !window.Echo) return;

        channel = window.Echo.join(channelName);

        channel.here((joinedUsers: UserPresence[]) => {
            users.value = joinedUsers;
            isJoined.value = true;
        });

        channel.joining((user: UserPresence) => {
            users.value.push(user);
        });

        channel.leaving((user: UserPresence) => {
            users.value = users.value.filter(u => u.id !== user.id);
        });

        channel.error((error: any) => {
            console.error('Presence channel error:', error);
            isJoined.value = false;
        });
    };

    const leave = () => {
        if (channel) {
            channel.leave();
            channel = null;
            users.value = [];
            isJoined.value = false;
        }
    };

    const here = (callback: (users: UserPresence[]) => void) => {
        if (channel) {
            channel.here(callback);
        }
    };

    const joining = (callback: (user: UserPresence) => void) => {
        if (channel) {
            channel.joining(callback);
        }
    };

    const leaving = (callback: (user: UserPresence) => void) => {
        if (channel) {
            channel.leaving(callback);
        }
    };

    onUnmounted(() => {
        leave();
    });

    return {
        users,
        currentUser,
        join,
        leave,
        isJoined,
        here,
        joining,
        leaving,
    };
}