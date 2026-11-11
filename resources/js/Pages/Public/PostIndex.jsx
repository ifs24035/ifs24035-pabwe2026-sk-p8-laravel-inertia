import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Pagination from '../../Components/Pagination';
import CategoryBadge from '../../Components/CategoryBadge';
import Avatar from '../../Components/Avatar';

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

export default function PublicPostIndex({ posts, filters = {}, categories = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [search, setSearch] = useState(filters.search || '');
    const activeCategory = filters.category || '';

    const visit = (params) => {
        router.get('/articles', params, { preserveState: true, preserveScroll: false });
    };

    const submitSearch = (e) => {
        e.preventDefault();
        visit({ search, category: activeCategory });
    };

    const selectCategory = (value) => {
        visit({ search, category: value });
    };

    return (
        <PublicLayout>
            <Head title="Beranda" />

            {/* Hero Banner */}
            <section className="bg-linear-to-br from-slate-900 via-indigo-900 to-violet-800 text-white">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
                    <span className="inline-block bg-white/15 text-indigo-100 text-xs font-semibold px-3 py-1 rounded-full mb-4 backdrop-blur-xs">
                        Platform Publikasi Artikel
                    </span>
                    <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight max-w-3xl leading-tight">
                        Temukan gagasan, bagikan tulisan, bangun komunitas.
                    </h1>
                    <p className="mt-4 text-indigo-100 text-base sm:text-lg max-w-2xl leading-relaxed">
                        BlogVerse Pro mempertemukan penulis dan pembaca. Baca artikel pilihan dari berbagai kategori tanpa perlu login,
                        atau bergabung untuk mulai menulis dan mempublikasikan karya Anda sendiri.
                    </p>

                    <div className="mt-8 flex flex-wrap items-center gap-3">
                        {user ? (
                            <Link
                                href={user.role === 'admin' ? '/admin/users' : '/dashboard/posts'}
                                className="px-6 py-3 rounded-xl bg-white text-indigo-800 font-bold text-sm hover:bg-indigo-50 shadow-md transition"
                            >
                                {user.role === 'admin' ? 'Buka Panel Admin' : 'Buka Dashboard Penulis'}
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-indigo-800 font-bold text-sm hover:bg-indigo-50 shadow-md transition"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Masuk</span>
                                </Link>
                                <Link
                                    href="/register"
                                    className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-violet-500 hover:bg-violet-400 text-white font-bold text-sm shadow-md transition"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span>Daftar Penulis</span>
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </section>

            {/* Search & Category Filter */}
            <section className="max-w-6xl mx-auto px-4 sm:px-6 -mt-7">
                <form onSubmit={submitSearch} className="bg-white rounded-2xl shadow-lg border border-slate-200 p-3 flex flex-col sm:flex-row gap-2">
                    <div className="relative flex-1">
                        <svg className="w-5 h-5 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari artikel berdasarkan judul atau isi..."
                            className="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <button type="submit" className="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white text-sm font-semibold transition">
                        Cari
                    </button>
                </form>

                <div className="mt-4 flex items-center gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        onClick={() => selectCategory('')}
                        className={`px-3.5 py-1.5 rounded-full text-xs font-semibold shrink-0 border transition ${
                            !activeCategory ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                        }`}
                    >
                        Semua
                    </button>
                    {categories.map((cat) => (
                        <button
                            key={cat.value}
                            type="button"
                            onClick={() => selectCategory(cat.value)}
                            className={`px-3.5 py-1.5 rounded-full text-xs font-semibold shrink-0 border transition ${
                                activeCategory === cat.value
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                            }`}
                        >
                            {cat.value}
                        </button>
                    ))}
                </div>
            </section>

            {/* Articles Gallery */}
            <section className="max-w-6xl mx-auto px-4 sm:px-6 mt-8">
                {posts.data.length === 0 ? (
                    <div className="bg-slate-50 rounded-2xl border border-slate-200 p-12 text-center">
                        <h3 className="text-base font-bold text-slate-800">Belum ada artikel ditemukan</h3>
                        <p className="text-sm text-slate-500 mt-1">Coba gunakan kata kunci atau kategori yang lain.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {posts.data.map((post) => (
                            <article
                                key={post.id}
                                className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-lg hover:-translate-y-0.5 transition flex flex-col"
                            >
                                <Link href={`/articles/${post.slug}`} className="block">
                                    {post.cover_image_path ? (
                                        <img src={`/storage/${post.cover_image_path}`} alt={post.title} className="h-44 w-full object-cover" />
                                    ) : (
                                        <div className="h-44 w-full bg-linear-to-br from-indigo-500 via-violet-500 to-fuchsia-500 flex items-center justify-center text-white/90 text-5xl font-black">
                                            {post.title?.charAt(0).toUpperCase()}
                                        </div>
                                    )}
                                </Link>
                                <div className="p-5 flex-1 flex flex-col">
                                    <CategoryBadge value={post.category} categories={categories} className="self-start" />
                                    <Link href={`/articles/${post.slug}`}>
                                        <h2 className="mt-3 text-lg font-bold text-slate-900 leading-snug line-clamp-2 hover:text-indigo-700 transition">
                                            {post.title}
                                        </h2>
                                    </Link>
                                    <p className="mt-2 text-sm text-slate-500 line-clamp-3 flex-1">{post.excerpt}</p>
                                    <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                        <span className="flex items-center gap-2 min-w-0">
                                            <Avatar name={post.user?.name} path={post.user?.profile_photo_path} className="w-6 h-6 text-[10px]" />
                                            <span className="truncate font-medium text-slate-700">{post.user?.name}</span>
                                        </span>
                                        <span className="shrink-0">{formatDate(post.published_at)}</span>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}

                <Pagination links={posts.links} />
            </section>
        </PublicLayout>
    );
}
