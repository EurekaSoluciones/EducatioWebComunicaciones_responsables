import { ArrowRight, CreditCard } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EureAlert, EureButtonPrimary, EureCard } from '../../components/ui/eure';
import FamilyFinanceFrame, { type FinancePageProps } from '../../components/family/FamilyFinanceFrame';
import { formatFinanceMoney } from '../../components/family/finance-table';

type Props = FinancePageProps & { payment: {
    studentId: string; amount: number; dueDate: string; dueLabel: string;
    submitUrl: string; backUrl: string; canContinue: boolean; published: boolean; error: string | null;
} };

export default function SuperviellePayment(props: Props) {
    const { payment } = props;
    const student = props.portal.students.find((item) => item.id === payment.studentId)!;
    const [busy, setBusy] = useState(false);
    const submitting = useRef(false);
    const canContinue = payment.canContinue && !payment.error;
    useEffect(() => {
        const restore = () => { submitting.current = false; setBusy(false); };
        window.addEventListener('pageshow', restore);
        return () => window.removeEventListener('pageshow', restore);
    }, []);

    return <FamilyFinanceFrame props={props} studentId={payment.studentId} section="account">
        <EureCard title="Confirmar pago con Supervielle" icon={CreditCard} className="finance-payment-confirm"
            description="Revisá los datos antes de continuar al sitio del banco.">
            {payment.error && <EureAlert type="warning" message={payment.error} />}
            <dl className="finance-payment-details">
                <div><dt>Estudiante</dt><dd>{student.name}</dd></div>
                <div><dt>Importe a pagar</dt><dd className="finance-payment-amount">{formatFinanceMoney(payment.amount)}</dd></div>
                <div><dt>Vencimiento del enlace</dt><dd><time dateTime={payment.dueDate}>{payment.dueLabel}</time></dd></div>
            </dl>
            <p>El pago se completa en Supervielle. Continuar no confirma que el importe haya sido abonado.</p>
            <form method="post" action={payment.submitUrl} onSubmit={(event) => {
                if (submitting.current || !canContinue) { event.preventDefault(); return; }
                submitting.current = true; setBusy(true);
            }}>
                <input type="hidden" name="_token" value={props.csrfToken} />
                <a className="eureui-button-secondary" href={payment.backUrl}>Volver a cuenta corriente</a>
                <EureButtonPrimary type="submit" disabled={!canContinue} aria-disabled={busy || !canContinue} aria-busy={busy}>
                    {busy ? 'Abriendo Supervielle…' : payment.published ? 'Abrir enlace de Supervielle' : 'Continuar a Supervielle'}
                    <ArrowRight size={17} aria-hidden="true" />
                </EureButtonPrimary>
            </form>
            {busy && <p role="status">Esperá mientras preparamos el acceso al banco.</p>}
        </EureCard>
    </FamilyFinanceFrame>;
}
