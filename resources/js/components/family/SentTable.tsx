import { ArrowDown, ArrowUp, ArrowUpDown, Eye, Send } from 'lucide-react';
import { useId, useMemo, useState } from 'react';
import { EureAvatar } from '../eureka';
import { EureButtonSecondary, EureHumanDate, EureInput } from '../ui/eure';
import FamilySenderQuickCard from './FamilySenderQuickCard';
import { filterReceived, paginateReceived } from './received-table';
import { sentSearchText, sortSent } from './sent-table';
import SentMessageState from './SentMessageState';
import type { SentMessage, SentSort } from './sent-table';

// Local React table for Familias. This is not the TanStack-based EureDataTable port.
// All own sent messages are loaded once; table interactions never request another page.
export default function SentTable({ messages, studentName }: { messages: SentMessage[]; studentName: string }) {
    const id = useId();
    const [search, setSearch] = useState('');
    // 10 rows is what fits above the fold at the common desktop height.
    const [size, setSize] = useState(10);
    const [page, setPage] = useState(1);
    const [sorting, setSorting] = useState<{ key: SentSort; descending: boolean }>({ key: 'id', descending: true });
    const indexed = useMemo(() => messages.map((message) => ({ message, searchText: sentSearchText(message) })), [messages]);
    const filtered = useMemo(() => filterReceived(indexed, search).map((row) => row.message), [indexed, search]);
    const sorted = useMemo(() => sortSent(filtered, sorting.key, sorting.descending), [filtered, sorting]);
    const pagination = paginateReceived(sorted, page, size);
    const columns: { key: SentSort; label: string; className?: string }[] = [
        { key: 'id', label: 'N.º', className: 'received-number-cell' },
        { key: 'recipient', label: 'Destinatario', className: 'received-sender-cell' },
        { key: 'subject', label: 'Asunto' },
        { key: 'dateTime', label: 'Fecha', className: 'received-date-cell' },
        { key: 'status', label: 'Estado', className: 'sent-status-cell' },
    ];

    return <>
        <div className="received-toolbar">
            <label className="received-sort" htmlFor={`${id}-size`}>Mostrar
                <select id={`${id}-size`} className="received-select" value={size} onChange={(event) => { setSize(Number(event.target.value)); setPage(1); }}>
                    {[10, 15, 20, 25, 50, 100].map((amount) => <option key={amount} value={amount}>{amount}</option>)}
                </select>
            </label>
            <p className="received-result-summary" role="status" aria-live="polite">
                <span>{pagination.from}–{pagination.to} de {pagination.total} mensajes{search.trim() && ` (de ${messages.length} cargados)`}</span>
                {search && <button type="button" className="received-clear-search" onClick={() => { setSearch(''); setPage(1); }}>Limpiar búsqueda</button>}
            </p>
            <div className="received-search"><label htmlFor={`${id}-search`}>Buscar:</label><EureInput id={`${id}-search`} type="search" placeholder="Buscar en todos los resultados…" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
        </div>
        <div className="received-table-wrap"><table className="received-table">
            <caption className="sr-only">Tus mensajes enviados sobre {studentName}</caption>
            <thead><tr>{columns.map((column) => <th key={column.key} scope="col" className={column.className} aria-sort={sorting.key === column.key ? sorting.descending ? 'descending' : 'ascending' : 'none'}>
                <button type="button" className="received-column-sort" onClick={() => { setSorting({ key: column.key, descending: sorting.key === column.key ? !sorting.descending : false }); setPage(1); }}>
                    {column.label}{sorting.key !== column.key ? <ArrowUpDown size={12} /> : sorting.descending ? <ArrowDown size={12} /> : <ArrowUp size={12} />}
                </button>
            </th>)}<th scope="col" className="received-action-cell"><span className="sr-only">Acciones</span></th></tr></thead>
            <tbody>{pagination.rows.map((message) => <tr key={message.id} className={message.replyUnread ? 'is-unread' : ''}>
                <td className="received-number-cell"><a href={message.url}>{message.number}</a></td>
                <td className="received-sender-cell">
                    <FamilySenderQuickCard sender={message.recipient} studentName={studentName} className="received-sender">
                        <EureAvatar name={message.recipient.name} src={message.recipient.avatar ?? undefined} />
                        <span className="received-sender-text">
                            <span className="received-sender-name" title={message.recipient.name}>{message.recipient.name}</span>
                            {message.recipient.role && <span className="received-sender-type">{message.recipient.role}</span>}
                        </span>
                    </FamilySenderQuickCard>
                </td>
                <td className="received-subject-cell">
                    <a className="received-subject" href={message.url} title={message.subject}>{message.subject}</a>
                </td>
                <td className="received-date-cell"><EureHumanDate value={message.dateTime} /></td>
                <td className="sent-status-cell"><SentMessageState message={message} /></td>
                <td className="received-action-cell"><a className="received-view" href={message.url} aria-label={`Ver el mensaje ${message.number}: ${message.subject}`}><Eye size={16} aria-hidden="true" /></a></td>
            </tr>)}</tbody>
        </table></div>
        {pagination.total === 0 && <div className="received-empty"><Send size={28} aria-hidden="true" /><h3>No encontramos mensajes</h3><p>{search ? 'Probá con otra búsqueda o limpiá el texto.' : 'Todavía no enviaste mensajes por este alumno.'}</p></div>}
        {pagination.lastPage > 1 && <nav className="received-pagination" aria-label="Páginas de mensajes">
            <EureButtonSecondary disabled={pagination.page === 1} onClick={() => setPage(pagination.page - 1)}>Anterior</EureButtonSecondary>
            <label className="received-sort" htmlFor={`${id}-page`}>Página
                <select id={`${id}-page`} className="received-select" value={pagination.page} onChange={(event) => setPage(Number(event.target.value))}>
                    {Array.from({ length: pagination.lastPage }, (_, index) => <option key={index + 1} value={index + 1}>{index + 1}</option>)}
                </select>
                de {pagination.lastPage}
            </label>
            <EureButtonSecondary disabled={pagination.page === pagination.lastPage} onClick={() => setPage(pagination.page + 1)}>Siguiente</EureButtonSecondary>
        </nav>}
    </>;
}
