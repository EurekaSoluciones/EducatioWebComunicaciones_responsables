import { Head, useForm } from '@inertiajs/react';
import { CheckCheck, ChevronRight, Filter, Inbox } from 'lucide-react';
import { useEffect } from 'react';
import { EureAvatar } from '../../components/eureka';
import { EureAlert, EureButtonPrimary, EureCard, EureInputGroup } from '../../components/ui/eure';
import ReceivedTable from '../../components/family/ReceivedTable';
import useCommunicationStates from '../../components/family/useCommunicationStates';
import type { ReceivedMessage } from '../../components/family/received-table';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import type { HomeProps } from '../../components/family/types';
import { studentGroup } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-received.css';

type Filters = { remitente: string; desde: string; hasta: string; noLeidos: boolean; filtrado: boolean };
type ReceivedProps = Omit<HomeProps, 'enrollmentResult'> & {
    received: {
        studentId: string; url: string; clearUrl: string; statusUrl: string; success: string | null; filters: Filters;
        senders: { id: string; name: string }[];
        messages: ReceivedMessage[];
    };
};

export default function Received(props: ReceivedProps) {
    const { received, portal, theme, institution, homeUrl } = props;
    const student = portal.students.find((item) => item.id === received.studentId)!;
    const form = useForm<Filters>(received.filters);
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const states = useCommunicationStates(received.messages, received.statusUrl, `${theme.clientId}:${portal.user.id}`, portal.user.id, refresh);
    const messages = received.filters.noLeidos ? states.messages.filter((message) => message.unread) : states.messages;
    const unread = notifications.byStudent.find((item) => item.studentId === student.id)?.received ?? 0;
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); }
        catch { /* The selector still works without storage. */ }
    }, [theme.clientId, portal.user.id, student.id]);
    const selectStudent = (id: string) => {
        const next = portal.students.find((item) => item.id === id);
        if (next && next.id !== student.id) window.location.assign(next.links.received);
    };

    return <EurekaFrame theme={theme} className="family-portal family-received"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} onSelect={selectStudent} notifications={notifications} close={close} currentUrl={received.url} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><strong aria-current="page">Comunicaciones recibidas</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`Recibidas · ${student.name}`} />
        <div className="received-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /></a><div className="received-heading-text"><h1>Comunicaciones recibidas</h1><p><strong>{student.name}</strong><span> · {studentGroup(student)}</span></p></div>
            <span className={`received-summary ${unread ? 'has-unread' : ''}`}>{unread ? <Inbox size={17} /> : <CheckCheck size={17} />}{unread ? `${unread} sin leer` : 'Al día'}</span>
        </div>
        {received.success && <EureAlert type="success" message={received.success} />}
        {error && <div className="family-home-error" role="status">No pudimos actualizar los pendientes. <button type="button" onClick={refresh}>Reintentar</button></div>}
        <EureCard title="Filtros" icon={Filter} className="received-filters">
            <form onSubmit={(event) => {
                event.preventDefault(); form.cancel();
                form.transform(({ remitente, desde, hasta, noLeidos }) => ({ remitente, desde, hasta, noLeidos }));
                form.get(received.url, { preserveScroll: true });
            }}>
                <div className="received-filter-row">
                    <div className="received-filter-sender"><label className="eureui-label" htmlFor="received-sender">Remitente:</label><select id="received-sender" className="received-select" value={form.data.remitente} onChange={(event) => form.setData('remitente', event.target.value)}><option value="">Todos los remitentes</option>{received.senders.map((sender) => <option key={sender.id} value={sender.id}>{sender.name}</option>)}</select></div>
                    <EureInputGroup name="desde" type="date" label="Desde" value={form.data.desde} onChange={(event) => form.setData('desde', event.target.value)} error={Boolean(form.errors.desde)} errorMessage={form.errors.desde} />
                    <EureInputGroup name="hasta" type="date" label="Hasta" value={form.data.hasta} min={form.data.desde || undefined} onChange={(event) => form.setData('hasta', event.target.value)} error={Boolean(form.errors.hasta)} errorMessage={form.errors.hasta} />
                    <label className="received-unread-filter"><input type="checkbox" checked={form.data.noLeidos} onChange={(event) => form.setData('noLeidos', event.target.checked)} />Solo sin leer</label>
                    <div className="received-filter-actions"><EureButtonPrimary type="submit" loading={form.processing}>Filtrar</EureButtonPrimary><a className="eureui-button-secondary" href={received.clearUrl}>Limpiar</a></div>
                </div>
                {Object.keys(form.errors).some((field) => !['desde', 'hasta'].includes(field)) && <EureAlert type="error" message={Object.values(form.errors).join(' ')} />}
            </form>
            {received.filters.filtrado && <div className="received-applied-filters" role="status">
                <strong><Filter size={14} aria-hidden="true" />Filtrados</strong>
                <span>{[
                    received.filters.remitente && `Remitente: ${received.senders.find((sender) => sender.id === received.filters.remitente)?.name ?? 'Seleccionado'}`,
                    received.filters.desde && `Desde: ${received.filters.desde.split('-').reverse().join('/')}`,
                    received.filters.hasta && `Hasta: ${received.filters.hasta.split('-').reverse().join('/')}`,
                    received.filters.noLeidos && 'Solo sin leer',
                ].filter(Boolean).join(' · ') || 'Todo el historial'}</span>
                <a href={received.clearUrl}>Limpiar</a>
            </div>}
        </EureCard>
        <EureCard className="received-results">
            {states.error && <div className="family-home-error" role="status">No pudimos actualizar los estados de lectura. <button type="button" onClick={states.refresh}>Reintentar</button></div>}
            <ReceivedTable key={`${student.id}:${JSON.stringify(received.filters)}`} messages={messages} studentName={student.name} />
        </EureCard>
        <footer className="eure-footer"><span>{institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
