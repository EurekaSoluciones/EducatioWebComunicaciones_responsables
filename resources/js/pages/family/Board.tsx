import { Head } from '@inertiajs/react';
import { ArrowLeft, BookOpen, ChevronRight, Download, File, FileImage, FileText, Paperclip } from 'lucide-react';
import { EureAvatar } from '../../components/eureka';
import { EureCard, EureHumanDate } from '../../components/ui/eure';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import FamilyBoardContent from '../../components/family/FamilyBoardContent';
import useSelectedStudent from '../../components/family/useSelectedStudent';
import type { HomeProps } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-board.css';

type BoardProps = Omit<HomeProps, 'enrollmentResult'> & {
    board: {
        id: string; title: string; html: string; url: string; createdAt: string | null; updatedAt: string | null;
        attachments: { id: string; name: string; extension: string; url: string }[];
    };
};

export default function Board(props: BoardProps) {
    const { board, portal, theme, institution, homeUrl } = props;
    const { selected, select } = useSelectedStudent(portal.students, `family-student:${theme.clientId}:${portal.user.id}`);
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);

    return <EurekaFrame theme={theme} className="family-portal family-board-page"
        sidebar={(close) => <FamilyNavigation {...props} selected={selected} onSelect={select} notifications={notifications} close={close} currentUrl={board.url} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><span>Carteleras</span><ChevronRight size={14} /><strong aria-current="page">{board.title}</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`Cartelera · ${board.title}`} />
        <div className="family-board-heading"><div><p className="eyebrow">{institution} · Cartelera</p><h1>{board.title}</h1></div><a className="eureui-button-secondary" href={homeUrl}><ArrowLeft size={17} />Volver al inicio</a></div>
        {error && <div className="family-home-error" role="status">No pudimos actualizar los pendientes. <button type="button" onClick={refresh}>Reintentar</button></div>}
        <EureCard title="Novedades" icon={BookOpen} className="family-board-article">
            <FamilyBoardContent html={board.html} title={board.title} theme={theme} />
            {(board.createdAt || board.updatedAt) && <div className="family-board-dates">
                {board.createdAt && <span>Creada <EureHumanDate value={board.createdAt} /></span>}
                {board.updatedAt && board.updatedAt !== board.createdAt && <span>Actualizada <EureHumanDate value={board.updatedAt} /></span>}
            </div>}
        </EureCard>
        <EureCard title="Adjuntos" icon={Paperclip} className="family-board-attachments">
            {board.attachments.length > 0 ? <ul>{board.attachments.map((attachment) => {
                const Icon = ['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(attachment.extension) ? FileImage : attachment.extension === 'pdf' ? FileText : File;
                return <li key={attachment.id}><a href={attachment.url} download={attachment.name} target="_blank" rel="noopener noreferrer">
                    <Icon size={26} aria-hidden="true" /><span><strong>{attachment.name}</strong><small>{attachment.extension.toUpperCase() || 'Archivo'} · Descargar</small></span><Download size={18} aria-hidden="true" />
                </a></li>;
            })}</ul> : <p className="eure-muted">Esta cartelera no tiene archivos adjuntos.</p>}
        </EureCard>
        <footer className="eure-footer"><span>{institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
