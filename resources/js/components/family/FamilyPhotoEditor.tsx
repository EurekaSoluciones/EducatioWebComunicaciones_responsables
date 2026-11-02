import axios from 'axios';
import { Camera, ImagePlus, Save, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EureAvatar } from '../eureka';
import { EureAlert, EureButtonPrimary, EureButtonSecondary, EureCard } from '../ui/eure';
import EureImageCropper from '../ui/eure/EureImageCropper';
import type { EureImageCropperHandle } from '../ui/eure/EureImageCropper';
import '../../../css/eureka-student-photo.css';

const MAX_SOURCE_BYTES = 10 * 1024 * 1024;

export default function FamilyPhotoEditor({ name, avatar, updateUrl, cancelUrl, csrfToken, loginUrl, method = 'PATCH', onBusyChange }: {
    name: string; avatar: string | null; updateUrl: string; cancelUrl: string; csrfToken: string; loginUrl: string;
    method?: 'PATCH' | 'POST'; onBusyChange?: (busy: boolean) => void;
}) {
    const input = useRef<HTMLInputElement>(null);
    const cropper = useRef<EureImageCropperHandle>(null);
    const pending = useRef<AbortController | null>(null);
    const mounted = useRef(true);
    const [file, setFile] = useState<File | null>(null);
    const [remove, setRemove] = useState(false);
    const [ready, setReady] = useState(false);
    const [busy, setBusy] = useState(false);
    const [progress, setProgress] = useState<number | null>(null);
    const [error, setError] = useState('');
    const [sessionExpired, setSessionExpired] = useState(false);
    useEffect(() => { mounted.current = true; return () => { mounted.current = false; pending.current?.abort(); }; }, []);

    useEffect(() => { onBusyChange?.(busy); }, [busy, onBusyChange]);

    const choose = (candidate?: File) => {
        if (!candidate || busy) return;
        setError('');
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(candidate.type)) { setError('Elegí una imagen JPG, PNG o WebP.'); return; }
        if (candidate.size > MAX_SOURCE_BYTES) { setError('La imagen original no puede superar los 10 MB.'); return; }
        setReady(false); setRemove(false); setFile(candidate);
    };
    const save = async () => {
        if (busy || (!remove && !ready)) return;
        setBusy(true); setError(''); setSessionExpired(false); setProgress(null);
        const controller = new AbortController(); pending.current = controller;
        try {
            const data = new FormData();
            data.append('_method', method); data.append('remove_photo', remove ? '1' : '0');
            if (!remove) {
                const cropped = await cropper.current?.exportFile();
                if (!cropped) throw new Error('Volvé a elegir la imagen para preparar el recorte.');
                data.append('photo', cropped);
            }
            if (!mounted.current) return;
            const response = await axios.post<{ redirectUrl: string }>(updateUrl, data, {
                signal: controller.signal, timeout: 60_000,
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                onUploadProgress: (event) => { if (mounted.current && event.total) setProgress(Math.round(event.loaded * 100 / event.total)); },
            });
            if (mounted.current) window.location.assign(response.data.redirectUrl);
        } catch (failure) {
            if (!mounted.current) return;
            if (axios.isAxiosError(failure)) {
                const status = failure.response?.status;
                setSessionExpired(status === 419 || status === 401);
                const fields = failure.response?.data?.errors as Record<string, string[]> | undefined;
                setError(status === 419 || status === 401 ? 'Tu sesión venció. Ingresá nuevamente antes de guardar.'
                    : status === 403 ? 'No tenés permiso para cambiar esta foto.'
                    : status === 413 ? 'La imagen supera el tamaño permitido por el servidor.'
                    : fields ? Object.values(fields).flat().join(' ') : 'No pudimos confirmar el guardado. Revisá la conexión y volvé a intentar.');
            } else setError(failure instanceof Error ? failure.message : 'No se pudo generar el recorte.');
        } finally { if (mounted.current) { setBusy(false); setProgress(null); } pending.current = null; }
    };

    return (
        <div className="student-photo-grid">
            <EureCard title={remove ? 'Sin foto personalizada' : 'Foto actual'} icon={Camera}>
                <div className="student-photo-current"><EureAvatar name={name} src={!remove ? avatar ?? undefined : undefined} size="lg" /><strong>{name}</strong>
                    <p>{remove ? 'Al guardar, el perfil mostrará sus iniciales.' : 'La nueva foto se aplica cuando guardás los cambios.'}</p>
                    {avatar && <EureButtonSecondary disabled={busy} onClick={() => { setRemove(!remove); setFile(null); setReady(false); setError(''); }}><Trash2 size={16} />{remove ? 'Conservar foto actual' : 'Quitar foto'}</EureButtonSecondary>}
                </div>
            </EureCard>
            <EureCard title="Elegí y ajustá la foto" icon={ImagePlus}>
                {error && <EureAlert message={error} className="student-photo-error" />}
                {sessionExpired && <a className="student-photo-login" href={loginUrl}>Volver a ingresar</a>}
                <input ref={input} type="file" accept="image/jpeg,image/png,image/webp" className="student-photo-input" aria-label="Seleccionar foto" disabled={busy}
                    onChange={(event) => { choose(event.target.files?.[0]); event.target.value = ''; }} />
                <div className="student-photo-picker" onDragOver={(event) => event.preventDefault()} onDrop={(event) => { event.preventDefault(); choose(event.dataTransfer.files[0]); }}>
                    <EureButtonSecondary disabled={busy} onClick={() => input.current?.click()}><ImagePlus size={18} />{file ? 'Elegir otra imagen' : 'Seleccionar imagen'}</EureButtonSecondary>
                    <p>JPG, PNG o WebP, hasta 10 MB. También podés arrastrar una imagen acá.</p>
                </div>
                {file && <EureImageCropper ref={cropper} file={file} disabled={busy} onReady={setReady} onError={(message) => { setError(message); setReady(false); }} />}
                {!file && <p className="student-photo-hint">{remove ? 'La foto se quitará al guardar. Podés seleccionar otra imagen para reemplazarla.' : 'Seleccioná una foto para encuadrar el rostro y ver el resultado antes de guardar.'}</p>}
                <div className="student-photo-save">
                    <EureButtonPrimary disabled={!remove && !ready} loading={busy} onClick={() => void save()}><Save size={17} />{busy ? (progress !== null && progress < 100 ? `Subiendo ${progress}%` : 'Guardando…') : remove ? 'Guardar sin foto' : 'Guardar foto'}</EureButtonPrimary>
                    <EureButtonSecondary disabled={busy} onClick={() => window.location.assign(cancelUrl)}>Cancelar</EureButtonSecondary>
                </div>
                {busy && <p role="status" className="student-photo-status">Guardando los cambios de {name}…</p>}
            </EureCard>
        </div>
    );
}
