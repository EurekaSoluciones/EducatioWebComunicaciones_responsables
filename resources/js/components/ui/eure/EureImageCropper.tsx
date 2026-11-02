import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';
import { RotateCcw, RotateCw, ZoomIn, ZoomOut } from 'lucide-react';
import EureButtonSecondary from './EureButtonSecondary';

interface CropperInstance {
    destroy(): void; reset(): void; rotate(degrees: number): void; zoom(ratio: number): void; move(x: number, y: number): void;
    disable(): void; enable(): void;
    getCroppedCanvas(options: { width: number; height: number; imageSmoothingEnabled?: boolean; imageSmoothingQuality?: string }): HTMLCanvasElement | null;
}
type CropperConstructor = new (image: HTMLImageElement, options: Record<string, unknown>) => CropperInstance;
declare global { interface Window { Cropper?: CropperConstructor } }
let cropperLoading: Promise<CropperConstructor> | undefined;
let stylesLoading: Promise<void> | undefined;

function loadCropperStyles(): Promise<void> {
    if (!stylesLoading) {
        stylesLoading = new Promise<void>((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet'; link.href = '/css/cropper.min.css';
            link.onload = () => resolve();
            link.onerror = () => { link.remove(); reject(new Error('No se pudo cargar el recortador. Volvé a elegir la foto para reintentar.')); };
            document.head.append(link);
        }).catch((error) => { stylesLoading = undefined; throw error; });
    }
    return stylesLoading;
}

async function loadCropper(): Promise<CropperConstructor> {
    // Its layout depends on the CSS; initialize only after the local stylesheet is ready.
    await loadCropperStyles();
    if (window.Cropper) return Promise.resolve(window.Cropper);
    if (!cropperLoading) {
        cropperLoading = new Promise<CropperConstructor>((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/js/cropper.min.js'; script.async = true;
            script.onload = () => { if (window.Cropper) resolve(window.Cropper); else { script.remove(); reject(new Error('No se pudo iniciar el recortador.')); } };
            script.onerror = () => { script.remove(); reject(new Error('No se pudo cargar el recortador. Volvé a elegir la foto para reintentar.')); };
            document.head.append(script);
        }).catch((error) => { cropperLoading = undefined; throw error; });
    }
    return cropperLoading;
}

export interface EureImageCropperHandle { exportFile(): Promise<File> }
export interface EureImageCropperProps {
    file: File; disabled?: boolean; onReady: (ready: boolean) => void; onError: (message: string) => void;
}

