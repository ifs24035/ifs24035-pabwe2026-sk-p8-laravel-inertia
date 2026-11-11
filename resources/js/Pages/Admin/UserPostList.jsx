import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminPostFilters from '../../Components/AdminPostFilters';
import AdminPostTable from '../../Components/AdminPostTable';

export default function AdminUserPostList({ targetUser, posts, filters, categories }) {
    return (
        <AppLayout>
            <Head title={`Artikel ${targetUser.name} - Admin`} />

            <div className="space-y-6">
                <Link
                    href="/admin/users"
                    className="inline-flex items-center space-x-2 text-sm font-semibold text-slate-600 hover:text-slate-900 transition"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Manajemen User</span>
                </Link>

                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Artikel {targetUser.name}</h1>
                        <p className="text-sm text-slate-500 mt-0.5">
                            @{targetUser.username} &middot; {targetUser.email} &middot; {targetUser.role} &middot; mode baca saja ({posts.total ?? 0} artikel)
                        </p>
                    </div>
                    <a
                        href={`/admin/users/${targetUser.id}/posts/export`}
                        className="inline-flex items-center space-x-1.5 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-xl transition self-start"
                    >
                        <span>Export Artikel User ke Excel</span>
                    </a>
                </div>

                <AdminPostFilters url={`/admin/users/${targetUser.id}/posts`} filters={filters} categories={categories} />
                <AdminPostTable posts={posts} categories={categories} showAuthor={false} />
            </div>
        </AppLayout>
    );
}
