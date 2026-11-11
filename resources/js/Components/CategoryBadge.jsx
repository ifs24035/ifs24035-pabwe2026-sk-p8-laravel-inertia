import React from 'react';

export default function CategoryBadge({ value, categories = [], className = '' }) {
    const found = categories.find((c) => c.value === value);
    const badge = found?.badgeClass || 'bg-slate-100 text-slate-800 border-slate-300';

    return (
        <span className={`inline-block text-[10px] font-bold px-2 py-0.5 rounded-full border ${badge} ${className}`}>
            {value}
        </span>
    );
}
