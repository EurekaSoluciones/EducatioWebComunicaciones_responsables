import { FileDown, Receipt } from 'lucide-react';
import { EureAlert, EureCard, EureHumanDate } from '../../components/ui/eure';
import FamilyFinanceFrame, { type FinancePageProps } from '../../components/family/FamilyFinanceFrame';
import FamilyFinanceTable, { type FinanceColumn } from '../../components/family/FamilyFinanceTable';
import { formatFinanceMoney, paymentSearchText, type PaymentRow } from '../../components/family/finance-table';

type Props = FinancePageProps & { payments: { studentId: string; success: string | null; rows: PaymentRow[] } };
const columns: FinanceColumn<PaymentRow>[] = [
    { key: 'reference', label: 'Comprobante', value: (row) => row.reference, render: (row) => <a href={row.url}>{row.reference || 'Ver comprobante'}</a>, className: 'finance-receipt' },
    { key: 'method', label: 'Forma de pago', value: (row) => row.method, render: (row) => row.method || '—', className: 'finance-description' },
    { key: 'amount', label: 'Importe', value: (row) => row.amount, render: (row) => <strong>{formatFinanceMoney(row.amount)}</strong>, className: 'finance-money' },
    { key: 'date', label: 'Fecha', value: (row) => row.date ? Date.parse(row.date) : 0, render: (row) => <EureHumanDate value={row.date} />, className: 'finance-payment-date' },
    { key: 'state', label: 'Estado', value: (row) => row.pending ? 'Pendiente' : 'Confirmado', render: (row) => <span className={`finance-state ${row.pending ? 'is-today' : 'is-paid'}`}>{row.pending ? 'Pendiente' : 'Confirmado'}</span>, className: 'finance-status' },
    { key: 'download', label: 'Descarga', value: () => '', sortable: false, render: (row) => !row.pending && <a className="finance-download" href={row.url} aria-label={`Descargar comprobante ${row.reference}`}><FileDown size={18} />PDF</a>, className: 'finance-download-cell' },
];
export default function Payments(props: Props) {
    const { payments } = props;
    return <FamilyFinanceFrame props={props} studentId={payments.studentId} section="payments">
        {payments.success && <EureAlert type="success" message={payments.success} />}
        <EureCard title="Pagos y comprobantes" icon={Receipt}><FamilyFinanceTable rows={payments.rows} columns={columns} searchText={paymentSearchText} caption="Pagos y comprobantes" emptyText="No hay pagos registrados" /></EureCard>
    </FamilyFinanceFrame>;
}
