import type { Theme } from '../../theme';
import FamilyHtmlContent from './FamilyHtmlContent';

export default function FamilyBoardContent(props: { html: string; title: string; theme: Theme }) {
    return <FamilyHtmlContent {...props} emptyText="No hay contenido publicado en esta cartelera." />;
}
