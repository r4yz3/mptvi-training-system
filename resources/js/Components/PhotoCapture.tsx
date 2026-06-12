import { useCallback, useEffect, useRef, useState } from 'react';
import Cropper, { Area } from 'react-easy-crop';
import { UploadCloud, Camera, X, Check, RotateCcw } from 'lucide-react';

type Mode = 'idle' | 'camera' | 'crop';

function createImage(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.addEventListener('load', () => resolve(img));
        img.addEventListener('error', (e) => reject(e));
        img.crossOrigin = 'anonymous';
        img.src = url;
    });
}

/** Crop the selected region into a square JPEG File (600×600). */
async function cropToSquare(imageSrc: string, area: Area): Promise<File> {
    const image = await createImage(imageSrc);
    const size = 600;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(image, area.x, area.y, area.width, area.height, 0, 0, size, size);
    return new Promise((resolve) =>
        canvas.toBlob(
            (blob) => resolve(new File([blob!], 'photo.jpg', { type: 'image/jpeg' })),
            'image/jpeg',
            0.9,
        ),
    );
}

export default function PhotoCapture({
    existing,
    onChange,
}: {
    existing?: string | null;
    onChange: (file: File | null) => void;
}) {
    const [mode, setMode] = useState<Mode>('idle');
    const [imageSrc, setImageSrc] = useState<string | null>(null);
    const [preview, setPreview] = useState<string | null>(existing ?? null);
    const [crop, setCrop] = useState({ x: 0, y: 0 });
    const [zoom, setZoom] = useState(1);
    const [areaPixels, setAreaPixels] = useState<Area | null>(null);
    const [camError, setCamError] = useState<string | null>(null);

    const fileRef = useRef<HTMLInputElement>(null);
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);

    const stopCamera = useCallback(() => {
        streamRef.current?.getTracks().forEach((t) => t.stop());
        streamRef.current = null;
    }, []);

    useEffect(() => () => stopCamera(), [stopCamera]);

    const onPickFile = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => { setImageSrc(reader.result as string); setMode('crop'); setZoom(1); setCrop({ x: 0, y: 0 }); };
        reader.readAsDataURL(file);
    };

    const startCamera = async () => {
        setCamError(null);
        setMode('camera');
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
                audio: false,
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }
        } catch {
            setCamError('Camera unavailable. Check permissions, or use Upload. (Camera needs HTTPS or localhost.)');
        }
    };

    const capture = () => {
        const video = videoRef.current;
        if (!video) return;
        const s = Math.min(video.videoWidth, video.videoHeight);
        const canvas = document.createElement('canvas');
        canvas.width = s; canvas.height = s;
        const ctx = canvas.getContext('2d')!;
        // center-crop the video frame to a square before cropping UI
        ctx.drawImage(video, (video.videoWidth - s) / 2, (video.videoHeight - s) / 2, s, s, 0, 0, s, s);
        stopCamera();
        setImageSrc(canvas.toDataURL('image/jpeg', 0.92));
        setMode('crop'); setZoom(1); setCrop({ x: 0, y: 0 });
    };

    const useCrop = async () => {
        if (!imageSrc || !areaPixels) return;
        const file = await cropToSquare(imageSrc, areaPixels);
        onChange(file);
        setPreview(URL.createObjectURL(file));
        setImageSrc(null);
        setMode('idle');
    };

    const cancel = () => { stopCamera(); setImageSrc(null); setMode('idle'); setCamError(null); };

    return (
        <div>
            {/* Idle: 2x2 preview + actions */}
            <div className="flex flex-col items-center gap-2">
                <div className="flex h-32 w-32 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-slate-300 bg-slate-50">
                    {preview
                        ? <img src={preview} alt="2x2" className="h-full w-full object-cover" />
                        : <div className="text-center text-xs text-slate-400"><UploadCloud className="mx-auto mb-1 h-6 w-6" />2×2 photo</div>}
                </div>
                <div className="flex gap-2">
                    <button type="button" onClick={() => fileRef.current?.click()} className="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <UploadCloud className="h-3.5 w-3.5" /> Upload
                    </button>
                    <button type="button" onClick={startCamera} className="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <Camera className="h-3.5 w-3.5" /> Camera
                    </button>
                </div>
                <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={onPickFile} />
            </div>

            {/* Camera / Crop modal */}
            {(mode === 'camera' || mode === 'crop') && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4">
                    <div className="w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-xl">
                        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                            <h3 className="text-sm font-semibold text-slate-800">
                                {mode === 'camera' ? 'Take photo' : 'Crop to 2×2 (square)'}
                            </h3>
                            <button type="button" onClick={cancel} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                        </div>

                        {mode === 'camera' && (
                            <div className="p-5">
                                {camError ? (
                                    <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-700">{camError}</p>
                                ) : (
                                    <div className="mx-auto aspect-square w-72 overflow-hidden rounded-lg bg-black">
                                        <video ref={videoRef} playsInline muted className="h-full w-full object-cover" />
                                    </div>
                                )}
                                <div className="mt-4 flex justify-center gap-2">
                                    <button type="button" onClick={cancel} className="btn-ghost">Cancel</button>
                                    {!camError && (
                                        <button type="button" onClick={capture} className="btn-primary"><Camera className="h-4 w-4" /> Capture</button>
                                    )}
                                </div>
                            </div>
                        )}

                        {mode === 'crop' && imageSrc && (
                            <div className="p-5">
                                <div className="relative mx-auto h-72 w-full max-w-sm overflow-hidden rounded-lg bg-slate-900">
                                    <Cropper
                                        image={imageSrc}
                                        crop={crop}
                                        zoom={zoom}
                                        aspect={1}
                                        cropShape="rect"
                                        showGrid={false}
                                        onCropChange={setCrop}
                                        onZoomChange={setZoom}
                                        onCropComplete={(_: Area, px: Area) => setAreaPixels(px)}
                                    />
                                </div>
                                <div className="mt-3 flex items-center gap-3">
                                    <span className="text-xs text-slate-500">Zoom</span>
                                    <input type="range" min={1} max={3} step={0.05} value={zoom} onChange={(e) => setZoom(Number(e.target.value))} className="flex-1" />
                                </div>
                                <div className="mt-4 flex justify-end gap-2">
                                    <button type="button" onClick={cancel} className="btn-ghost"><RotateCcw className="h-4 w-4" /> Cancel</button>
                                    <button type="button" onClick={useCrop} className="btn-primary"><Check className="h-4 w-4" /> Use photo</button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
