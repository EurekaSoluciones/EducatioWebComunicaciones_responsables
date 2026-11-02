import { useCallback, useEffect, useRef, useState } from 'react';
import { announceReading, mergeReadStates, watchReadingChanges } from './reading-sync';

export function useAnnounceReading(scope: string, messageKey: string, confirmed: boolean) {
    const last = useRef('');
    useEffect(() => {
        const key = `${scope}:${messageKey}`;
        if (confirmed && last.current !== key) { last.current = key; announceReading(scope); }
    }, [scope, messageKey, confirmed]);
}

export default function useCommunicationStates<T extends { id: string }>(initial: T[], url: string, scope: string, userId: string, onSynced: () => void) {
    const [snapshot, setSnapshot] = useState({ source: initial, rows: initial });
    const [error, setError] = useState(false);
    const [retry, setRetry] = useState(0);
    useEffect(() => {
        let disposed = false;
        let timer: ReturnType<typeof setTimeout> | undefined;
        let pending: AbortController | null = null;
        let again = false;
        const sync = async () => {
            if (disposed || document.hidden) return;
            if (pending) { again = true; return; }
            const controller = new AbortController(); pending = controller; again = false;
            const timeout = setTimeout(() => controller.abort(), 10000);
            try {
                const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store', signal: controller.signal });
                if (!response.ok) throw new Error('Unable to refresh reading states');
                const result = await response.json() as { userId: string; states: { id: string }[] };
                if (disposed) return;
                if (result.userId !== userId) { window.location.reload(); return; }
                if (!again) {
                    setSnapshot({ source: initial, rows: mergeReadStates(initial, result.states) });
                    setError(false); onSynced();
                }
            } catch { if (!disposed) setError(true); }
            finally {
                clearTimeout(timeout); pending = null;
                if (!disposed && again) schedule();
            }
        };
        // Coalesce focus + visibility + BFCache and duplicate cross-tab signals.
        const schedule = () => { clearTimeout(timer); timer = setTimeout(() => void sync(), 120); };
        const stopRequest = () => { clearTimeout(timer); pending?.abort(); };
        const unwatch = watchReadingChanges(scope, schedule);
        window.addEventListener('pagehide', stopRequest);
        schedule(); // Also revalidate cached Inertia history restored by mounting the page.
        return () => { disposed = true; clearTimeout(timer); pending?.abort(); unwatch(); window.removeEventListener('pagehide', stopRequest); };
    }, [initial, url, scope, userId, onSynced, retry]);
    const refresh = useCallback(() => setRetry((value) => value + 1), []);
    return { messages: snapshot.source === initial ? snapshot.rows : initial, error, refresh };
}
