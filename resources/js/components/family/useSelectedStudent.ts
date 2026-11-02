import { useEffect, useState } from 'react';
import type { Student } from './types';

export default function useSelectedStudent(students: Student[], key: string) {
    const [id, setId] = useState(() => {
        try { return sessionStorage.getItem(key) ?? students[0]?.id ?? ''; }
        catch { return students[0]?.id ?? ''; }
    });
    const selected = students.find((student) => student.id === id) ?? students[0];
    useEffect(() => {
        try { if (selected) sessionStorage.setItem(key, selected.id); }
        catch { /* Navigation also works when browser storage is disabled. */ }
    }, [key, selected?.id]);
    return { selected, select: setId };
}
