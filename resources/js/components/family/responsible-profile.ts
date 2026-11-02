import type { HomeProps } from './types';

export type ResponsibleProfileProps = Omit<HomeProps, 'enrollmentResult'> & {
    profile: {
        responsibleId: string; login: string; email: string; phone: string; mobile: string; dni: string;
        studentPlans: Record<string, string>;
        url: string; photoUrl: string; updatePhotoUrl: string; success: string | null;
    };
};
