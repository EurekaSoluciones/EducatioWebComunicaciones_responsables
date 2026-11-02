import { CreditCard, Wallet } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EureAlert, EureButtonPrimary, EureCard, EureInputGroup } from '../../components/ui/eure';
import FamilyFinanceFrame, { type FinancePageProps } from '../../components/family/FamilyFinanceFrame';
import FamilyFinanceTable, { type FinanceColumn } from '../../components/family/FamilyFinanceTable';
import { accountSearchText, accountStates, formatFinanceMoney, normalizePaymentAmount, type AccountRow } from '../../components/family/finance-table';

type Payment = { url: string; suggestedAmount: string; methods: { id: string; name: string; logo: string }[] };
type Props = FinancePageProps & { account: {
    studentId: string; success: string | null; totals: { month: number; today: number; overdue: number }; monthNotice: string;
    rows: AccountRow[]; payment: Payment;
} };
const columns: FinanceColumn<AccountRow>[] = [
    { key: 'reference', label: 'Id', value: (row) => row.reference, render: (row) => row.reference || '—', className: 'finance-reference' },
    { key: 'description', label: 'Descripción', value: (row) => row.description, render: (row) => row.description, className: 'finance-description' },
    { key: 'installment', label: 'Cuota', value: (row) => row.installment, render: (row) => row.installment, className: 'finance-installment' },
    { key: 'date', label: 'Vencimiento', value: (row) => Date.parse(row.date), render: (row) => <time dateTime={row.date}>{row.dateLabel}</time>, className: 'finance-date' },
    { key: 'amount', label: 'Importe', value: (row) => row.amount, render: (row) => formatFinanceMoney(row.amount), className: 'finance-money' },
    { key: 'balance', label: 'Saldo', value: (row) => row.balance, render: (row) => <strong>{formatFinanceMoney(row.balance)}</strong>, className: 'finance-money' },
    { key: 'state', label: 'Estado', value: (row) => accountStates[row.state], render: (row) => <span className={`finance-state is-${row.state}`}>{accountStates[row.state]}</span>, className: 'finance-status' },
];

export default function Account(props: Props) {
    const { account } = props;
    return <FamilyFinanceFrame props={props} studentId={account.studentId} section="account">
        {account.success && <EureAlert type="success" message={account.success} />}
        <EureCard className="finance-summary"><dl>
            <div><dt>A vencer este mes</dt><dd>{formatFinanceMoney(account.totals.month)}</dd><p>{account.monthNotice}</p></div>
            <div className={account.totals.today > 0 ? 'has-due' : ''}><dt>Vence hoy</dt><dd>{formatFinanceMoney(account.totals.today)}</dd><p>{account.totals.today > 0 ? 'Pagá hoy para evitar recargos.' : 'No tenés deuda con vencimiento hoy.'}</p></div>
            <div className={account.totals.overdue > 0 ? 'has-overdue' : ''}><dt>Vencido</dt><dd>{formatFinanceMoney(account.totals.overdue)}</dd><p>{account.totals.overdue > 0 ? 'Podrían existir recargos adicionales.' : 'No tenés cuotas atrasadas.'}</p></div>
        </dl></EureCard>
        <EureCard title="Detalle de la cuenta" icon={Wallet} className="finance-detail"><FamilyFinanceTable rows={account.rows} columns={columns} searchText={accountSearchText} caption="Cuenta corriente" emptyText="No hay movimientos para mostrar" totalsNote /></EureCard>
        {account.payment.methods.length > 0 && <OnlinePayment key={account.studentId} payment={account.payment} studentId={account.studentId} csrfToken={props.csrfToken} />}
    </FamilyFinanceFrame>;
}

function OnlinePayment({ payment, studentId, csrfToken }: { payment: Payment; studentId: string; csrfToken: string }) {
    const [amount, setAmount] = useState(payment.suggestedAmount);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);
    const methodInput = useRef<HTMLInputElement>(null);
    const submitting = useRef(false);
    const normalized = normalizePaymentAmount(amount);
    useEffect(() => {
        const reset = () => { submitting.current = false; setBusy(false); };
        window.addEventListener('pageshow', reset);
        return () => window.removeEventListener('pageshow', reset);
    }, []);
    return <EureCard title="Pagar en línea" icon={CreditCard} className="finance-payment" description="Indicá el importe y elegí un medio de pago.">
        <form method="POST" action={payment.url} onSubmit={(event) => {
            if (submitting.current) { event.preventDefault(); return; }
            if (!normalized) { event.preventDefault(); setError('Ingresá un importe mayor que cero, sin puntos de miles. Ejemplo: 12500,50.'); return; }
            const submitter = (event.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
            const method = submitter?.value || payment.methods[0].id;
            if (!payment.methods.some((item) => item.id === method) || !methodInput.current) { event.preventDefault(); return; }
            // Native POST preserves the existing provider redirects. A hidden field survives disabled buttons.
            methodInput.current.value = method;
            submitting.current = true; setError(''); setBusy(true);
        }}>
            <input type="hidden" name="_token" value={csrfToken} /><input type="hidden" name="alumno" value={studentId} />
            <input type="hidden" name="importe" value={normalized ?? ''} /><input ref={methodInput} type="hidden" name="metodo" defaultValue="" />
            <EureInputGroup label="Importe a pagar ($)" name="importeVisible" value={amount} inputMode="decimal" autoComplete="off" disabled={busy} error={Boolean(error)} errorMessage={error} helperText="Sin separadores de miles. Por ejemplo: 12500,50." onChange={(event) => { setAmount(event.target.value); setError(''); }} />
            <div className="finance-payment-methods">{payment.methods.map((method) => <EureButtonPrimary key={method.id} type="submit" value={method.id} disabled={busy}><img src={method.logo} alt="" />Pagar con {method.name}</EureButtonPrimary>)}</div>
            {busy && <p className="finance-payment-progress" role="status">Abriendo el medio de pago…</p>}
        </form>
    </EureCard>;
}
