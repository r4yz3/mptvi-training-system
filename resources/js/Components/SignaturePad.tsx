import { useEffect, useRef, useState } from 'react';
import { X } from 'lucide-react';

/**
 * Lightweight canvas signature pad (mouse + touch). Calls onChange with a PNG
 * data URL while drawing, or null when cleared. `existing` shows a previously
 * saved signature image (edit mode); drawing replaces it.
 */
export default function SignaturePad({
    label,
    existing,
    onChange,
    height = 150,
}: {
    label?: string;
    existing?: string | null;
    onChange: (dataUrl: string | null) => void;
    height?: number;
}) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const drawing = useRef(false);
    const last = useRef<{ x: number; y: number } | null>(null);
    const [dirty, setDirty] = useState(false);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        // Scale for crisp lines on HiDPI.
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#15366B';
        }
    }, []);

    const pos = (e: React.PointerEvent) => {
        const rect = canvasRef.current!.getBoundingClientRect();
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    };

    const start = (e: React.PointerEvent) => {
        e.preventDefault();
        drawing.current = true;
        last.current = pos(e);
        canvasRef.current?.setPointerCapture(e.pointerId);
    };
    const move = (e: React.PointerEvent) => {
        if (!drawing.current) return;
        const ctx = canvasRef.current?.getContext('2d');
        const p = pos(e);
        if (ctx && last.current) {
            ctx.beginPath();
            ctx.moveTo(last.current.x, last.current.y);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        }
        last.current = p;
        if (!dirty) setDirty(true);
    };
    const end = () => {
        if (!drawing.current) return;
        drawing.current = false;
        last.current = null;
        if (canvasRef.current) onChange(canvasRef.current.toDataURL('image/png'));
    };

    const clear = () => {
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (canvas && ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
        setDirty(false);
        onChange(null);
    };

    return (
        <div>
            {label && <div className="mb-1 text-xs font-medium text-slate-600">{label}</div>}
            <div className="relative rounded-lg border border-slate-300 bg-white" style={{ height }}>
                {existing && !dirty && (
                    <img src={existing} alt="signature" className="pointer-events-none absolute inset-0 h-full w-full object-contain p-1" />
                )}
                <canvas
                    ref={canvasRef}
                    className="absolute inset-0 h-full w-full touch-none"
                    onPointerDown={start}
                    onPointerMove={move}
                    onPointerUp={end}
                    onPointerLeave={end}
                />
            </div>
            <button type="button" onClick={clear} className="mt-1 inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-xs text-slate-500 hover:bg-slate-50">
                <X className="h-3 w-3" /> Clear
            </button>
        </div>
    );
}
