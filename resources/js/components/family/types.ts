import type { Theme } from '../../theme';

export type Person = { id: string; name: string; avatar: string | null };
export type Student = Person & {
    course: string; division: string; shift: string; academicContact: string;
    guardians: (Person & { role: string; url: string })[];
    enrollment: { periodoActivo: boolean; habilitaBoton: boolean; mensaje: string };
    links: Record<'profile' | 'photo' | 'received' | 'sent' | 'payments' | 'account' | 'documents' | 'attendance' | 'enrollment', string>;
};
export type Notifications = { received: number; replies: number; byStudent: { studentId: string; received: number; replies: number }[] };
export type Portal = {
    user: Person & { firstName: string; profileUrl: string; editUrl: string; passwordUrl: string };
    students: Student[]; labels: Record<'payments' | 'account' | 'documents', string>;
    boards: { id: string; name: string; url: string }[];
    board: { title: string; html: string; url: string } | null;
    notifications: Notifications;
};
export type HomeProps = {
    boardPreviews?: { id: string; title: string; html: string; url: string }[];
    portal: Portal; theme: Theme; institution: string; homeUrl: string; logoutUrl: string;
    institutionLogoUrl: string | null;
    notificationsUrl: string; loginUrl: string; csrfToken: string;
    enrollmentResult: { studentId: string; type: string; message: string } | null;
    // Only present while both home proposals coexist for review.
};
export function studentGroup(student: Student): string {
    return [student.course, student.division, student.shift].filter(Boolean).join(' · ') || 'Sin curso asignado';
}
