import { Head } from '@inertiajs/react';
import { ArrowDown, ArrowLeft, ArrowUp, ArrowUpDown, CalendarDays, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { EureAvatar } from '../../components/eureka';
import { EureAlert, EureButtonSecondary, EureCard, EureInput } from '../../components/ui/eure';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import { attendanceRows, formatAttendanceWeight, type AttendanceRow, type AttendanceSort } from '../../components/family/attendance-table';
import { paginateReceived } from '../../components/family/received-table';
import type { HomeProps } from '../../components/family/types';
import { studentGroup } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-received.css';
import '../../../css/eureka-attendance.css';

type Props = Omit<HomeProps, 'enrollmentResult'> & { attendance: {
    studentId: string; schoolYear: string; success: string | null;
    totals: { year: number; month: number; week: number }; rows: AttendanceRow[];
} };
const columns: { key: AttendanceSort; label: string }[] = [
    { key: 'date', label: 'Fecha' }, { key: 'type', label: 'Tipo' }, { key: 'weight', label: 'Peso' }, { key: 'notes', label: 'Observaciones' },
];

export default function Attendance(props: Props) {
    const { attendance, portal, theme, homeUrl, institution } = props;
    const student = portal.students.find((item) => item.id === attendance.studentId)!;
    const { data, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const [search, setSearch] = useState('');
    const [size, setSize] = useState(10);
    const [page, setPage] = useState(1);
    const [sorting, setSorting] = useState<{ key: AttendanceSort; descending: boolean }>({ key: 'date', descending: true });
    const rows = useMemo(() => attendanceRows(attendance.rows, search, sorting.key, sorting.descending), [attendance.rows, search, sorting]);
    const pagination = paginateReceived(rows, page, size);
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); } catch { /* Optional. */ }
    }, [theme.clientId, portal.user.id, student.id]);

    return <EurekaFrame theme={theme} className="family-portal family-attendance"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={data} close={close} currentUrl={student.links.attendance}
            onSelect={(id) => { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(next.links.attendance); }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><strong aria-current="page">Inasistencias</strong></nav>}
        actions={<><FamilyNotifications data={data} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`Inasistencias · ${student.name}`} />
        <div className="attendance-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /></a><div><h1>Inasistencias</h1><p><strong>{student.name}</strong><span>{studentGroup(student)}</span></p></div><a className="attendance-back" href={student.links.profile}><ArrowLeft size={17} />Volver a la ficha</a></div>
        {attendance.success && <EureAlert type="success" message={attendance.success} />}
        <EureCard className="attendance-summary">
            <dl><div><dt>Año lectivo {attendance.schoolYear}</dt><dd>{formatAttendanceWeight(attendance.totals.year)}</dd></div><div><dt>Último mes</dt><dd>{formatAttendanceWeight(attendance.totals.month)}</dd></div><div><dt>Últimos 7 días</dt><dd>{formatAttendanceWeight(attendance.totals.week)}</dd></div></dl>
            <p>Los totales suman el peso de las inasistencias registrado por la institución.</p>
        </EureCard>
        <EureCard title="Detalle de inasistencias" icon={CalendarDays} className="attendance-detail">
            <div className="received-toolbar">
                <label className="received-sort" htmlFor="attendance-size">Mostrar<select id="attendance-size" className="received-select" value={size} onChange={(event) => { setSize(Number(event.target.value)); setPage(1); }}>{[10, 25, 50, 100].map((amount) => <option key={amount} value={amount}>{amount}</option>)}</select></label>
                <p className="received-result-summary" role="status"><span>{pagination.from}–{pagination.to} de {pagination.total} registros{search.trim() && ` (de ${attendance.rows.length} cargados)`}</span>{search && <button className="received-clear-search" type="button" onClick={() => { setSearch(''); setPage(1); }}>Limpiar búsqueda</button>}</p>
                <div className="received-search"><label htmlFor="attendance-search">Buscar:</label><EureInput id="attendance-search" type="search" placeholder="Fecha, tipo, peso u observaciones…" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            </div>
            {search.trim() && <p className="attendance-search-note">La búsqueda filtra el detalle. Los totales de arriba corresponden al período completo.</p>}
            <div className="received-table-wrap"><table className="received-table attendance-table"><caption className="sr-only">Inasistencias de {student.name}, año lectivo {attendance.schoolYear}</caption>
                <thead><tr>{columns.map((column) => <th key={column.key} scope="col" className={`attendance-${column.key}`} aria-sort={sorting.key === column.key ? sorting.descending ? 'descending' : 'ascending' : 'none'}><button type="button" className="received-column-sort" onClick={() => { setSorting({ key: column.key, descending: sorting.key === column.key ? !sorting.descending : false }); setPage(1); }}>{column.label}{sorting.key !== column.key ? <ArrowUpDown size={12} /> : sorting.descending ? <ArrowDown size={12} /> : <ArrowUp size={12} />}</button></th>)}</tr></thead>
                <tbody>{pagination.rows.map((row) => <tr key={row.id}><td className="attendance-date"><time dateTime={row.date}>{row.dateLabel}</time></td><td>{row.type || 'Sin detalle'}</td><td className="attendance-weight">{formatAttendanceWeight(row.weight)}</td><td className="attendance-notes">{row.notes || '—'}</td></tr>)}</tbody>
            </table></div>
            {!pagination.total && <div className="received-empty"><CalendarDays size={28} aria-hidden="true" /><h3>{attendance.rows.length ? 'No encontramos coincidencias' : 'Sin inasistencias registradas'}</h3><p>{attendance.rows.length ? 'Probá otra búsqueda o limpiá el texto.' : `No hay registros para el año lectivo ${attendance.schoolYear}.`}</p></div>}
            {pagination.lastPage > 1 && <nav className="received-pagination" aria-label="Páginas de inasistencias"><EureButtonSecondary disabled={pagination.page === 1} onClick={() => setPage(pagination.page - 1)}>Anterior</EureButtonSecondary><label className="received-sort" htmlFor="attendance-page">Página<select id="attendance-page" className="received-select" value={pagination.page} onChange={(event) => setPage(Number(event.target.value))}>{Array.from({ length: pagination.lastPage }, (_, index) => <option key={index + 1} value={index + 1}>{index + 1}</option>)}</select>de {pagination.lastPage}</label><EureButtonSecondary disabled={pagination.page === pagination.lastPage} onClick={() => setPage(pagination.page + 1)}>Siguiente</EureButtonSecondary></nav>}
        </EureCard>
        <footer className="eure-footer"><span>{institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
