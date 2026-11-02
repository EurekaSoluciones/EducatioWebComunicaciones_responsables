// Cross-tab messages are invalidation hints only. The server remains the source of truth.
const keyFor = (scope: string) => `family-reading:${scope}`;

export function announceReading(scope: string) {
    const key = keyFor(scope);
    const signal = { version: 1, nonce: `${Date.now()}:${Math.random()}` };
    try {
        const channel = new BroadcastChannel(key);
        channel.postMessage(signal);
        channel.close();
    } catch { /* Storage events cover browsers without BroadcastChannel. */ }
    try { localStorage.setItem(key, JSON.stringify(signal)); }
    catch { /* Returning/focusing still refreshes when browser storage is unavailable. */ }
}

export function watchReadingChanges(scope: string, refresh: () => void): () => void {
    const key = keyFor(scope);
    let nonce = '';
    let channel: BroadcastChannel | null = null;
    const visible = () => { if (!document.hidden) refresh(); };
    const restored = (event: PageTransitionEvent) => { if (event.persisted) visible(); };
    const signal = (data: unknown) => {
        if (!data || typeof data !== 'object' || !('version' in data) || data.version !== 1
            || !('nonce' in data) || typeof data.nonce !== 'string' || data.nonce === nonce) return;
        nonce = data.nonce;
        visible();
    };
    const storage = (event: StorageEvent) => {
        if (event.key !== key || !event.newValue) return;
        try { signal(JSON.parse(event.newValue)); } catch { /* Ignore unrelated or invalid data. */ }
    };
    try { channel = new BroadcastChannel(key); channel.onmessage = (event) => signal(event.data); } catch { /* Storage fallback. */ }
    window.addEventListener('focus', visible);
    window.addEventListener('pageshow', restored);
    document.addEventListener('visibilitychange', visible);
    window.addEventListener('storage', storage);
    return () => {
        channel?.close();
        window.removeEventListener('focus', visible);
        window.removeEventListener('pageshow', restored);
        document.removeEventListener('visibilitychange', visible);
        window.removeEventListener('storage', storage);
    };
}

export function mergeReadStates<T extends { id: string }, S extends { id: string }>(rows: T[], states: S[]): (T & S)[] {
    const byId = new Map(states.map((state) => [state.id, state]));
    // Keep loaded content/order; remove records that no longer belong to this scope.
    // Newly arriving messages are intentionally not added by a state-only refresh.
    return rows.flatMap((row) => { const state = byId.get(row.id); return state ? [{ ...row, ...state }] : []; });
}
