import React from 'react';
import { Link } from '@inertiajs/react';

export default function Pagination({ links = [], activeClass = 'bg-indigo-600 text-white border-indigo-600' }) {
    if (!links || links.length <= 3) return null;

    return (
        <div className="flex flex-wrap items-center justify-center gap-1 pt-4">
            {links.map((link, idx) =>
                link.url ? (
                    <Link
                        key={idx}
                        href={link.url}
                        preserveScroll
                        dangerouslySetInnerHTML={{ __html: link.label }}
                        className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition ${
                            link.active ? activeClass : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                        }`}
                    />
                ) : (
                    <span
                        key={idx}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                        className="px-3 py-1.5 text-xs font-semibold rounded-lg border bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed"
                    />
                )
            )}
        </div>
    );
}
