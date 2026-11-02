import { Download, ExternalLink, FileText, FolderOpen, LockKeyhole, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import FamilyDocumentsFrame, { type DocumentsPageProps } from '../../components/family/FamilyDocumentsFrame';
import { EureAlert, EureButtonSecondary, EureCard, EureHumanDate, EureInput } from '../../components/ui/eure';

type Props = DocumentsPageProps & { documents: {
    studentId: string; notice: string | null; newAttachmentUrl: string;
    reports: { id: string; name: string; enabled: boolean; message: string | null; url: string | null }[];
    attachments: { id: string; name: string; extension: string; url: string; date: string | null }[];
} };
const fold = (value: string) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();

export default function Documents(props: Props) {
    const { documents, portal } = props;
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const query = fold(search.trim());
    const reports = documents.reports.filter((item) => fold(`${item.name} ${item.message ?? ''}`).includes(query));
    const files = documents.attachments.filter((item) => fold(`${item.name} ${item.extension}`).includes(query));
    const pages = Math.max(1, Math.ceil(files.length / 10));
    const current = Math.min(page, pages);
    return <FamilyDocumentsFrame props={props} studentId={documents.studentId} title={portal.labels.documents}>
        {documents.notice && <EureAlert type="info" message={documents.notice} />}
        <div className="documents-tools"><label htmlFor="documents-search"><Search size={17} />Buscar en documentos y legajo</label><EureInput id="documents-search" type="search" value={search} placeholder="Nombre del documento o archivo" onChange={(event) => { setSearch(event.target.value); setPage(1); }} />{search && <EureButtonSecondary onClick={() => { setSearch(''); setPage(1); }}>Limpiar</EureButtonSecondary>}</div>
        <EureCard title="Documentos de la institución" icon={FileText} description="Informes, certificados y otros documentos del alumno.">
            {reports.length ? <ul className="documents-list">{reports.map((report) => <li key={report.id}>
                {report.enabled ? <FileText size={22} aria-hidden="true" /> : <LockKeyhole size={22} className="documents-lock" aria-hidden="true" />}
                <div><strong>{report.name}</strong>{!report.enabled && <p className="documents-restriction">{report.message || 'No disponible por el momento.'}</p>}</div>
                {report.url ? <a className="eureui-button-secondary" href={report.url} aria-label={`Descargar ${report.name}`}><Download size={16} />Descargar</a> : <span className="documents-unavailable">No disponible</span>}
            </li>)}</ul> : <p className="documents-empty">{documents.reports.length ? 'No hay documentos que coincidan con la búsqueda.' : 'La institución todavía no tiene documentos disponibles para este alumno.'}</p>}
        </EureCard>
        <div className="documents-card-separator" aria-hidden="true"><span /><span /><span /></div>
        <EureCard title={<><span>Legajo del alumno</span><a className="eureui-button-primary" href={documents.newAttachmentUrl}><Plus size={16} />Nuevo adjunto</a></>} icon={FolderOpen} description="Archivos incorporados al legajo. Podés abrirlos o agregar nuevos.">
            {files.length ? <>
                <div className="documents-table-wrap"><table className="documents-table"><thead><tr><th scope="col">Archivo</th><th scope="col">Fecha de carga</th><th scope="col"><span className="documents-action-label">Acción</span></th></tr></thead><tbody>{files.slice((current - 1) * 10, current * 10).map((file) => <tr key={file.id}>
                    <td><a href={file.url} target="_blank" rel="noopener noreferrer"><FileText size={19} /><span>{file.name}<small>{file.extension.toUpperCase() || 'Archivo'}</small></span></a></td>
                    <td>{file.date ? <EureHumanDate value={file.date} /> : '—'}</td>
                    <td><a className="eureui-button-secondary" href={file.url} target="_blank" rel="noopener noreferrer" aria-label={`Abrir ${file.name}`}><ExternalLink size={15} />Abrir</a></td>
                </tr>)}</tbody></table></div>
                <div className="documents-pagination"><span>{files.length} {files.length === 1 ? 'archivo' : 'archivos'}{query && ' encontrados'}</span>{pages > 1 && <><EureButtonSecondary disabled={current === 1} onClick={() => setPage(current - 1)}>Anterior</EureButtonSecondary><span>Página {current} de {pages}</span><EureButtonSecondary disabled={current === pages} onClick={() => setPage(current + 1)}>Siguiente</EureButtonSecondary></>}</div>
            </> : <p className="documents-empty">{documents.attachments.length ? 'No hay archivos que coincidan con la búsqueda.' : 'Todavía no hay archivos en el legajo.'}</p>}
        </EureCard>
    </FamilyDocumentsFrame>;
}
