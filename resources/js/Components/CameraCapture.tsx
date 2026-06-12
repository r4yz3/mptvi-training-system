import { useEffect, useRef, useState } from 'react';
import { UploadCloud, Camera, X, Check, Trash2 } from 'lucide-react';

/**
 * Capture an image by uploading a file OR taking a photo with the device camera
 * (rear camera preferred — good for documents like OR receipts). No cropping.
 * Returns a JPEG File via onChange.
 */
export default function CameraCapture({
    value,
    onChange,
    placeholder = 'No photo',
}: {
    value: File | null;
    onChange: (file: File | null) => void;
    placeholder?: string;
}) {
    const [cam, setCam] = useState(false);
    const [err, setErr] = useState<string | null>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const fileRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!value) { setPreview(null); return; }
        const url = URL.createObjectURL(value);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [value]);

    const stop = () => { streamRef.current?.getTracks().forEach((t) => t.stop()); streamRef.current = null; };
    useEffect(() => () => stop(), []);

    const start = async () => {
        setErr(null);
        setCam(true);
        try {
            const s = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            streamRef.current = s;
            if (videoRef.current) { videoRef.current.srcObject = s; await videoRef.current.play(); }
        } catch {
            setErr('Camera unavailable — check permissions, or use Upload. (Needs HTTPS or localhost.)');
        }
    };
    const capture = () => {
        const v = videoRef.current;
        if (!v) return;
        const canvas = document.createElement('canvas');
        canvas.width = v.videoWidth;
        canvas.height = v.videoHeight;
        canvas.getContext('2d')!.drawImage(v, 0, 0);
        stop();
        setCam(false);
        canvas.toBlob((b) => { if (b) onChange(new File([b], 'or-photo.jpg', { type: 'image/jpeg' })); }, 'image/jpeg', 0.9);
    };
    const cancel = () => { stop(); setCam(false); setErr(null); };

    return (
        <div>
            <div className="flex items-center gap-3">
                <div className="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-[10px] text-slate-400">
                    {preview ? <img src={preview} alt="OR" className="h-full w-full object-cover" /> : placeholder}
                </div>
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => fileRef.current?.click()} className="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <UploadCloud className="h-3.5 w-3.5" /> Upload
                    </button>
                    <button type="button" onClick={start} className="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <Camera className="h-3.5 w-3.5" /> Take photo
                    </button>
                    {value && (
                        <button type="button" onClick={() => onChange(null)} className="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-xs text-slate-400 hover:text-rose-600">
                            <Trash2 className="h-3.5 w-3.5" /> Remove
                        </button>
                    )}
                </div>
            </div>
            <input ref={fileRef} type="file" accept="image/*" capture="environment" className="hidden" onChange={(e) => onChange(e.target.files?.[0] ?? null)} />

            {cam && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4">
                    <div className="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                            <h3 className="text-sm font-semibold text-slate-800">Take OR photo</h3>
                            <button type="button" onClick={cancel} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                        </div>
                        <div className="p-5">
                            {err ? (
                                <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-700">{err}</p>
                            ) : (
                                <div className="overflow-hidden rounded-lg bg-black">
                                    <video ref={videoRef} playsInline muted className="max-h-[60vh] w-full object-contain" />
                                </div>
                            )}
                            <div className="mt-4 flex justify-center gap-2">
                                <button type="button" onClick={cancel} className="btn-ghost">Cancel</button>
                                {!err && <button type="button" onClick={capture} className="btn-primary"><Check className="h-4 w-4" /> Capture</button>}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
