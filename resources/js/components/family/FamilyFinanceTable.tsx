import { ArrowDown, ArrowUp, ArrowUpDown, Search } from 'lucide-react';
import { useId, useMemo, useState, type ReactNode } from 'react';
import { EureButtonSecondary, EureInput } from '../ui/eure';
import { paginateReceived } from './received-table';
import { searchFinance, sortFinance } from './finance-table';

export type FinanceColumn<T> = { key: string; label: string; value: (row: T) => string | number; render: (row: T) => ReactNode; className?: string; sortable?: boolean };

export default function FamilyFinanceTable<T extends { id: string }>({ rows, columns, searchText, caption, emptyText, totalsNote = false }: {
    rows: T[]; columns: FinanceColumn<T>[]; searchText: (row: T) => string; caption: string; emptyText: string; totalsNote?: boolean;
}) {
    const id = useId();
    const [search, setSearch] = useState('');
    const [size, setSize] = useState(10), [page, setPage] = useState(1);
    const [sorting, setSorting] = useState({ key: 'date', descending: true });
    const sorted = useMemo(() => {
        const column = columns.find((item) => item.key === sorting.key)!;
        return sortFinance(searchFinance(rows, search, searchText), column.value, sorting.descending);
    }, [rows, columns, sorting, search, searchText]);
    const pagination = paginateReceived(sorted, page, size);
    return <>
        <div className="received-toolbar">
            <label className="received-sort" htmlFor={`${id}-size`}>Mostrar<select id={`${id}-size`} className="received-select" value={size} onChange={(event) => { setSize(Number(event.target.value)); setPage(1); }}>{[10, 25, 50, 100].map((amount) => <option key={amount} value={amount}>{amount}</option>)}</select></label>
            <p className="received-result-summary" role="status"><span>{pagination.from}–{pagination.to} de {pagination.total}{search.trim() && ` (de ${rows.length} cargados)`}</span>{search && <button type="button" className="received-clear-search" onClick={() => { setSearch(''); setPage(1); }}>Limpiar búsqueda</button>}</p>
            <div className="received-search"><label htmlFor={`${id}-search`}>Buscar:</label><EureInput id={`${id}-search`} type="search" value={search} placeholder="Buscar en todos los resultados…" onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
        </div>
        {search.trim() && totalsNote && <p className="finance-search-note">La búsqueda filtra el detalle; no cambia los totales ni el importe sugerido.</p>}
        <div className="received-table-wrap"><table className="received-table finance-table"><caption className="sr-only">{caption}</caption>
            <thead><tr>{columns.map((column) => <th key={column.key} scope="col" className={column.className} aria-sort={column.sortable === false ? undefined : sorting.key === column.key ? sorting.descending ? 'descending' : 'ascending' : 'none'}>
                {column.sortable === false ? column.label : <button type="button" className="received-column-sort" onClick={() => { setSorting({ key: column.key, descending: sorting.key === column.key ? !sorting.descending : false }); setPage(1); }}>{column.label}{sorting.key !== column.key ? <ArrowUpDown size={12} /> : sorting.descending ? <ArrowDown size={12} /> : <ArrowUp size={12} />}</button>}
            </th>)}</tr></thead>
            <tbody>{pagination.rows.map((row) => <tr key={row.id}>{columns.map((column) => <td key={column.key} className={column.className}>{column.render(row)}</td>)}</tr>)}</tbody>
        </table></div>
        {!pagination.total && <div className="received-empty"><Search size={26} aria-hidden="true" /><h3>{search.trim() ? 'No encontramos coincidencias' : emptyText}</h3>{search.trim() && <p>Probá otra búsqueda o limpiá el texto.</p>}</div>}
        {pagination.lastPage > 1 && <nav className="received-pagination" aria-label={`Páginas de ${caption}`}><EureButtonSecondary disabled={pagination.page === 1} onClick={() => setPage(pagination.page - 1)}>Anterior</EureButtonSecondary><label className="received-sort" htmlFor={`${id}-page`}>Página<select id={`${id}-page`} className="received-select" value={pagination.page} onChange={(event) => setPage(Number(event.target.value))}>{Array.from({ length: pagination.lastPage }, (_, index) => <option key={index + 1} value={index + 1}>{index + 1}</option>)}</select>de {pagination.lastPage}</label><EureButtonSecondary disabled={pagination.page === pagination.lastPage} onClick={() => setPage(pagination.page + 1)}>Siguiente</EureButtonSecondary></nav>}
    </>;
}
