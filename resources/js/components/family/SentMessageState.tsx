import { replyStatus, sentStatus } from './sent-table';
import type { SentMessage } from './sent-table';

export default function SentMessageState({ message }: { message: SentMessage }) {
    return <span className="sent-message-state">
        <span className="received-status" title={message.readAt ? 'La institución leyó tu mensaje' : 'La institución todavía no leyó tu mensaje'}>{sentStatus(message)}</span>
        {message.answeredAt && <span className={`received-status ${message.replyUnread ? 'is-unread' : ''}`}>{replyStatus(message)}</span>}
    </span>;
}
