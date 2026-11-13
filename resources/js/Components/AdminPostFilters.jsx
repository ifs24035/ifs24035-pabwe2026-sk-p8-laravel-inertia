import React, { useState } from 'react';
import { router } from '@inertiajs/react';

export default function AdminPostFilters({ url, filters = {}, categories = [] }) {
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '');
    const [status, setStatus] = useState(filters.status || '');

    const apply = (next) => {
        router.get(url, { search, category, status, ...next }, { preserveState: true, replace: true });
    };

    return (
        <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col lg:flex-row gap-3">
            <div className="flex-1 relative">
                <input
                    type="text"
                    aria-label="Cari artikel" placeholder="Cari judul atau isi artikel..."
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        apply({ search: e.target.value });
                    }}
                    className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                />
                <svg className="w-4 h-4 text-slate-600 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <select
                value={category}
                onChange={(e) => {
                    setCategory(e.target.value);
                    apply({ category: e.target.value });
                }}
                aria-label="Filter kategori" className="lg:w-52 py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
            >
                <option value="">Semua Kategori</option>
                {categories.map((cat) => (
                    <option key={cat.value} value={cat.value}>{cat.value}</option>
                ))}
            </select>
            <select
                value={status}
                onChange={(e) => {
                    setStatus(e.target.value);
                    apply({ status: e.target.value });
                }}
                aria-label="Filter status" className="lg:w-44 py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
            >
                <option value="">Semua Status</option>
                <option value="published">Dipublikasikan</option>
                <option value="draft">Draf</option>
            </select>
        </div>
    );
}
