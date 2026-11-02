import { ArrowDown, ArrowUp, ArrowUpDown, Eye, Inbox, Paperclip } from 'lucide-react';
import { useId, useMemo, useState } from 'react';
import { EureAvatar } from '../eureka';
import { EureButtonSecondary, EureHumanDate, EureInput } from '../ui/eure';
import FamilySenderQuickCard from './FamilySenderQuickCard';
import { filterReceived, paginateReceived, receivedSearchText, sortReceived } from './received-table';
import type { ReceivedMessage, ReceivedSort } from './received-table';

// Local React table for Familias. This is not the TanStack-based EureDataTable port.
// One line per communication: the subject column carries the opening of the message,
// so the width between the sender and the date is never dead space.
export default function ReceivedTable({ messages, studentName }: { messages: ReceivedMessage[]; studentName: string }) {
    const id = useId();
    const [search, setSearch] = useState('');
    // 10 rows is what fits above the fold at the common desktop height.
    const [size, setSize] = useState(10);
    const [page, setPage] = useState(1);
    const [sorting, setSorting] = useState<{ key: ReceivedSort; descending: boolean }>({ key: 'id', descending: true });
    const indexed = useMemo(() => messages.map((message) => ({ message, searchText: receivedSearchText(message) })), [messages]);
    const filtered = useMemo(() => filterReceived(indexed, search).map((row) => row.message), [indexed, search]);
    const sorted = useMemo(() => sortReceived(filtered, sorting.key, sorting.descending), [filtered, sorting]);
    const pagination = paginateReceived(sorted, page, size);
    const columns: { key: ReceivedSort; label: string; className?: string }[] = [
        { key: 'id', label: 'N.º', className: 'received-number-cell' },
        { key: 'sender', label: 'Remitente', className: 'received-sender-cell' },
        { key: 'subject', label: 'Asunto' },
        { key: 'dateTime', label: 'Fecha', className: 'received-date-cell' },
        { key: 'unread', label: 'Estado', className: 'received-status-cell' },
    ];

    return <>
        <div className="received-toolbar">
            <label className="received-sort" htmlFor={`${id}-size`}>Mostrar
                <select id={`${id}-size`} className="received-select" value={size} onChange={(event) => { setSize(Number(event.target.value)); setPage(1); }}>
                    {[10, 15, 20, 25, 50, 100].map((amount) => <option key={amount} value={amount}>{amount}</option>)}
                </select>
            </label>
            <p className="received-result-summary" role="status" aria-live="polite">
                <span>{pagination.from}–{pagination.to} de {pagination.total} comunicaciones{search.trim() && ` (de ${messages.length} cargadas)`}</span>
                {search && <button type="button" className="received-clear-search" onClick={() => { setSearch(''); setPage(1); }}>Limpiar búsqueda</button>}
            </p>
            <div className="received-search"><label htmlFor={`${id}-search`}>Buscar:</label><EureInput id={`${id}-search`} type="search" placeholder="Buscar en todos los resultados…" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
        </div>
        <div className="received-table-wrap"><table className="received-table">
            <caption className="sr-only">Comunicaciones recibidas de {studentName}</caption>
            <thead><tr>{columns.map((column) => <th key={column.key} scope="col" className={column.className} aria-sort={sorting.key === column.key ? sorting.descending ? 'descending' : 'ascending' : 'none'}>
                <button type="button" className="received-column-sort" onClick={() => { setSorting({ key: column.key, descending: sorting.key === column.key ? !sorting.descending : false }); setPage(1); }}>
                    {column.label}{sorting.key !== column.key ? <ArrowUpDown size={12} /> : sorting.descending ? <ArrowDown size={12} /> : <ArrowUp size={12} />}
                </button>
            </th>)}<th scope="col" className="received-action-cell"><span className="sr-only">Acciones</span></th></tr></thead>
            <tbody>{pagination.rows.map((message) => <tr key={message.id} className={message.unread ? 'is-unread' : ''}>
                <td className="received-number-cell"><a href={message.url}>{message.number}</a></td>
                <td className="received-sender-cell">
                    <FamilySenderQuickCard sender={message.sender} studentName={studentName} className="received-sender">
                        <EureAvatar name={message.sender.name} src={message.sender.avatar ?? undefined} />
                        <span className="received-sender-text">
                            <span className="received-sender-name" title={message.sender.name}>{message.sender.name}</span>
                            {message.type && <span className="received-sender-type">{message.type}</span>}
                        </span>
                    </FamilySenderQuickCard>
                </td>
                <td className="received-subject-cell">
                    <a className="received-subject" href={message.url} title={message.subject}>{message.subject}</a>
                    {message.attachmentCount > 0 && <span className="received-clip" title={`${message.attachmentCount} ${message.attachmentCount === 1 ? 'adjunto' : 'adjuntos'}`}><Paperclip size={12} aria-hidden="true" /><span className="sr-only">{message.attachmentCount} {message.attachmentCount === 1 ? 'adjunto' : 'adjuntos'}</span></span>}
                    {message.preview && <span className="received-preview" aria-hidden="true">— {message.preview}</span>}
                </td>
                <td className="received-date-cell"><EureHumanDate value={message.dateTime} /></td>
                <td className="received-status-cell"><span className={`received-status ${message.unread ? 'is-unread' : ''}`}>{message.unread ? 'Sin leer' : 'Leída'}</span></td>
                <td className="received-action-cell"><a className="received-view" href={message.url} aria-label={`Ver la comunicación ${message.number}: ${message.subject}`}><Eye size={16} aria-hidden="true" /></a></td>
            </tr>)}</tbody>
        </table></div>
        {pagination.total === 0 && <div className="received-empty"><Inbox size={28} aria-hidden="true" /><h3>No encontramos comunicaciones</h3><p>{search ? 'Probá con otra búsqueda o limpiá el texto.' : 'Probá ampliar las fechas o cambiar los filtros.'}</p></div>}
        {pagination.lastPage > 1 && <nav className="received-pagination" aria-label="Páginas de comunicaciones">
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
