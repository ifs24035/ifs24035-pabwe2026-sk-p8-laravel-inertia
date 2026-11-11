import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminPostFilters from '../../Components/AdminPostFilters';
import AdminPostTable from '../../Components/AdminPostTable';

export default function AdminPostList({ posts, filters, categories }) {
    return (
        <AppLayout>
            <Head title="Semua Artikel - Admin" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Monitoring Semua Artikel</h1>
                        <p className="text-sm text-slate-500 mt-0.5">
                            Pantau seluruh artikel dari semua penulis (mode baca saja). Total {posts.total ?? 0} artikel.
                        </p>
                    </div>
                    <a
                        href="/admin/posts/export"
                        className="inline-flex items-center space-x-1.5 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-xl transition self-start"
                    >
                        <svg className="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export Semua ke Excel</span>
                    </a>
                </div>

                <AdminPostFilters url="/admin/posts" filters={filters} categories={categories} />
                <AdminPostTable posts={posts} categories={categories} />
            </div>
        </AppLayout>
    );
}
