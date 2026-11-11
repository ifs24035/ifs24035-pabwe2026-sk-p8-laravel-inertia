import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import Spinner from './Spinner';

/**
 * Top progress bar + floating "Memproses tindakan..." pill shown while an
 * Inertia visit / request is running.
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
            <div className="fixed top-0 left-0 right-0 z-[60] h-1 bg-linear-to-r from-indigo-600 via-violet-600 to-fuchsia-500">
                <div className="h-full bg-white/50 w-full animate-pulse"></div>
            </div>
            <div className="fixed bottom-5 right-5 z-[60] bg-slate-900/95 text-white px-4 py-2.5 rounded-2xl shadow-xl flex items-center space-x-2.5 text-xs font-semibold backdrop-blur-sm border border-slate-700/60">
                <Spinner className="w-4 h-4 text-indigo-400" />
                <span>Memproses tindakan...</span>
            </div>
        </>
    );
}
