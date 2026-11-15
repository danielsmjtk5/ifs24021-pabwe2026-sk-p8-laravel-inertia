import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import Spinner from './Spinner';

/**
 * Top progress bar + floating "Memproses tindakan..." pill,
 * shown while any Inertia visit or request is running.
 */
export default function ProcessingIndicator() {
    const [isProcessing, setIsProcessing] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setIsProcessing(true));
        const removeFinish = router.on('finish', () => setIsProcessing(false));
        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    if (!isProcessing) return null;

    return (
        <>
            <div className="fixed top-0 left-0 right-0 z-[60] h-1 bg-indigo-100 overflow-hidden" role="progressbar" aria-label="Memuat">
                <div className="h-full w-1/3 bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 animate-progress"></div>
            </div>
            <div
                className="fixed bottom-5 right-5 z-[60] bg-slate-900/95 text-white px-4 py-2.5 rounded-2xl shadow-xl flex items-center gap-2.5 text-xs font-semibold border border-slate-700/60"
                role="status"
                aria-live="polite"
            >
                <Spinner className="w-4 h-4 text-indigo-300" />
                <span>Memproses tindakan...</span>
            </div>
        </>
    );
}
