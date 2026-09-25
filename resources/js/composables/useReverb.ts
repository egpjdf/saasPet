import { ref, onMounted, onUnmounted, watch } from 'vue';
import type { Channel, PresenceChannel, PrivateChannel } from 'laravel-echo';

interface UseReverbOptions {
    channel?: string;
    private?: boolean;
    presence?: boolean;
}

interface UseReverbReturn {
    channel: Channel | PrivateChannel | PresenceChannel | null;
    isConnected: boolean;
    connect: () => void;
    disconnect: () => void;
    listen: (event: string, callback: (data: any) => void) => void;
    listenForWhisper: (event: string, callback: (data: any) => void) => void;
}

export function useReverb(options: UseReverbOptions = {}): UseReverbReturn {
    const { channel: channelName, private: isPrivate = false, presence = false } = options;
    
    const channel = ref<Channel | PrivateChannel | PresenceChannel | null>(null);
    const isConnected = ref(false);
    let echoInstance: any = null;

    const connect = () => {
        if (typeof window === 'undefined' || !window.Echo) return;

        echoInstance = window.Echo;

        if (channelName) {
            if (presence) {
                channel.value = echoInstance.join(channelName);
            } else if (isPrivate) {
                channel.value = echoInstance.private(channelName);
            } else {
                channel.value = echoInstance.channel(channelName);
            }

            channel.value.subscribed(() => {
                isConnected.value = true;
            });

            channel.value.error((error: any) => {
                console.error('Reverb channel error:', error);
                isConnected.value = false;
            });
        }

        // Listen for connection state
        echoInstance.connector.pusher.connection.bind('connected', () => {
            isConnected.value = true;
        });

        echoInstance.connector.pusher.connection.bind('disconnected', () => {
            isConnected.value = false;
        });
    };

    const disconnect = () => {
        if (channel.value) {
            if (presence && 'leave' in channel.value) {
                (channel.value as PresenceChannel).leave();
            } else {
                window.Echo.leaveChannel(channelName!);
            }
            channel.value = null;
        }
        isConnected.value = false;
    };

    const listen = (event: string, callback: (data: any) => void) => {
        if (channel.value) {
            channel.value.listen(event, callback);
        }
    };

    const listenForWhisper = (event: string, callback: (data: any) => void) => {
        if (channel.value && 'listenForWhisper' in channel.value) {
            (channel.value as PresenceChannel).listenForWhisper(event, callback);
        }
    };

    onMounted(() => {
        connect();
    });

    onUnmounted(() => {
        disconnect();
    });

    // Reconnect if channel name changes
    watch(() => channelName, () => {
        disconnect();
        connect();
    });

    return {
        channel,
        isConnected,
        connect,
        disconnect,
        listen,
        listenForWhisper,
    };
}