import { Wallet } from 'lucide-react';
import { EureAlert, EureCard } from '../../components/ui/eure';
import FamilyFinanceFrame, { type FinancePageProps } from '../../components/family/FamilyFinanceFrame';
import FamilyFinanceTable, { type FinanceColumn } from '../../components/family/FamilyFinanceTable';
import { accountSearchText, accountStates, formatFinanceMoney, type AccountRow } from '../../components/family/finance-table';

import FamilyOnlinePayment, { type Payment } from '../../components/family/FamilyOnlinePayment';
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
        {account.payment.methods.length > 0 && <FamilyOnlinePayment key={account.studentId} payment={account.payment} studentId={account.studentId} csrfToken={props.csrfToken} />}
    </FamilyFinanceFrame>;
}
