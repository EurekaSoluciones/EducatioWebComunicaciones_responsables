import { useState } from 'react';
import EureNotifications from '../eureka/EureNotifications';
import type { EureNotification } from '../eureka/EureNotifications';

const demoNotifications: EureNotification[] = [
    { id: 'encuentro', title: 'Encuentro de familias', description: 'Hay una nueva invitación para la familia de Lucía.', person: 'Lucía Torres', avatar: '/demo/avatars/lucia.svg', time: 'Hace 5 min', read: false },
    { id: 'salida', title: 'Salida educativa al museo', description: 'La escuela compartió una comunicación para Tomás.', person: 'Tomás Torres', avatar: '/demo/avatars/tomas.svg', time: 'Hace 1 h', read: false },
    { id: 'calendario', title: 'Calendario de noviembre', description: 'Ya están disponibles las próximas actividades.', person: 'Dirección', time: 'Ayer', read: true },
];

export default function DemoNotifications() {
    const [notifications, setNotifications] = useState(demoNotifications);
    return <EureNotifications notifications={notifications} demo listUrl="#tabla"
        onRead={(id) => setNotifications((items) => items.map((item) => item.id === id ? { ...item, read: true } : item))}
        onReadAll={() => setNotifications((items) => items.map((item) => ({ ...item, read: true })))}
        onReset={() => setNotifications(demoNotifications)} />;
}
