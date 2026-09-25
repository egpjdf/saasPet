import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface WebSocketState {
    connected: boolean;
    connecting: boolean;
    error: string | null;
    reconnectAttempts: number;
    maxReconnectAttempts: number;
}

export const useWebSocketStore = defineStore('websocket', () => {
    const state = ref<WebSocketState>({
        connected: false,
        connecting: false,
        error: null,
        reconnectAttempts: 0,
        maxReconnectAttempts: 5,
    });

    const isConnected = computed(() => state.value.connected);
    const isConnecting = computed(() => state.value.connecting);
    const connectionError = computed(() => state.value.error);

    const setConnected = (connected: boolean) => {
        state.value.connected = connected;
        state.value.connecting = false;
        if (connected) {
            state.value.reconnectAttempts = 0;
            state.value.error = null;
        }
    };

    const setConnecting = (connecting: boolean) => {
        state.value.connecting = connecting;
    };

    const setError = (error: string | null) => {
        state.value.error = error;
        state.value.connecting = false;
    };

    const incrementReconnectAttempts = () => {
        state.value.reconnectAttempts++;
    };

    const canReconnect = computed(() => 
        state.value.reconnectAttempts < state.value.maxReconnectAttempts
    );

    const reset = () => {
        state.value = {
            connected: false,
            connecting: false,
            error: null,
            reconnectAttempts: 0,
            maxReconnectAttempts: 5,
        };
    };

    return {
        state,
        isConnected,
        isConnecting,
        connectionError,
        setConnected,
        setConnecting,
        setError,
        incrementReconnectAttempts,
        canReconnect,
        reset,
    };
});