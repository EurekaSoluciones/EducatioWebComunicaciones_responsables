import { CreditCard } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { flushSync } from 'react-dom';
import EureButtonPrimary from '../ui/eure/EureButtonPrimary';
import EureCard from '../ui/eure/EureCard';
import EureInputGroup from '../ui/eure/EureInputGroup';
import { normalizePaymentAmount } from './finance-table';

export type Payment = { url: string; suggestedAmount: string; error?: string | null; methods: { id: string; name: string; logo: string | null }[] };

export default function FamilyOnlinePayment({ payment, studentId, csrfToken }: { payment: Payment; studentId: string; csrfToken: string }) {
    const [amount, setAmount] = useState(payment.suggestedAmount);
    const [error, setError] = useState(payment.error ?? '');
    const [busy, setBusy] = useState(false);
    const [selectedMethod, setSelectedMethod] = useState(payment.methods[0]?.id ?? '');
    const submitting = useRef(false);
    const normalized = normalizePaymentAmount(amount);
    useEffect(() => {
        const reset = () => { submitting.current = false; setBusy(false); };
        window.addEventListener('pageshow', reset);
        return () => window.removeEventListener('pageshow', reset);
    }, []);
    const supervielle = payment.methods.length === 1 && payment.methods[0].id === 'supervielle';
    return <EureCard title="Pagar en línea" icon={CreditCard} className="finance-payment" description={supervielle ? 'Indicá el importe para continuar a Supervielle.' : 'Indicá el importe y elegí un medio de pago.'}>
        <form method="POST" action={payment.url} onSubmit={(event) => {
            if (submitting.current) { event.preventDefault(); return; }
            if (!normalized) { event.preventDefault(); setError('Ingresá un importe mayor que cero, sin puntos de miles. Ejemplo: 12500,50.'); return; }
            const submitter = (event.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
            const method = submitter?.value || payment.methods[0].id;
            if (!payment.methods.some((item) => item.id === method)) {
                event.preventDefault(); setError('Elegí un medio de pago válido.'); return;
            }
            // Commit the selected method before the browser serializes the native POST.
            // An imperative .value on a hidden input with defaultValue="" is reset by React.
            submitting.current = true;
            flushSync(() => { setSelectedMethod(method); setError(''); setBusy(true); });
        }}>
            <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="alumno" value={studentId} />
            <input type="hidden" name="importe" value={normalized ?? ''} /><input type="hidden" name="metodo" value={selectedMethod} />
            <EureInputGroup label="Importe a pagar ($)" name="importeVisible" value={amount} inputMode="decimal" autoComplete="off" disabled={busy} error={Boolean(error)} errorMessage={error} helperText="Sin separadores de miles. Por ejemplo: 12500,50." onChange={(event) => { setAmount(event.target.value); setError(''); }} />
            <div className="finance-payment-methods">{payment.methods.map((method) => <EureButtonPrimary key={method.id} type="submit" value={method.id} aria-disabled={busy} aria-busy={busy}>{method.logo ? <img src={method.logo} alt="" /> : <CreditCard size={18} aria-hidden="true" />}Pagar con {method.name}</EureButtonPrimary>)}</div>
            {busy && <p className="finance-payment-progress" role="status">{supervielle ? 'Preparando la confirmación…' : 'Abriendo el medio de pago…'}</p>}
        </form>
    </EureCard>;
}