// Local React wrapper around the Cropper.js 1.6.2 assets already shipped by this project.
const EureImageCropper = forwardRef<EureImageCropperHandle, EureImageCropperProps>(function EureImageCropper({ file, disabled = false, onReady, onError }, ref) {
    const img = useRef<HTMLImageElement>(null);
    const instance = useRef<CropperInstance | null>(null);
    const callbacks = useRef({ onReady, onError });
    callbacks.current = { onReady, onError };
    const [ready, setReady] = useState(false);
    const [failed, setFailed] = useState(false);
    const [preview, setPreview] = useState('');
    useImperativeHandle(ref, () => ({
        exportFile: () => new Promise<File>((resolve, reject) => {
            const canvas = instance.current?.getCroppedCanvas({ width: 512, height: 512, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
            if (!canvas) { reject(new Error('Esperá a que termine de cargar la imagen.')); return; }
            canvas.toBlob((blob) => {
                if (!blob) { reject(new Error('No se pudo generar el recorte. Probá con otra imagen.')); return; }
                resolve(new File([blob], 'foto-alumno.png', { type: 'image/png' }));
            }, 'image/png');
        }),
    }), []);
    useEffect(() => {
        let disposed = false;
        let animation = 0;
        let initialized = false;
        const image = img.current!;
        const url = URL.createObjectURL(file);
        setReady(false); setFailed(false); setPreview(''); callbacks.current.onReady(false);
        const fail = (message: string) => { if (!disposed) { setFailed(true); callbacks.current.onReady(false); callbacks.current.onError(message); } };
        const updatePreview = () => {
            cancelAnimationFrame(animation);
            animation = requestAnimationFrame(() => {
                if (!disposed && initialized) {
                    const canvas = instance.current?.getCroppedCanvas({ width: 160, height: 160 });
                    if (canvas) setPreview(canvas.toDataURL('image/png'));
                }
            });
        };
        void loadCropper().then((Cropper) => {
            if (disposed) return;
            image.onload = () => {
                image.onload = null;
                if (disposed) return;
                if (image.naturalWidth < 64 || image.naturalHeight < 64 || image.naturalWidth * image.naturalHeight > 24_000_000) {
                    fail('Elegí una imagen de al menos 64 × 64 píxeles y hasta 24 megapíxeles.'); return;
                }
                instance.current = new Cropper(image, {
                    aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: .9,
                    cropBoxMovable: false, cropBoxResizable: false, toggleDragModeOnDblclick: false,
                    background: false, responsive: true, checkOrientation: true,
                    ready: () => {
                        if (disposed) return;
                        initialized = true; setReady(true); callbacks.current.onReady(true); updatePreview();
                    },
                    crop: updatePreview,
                });
            };
            image.onerror = () => fail('No se pudo leer esa imagen. Probá con un JPG, PNG o WebP.');
            image.src = url;
        }).catch((error: Error) => fail(error.message));
        return () => {
            disposed = true; image.onload = null; image.onerror = null;
            cancelAnimationFrame(animation); instance.current?.destroy(); instance.current = null;
            URL.revokeObjectURL(url);
        };
    }, [file]);
    useEffect(() => { if (ready) { if (disabled) instance.current?.disable(); else instance.current?.enable(); } }, [disabled, ready]);

    return <div className="eureui-cropper">
        <div className="eureui-cropper-workspace" tabIndex={disabled ? -1 : 0} aria-label="Recorte de foto. Arrastrá la imagen o usá las flechas del teclado para moverla."
            onKeyDown={(event) => {
                if (disabled || !ready) return;
                const movements: Record<string, [number, number]> = { ArrowLeft: [-10, 0], ArrowRight: [10, 0], ArrowUp: [0, -10], ArrowDown: [0, 10] };
                if (movements[event.key]) { event.preventDefault(); instance.current?.move(...movements[event.key]); }
            }}>
            <img ref={img} alt="Imagen seleccionada para recortar" />
        </div>
        {!ready && !failed && <p role="status">Preparando imagen…</p>}
        <div className="eureui-cropper-controls" aria-label="Ajustes del recorte">
            <EureButtonSecondary disabled={!ready || disabled} onClick={() => instance.current?.zoom(-.1)} aria-label="Alejar"><ZoomOut size={18} /></EureButtonSecondary>
            <EureButtonSecondary disabled={!ready || disabled} onClick={() => instance.current?.zoom(.1)} aria-label="Acercar"><ZoomIn size={18} /></EureButtonSecondary>
            <EureButtonSecondary disabled={!ready || disabled} onClick={() => instance.current?.rotate(-90)} aria-label="Girar a la izquierda"><RotateCcw size={18} /></EureButtonSecondary>
            <EureButtonSecondary disabled={!ready || disabled} onClick={() => instance.current?.rotate(90)} aria-label="Girar a la derecha"><RotateCw size={18} /></EureButtonSecondary>
            <EureButtonSecondary disabled={!ready || disabled} onClick={() => instance.current?.reset()}>Restablecer</EureButtonSecondary>
        </div>
        {preview && <div className="eureui-cropper-preview"><img src={preview} alt="Así se verá la foto de perfil" /><div><strong>Vista previa</strong><p>Arrastrá para encuadrar el rostro. También podés usar las flechas del teclado.</p></div></div>}
    </div>;
});
export default EureImageCropper;
