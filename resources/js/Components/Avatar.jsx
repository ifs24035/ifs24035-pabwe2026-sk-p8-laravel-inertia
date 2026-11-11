import React, { useEffect, useState } from 'react';

export default function Avatar({ name = '', path = null, className = 'w-8 h-8 text-xs' }) {
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        setFailed(false);
    }, [path]);

    return (
        <span className={`rounded-full overflow-hidden bg-indigo-600 text-white font-bold inline-flex items-center justify-center shrink-0 border border-slate-200 ${className}`}>
            {path && !failed ? (
                <img src={`/storage/${path}`} alt={name} className="w-full h-full object-cover" onError={() => setFailed(true)} />
            ) : (
                <span>{(name || '?').charAt(0).toUpperCase()}</span>
            )}
        </span>
    );
}
