import React, { useState, useEffect, lazy, Suspense } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../Components/Pagination';
import CategoryBadge from '../../Components/CategoryBadge';
import Spinner from '../../Components/Spinner';

// Editor Toast UI berat: hanya dimuat saat modal tulis/ubah dibuka.
const MarkdownEditor = lazy(() => import('../../Components/MarkdownEditor'));

const emptyForm = { title: '', category: 'Umum', excerpt: '', content: '', is_published: false };

export default function PostIndex({ posts, filters = {}, categories = [], stats = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [activeMenuId, setActiveMenuId] = useState(null);

    // Create / Edit modal
    const [showFormModal, setShowFormModal] = useState(false);
    const [editingPost, setEditingPost] = useState(null);
    const form = useForm(emptyForm);

    // Delete modal
    const [deletingPost, setDeletingPost] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Quick publish toggle
    const [togglingId, setTogglingId] = useState(null);

    // Import modal
    const [showImportModal, setShowImportModal] = useState(false);
    const importForm = useForm({ importFile: null });

    // AI modal
    const [showAiModal, setShowAiModal] = useState(false);
    const [aiPrompt, setAiPrompt] = useState('');
    const [aiCount, setAiCount] = useState(1);
    const [aiIsGenerating, setAiIsGenerating] = useState(false);
    const [aiItems, setAiItems] = useState([]);
    const [aiSelected, setAiSelected] = useState([]);
    const [aiError, setAiError] = useState('');
    const [isSavingAi, setIsSavingAi] = useState(false);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (!e.target.closest('.post-menu-container')) {
                setActiveMenuId(null);
            }
        };
        document.addEventListener('click', handleClickOutside);
        return () => document.removeEventListener('click', handleClickOutside);
    }, []);

    const applyFilters = (next) => {
        router.get('/dashboard/posts', { search, category: categoryFilter, status: statusFilter, ...next }, {
            preserveState: true,
            replace: true,
        });
    };

    // ---- Create / Edit ----
    const openCreateModal = () => {
        setEditingPost(null);
        form.setData(emptyForm);
        form.clearErrors();
        setShowFormModal(true);
    };

    const openEditModal = (post) => {
        setEditingPost(post);
        form.setData({
            title: post.title,
            category: post.category,
            excerpt: post.excerpt || '',
            content: post.content || '',
            is_published: Boolean(post.is_published),
        });
        form.clearErrors();
        setShowFormModal(true);
    };

    const submitForm = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setShowFormModal(false) };
        if (editingPost) {
            form.put(`/dashboard/posts/${editingPost.id}`, options);
        } else {
            form.post('/dashboard/posts', options);
        }
    };

    // ---- Publish toggle ----
    const togglePublish = (post) => {
        setTogglingId(post.id);
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, {
            preserveScroll: true,
            onFinish: () => setTogglingId(null),
        });
    };

    // ---- Delete ----
    const executeDelete = () => {
        if (!deletingPost) return;
        setIsDeleting(true);
        router.delete(`/dashboard/posts/${deletingPost.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingPost(null),
            onFinish: () => setIsDeleting(false),
        });
    };

    // ---- Import ----
    const submitImport = (e) => {
        e.preventDefault();
        importForm.post('/dashboard/posts/import', {
            forceFormData: true,
            onSuccess: () => {
                setShowImportModal(false);
                importForm.reset();
            },
        });
    };

    // ---- AI Generator ----
    const clampCount = (value) => Math.max(1, Math.min(10, parseInt(value, 10) || 1));

    const generateAi = async () => {
        if (aiPrompt.trim().length < 3) {
            setAiError('Topik / instruksi artikel harus diisi minimal 3 karakter.');
            return;
        }
        setAiIsGenerating(true);
        setAiError('');

        try {
            const res = await fetch('/dashboard/posts/ai-generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ prompt: aiPrompt, count: clampCount(aiCount) }),
            });

            const data = await res.json();
            if (!res.ok || data.success === false) {
                let msg = data.message || 'Gagal menghasilkan artikel dengan AI.';
                if (data.errors) {
                    const list = Object.values(data.errors).flat().join('. ');
                    if (list) msg = list;
                }
                setAiError(msg);
            } else {
                const list = data.items || [];
                setAiItems(list);
                setAiSelected(list.map((_, i) => i));
            }
        } catch (err) {
            setAiError(err.message || 'Terjadi kesalahan jaringan atau server tidak merespon.');
        } finally {
            setAiIsGenerating(false);
        }
    };

    const toggleAiSelect = (index) => {
        setAiSelected((prev) => (prev.includes(index) ? prev.filter((i) => i !== index) : [...prev, index]));
    };

    const saveAiGenerated = () => {
        const items = aiSelected.map((i) => aiItems[i]);
        if (items.length === 0) return;

        setIsSavingAi(true);
        router.post('/dashboard/posts/ai-save', { items }, {
            onSuccess: () => {
                setShowAiModal(false);
                setAiItems([]);
                setAiSelected([]);
                setAiPrompt('');
            },
            onFinish: () => setIsSavingAi(false),
        });
    };

    const statCards = [
        { label: 'Total Artikel', value: stats.total ?? 0, unit: 'tulisan', tone: 'indigo', path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
        { label: 'Total Selesai', value: stats.published ?? 0, unit: 'terpublikasi', tone: 'emerald', path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
        { label: 'Belum Selesai', value: stats.draft ?? 0, unit: 'masih draf', tone: 'amber', path: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    ];
    const toneClasses = {
        indigo: { box: 'bg-indigo-50 text-indigo-600 border-indigo-100', num: 'text-slate-900', unit: 'text-slate-500' },
        emerald: { box: 'bg-emerald-50 text-emerald-700 border-emerald-100', num: 'text-emerald-700', unit: 'text-emerald-700' },
        amber: { box: 'bg-amber-50 text-amber-700 border-amber-100', num: 'text-amber-700', unit: 'text-amber-700' },
    };

    return (
        <AppLayout>
            <Head title="Artikel Saya" />

            <div className="w-full space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Artikel Saya</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Tulis, kelola, dan publikasikan artikel Anda. Draf hanya terlihat oleh Anda sampai dipublikasikan.
                    </p>
                </div>

                {/* Actions Toolbar */}
                <div className="flex flex-wrap items-center gap-2.5">
                    <button
                        onClick={openCreateModal}
                        className="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tulis Artikel</span>
                    </button>

                    <button
                        onClick={() => setShowAiModal(true)}
                        className="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold rounded-xl transition"
                    >
                        <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>AI Generator</span>
                    </button>

                    <a
                        href="/dashboard/posts/export"
                        className="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-xl transition"
                    >
                        <svg className="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export Excel</span>
                    </a>

                    <button
                        onClick={() => setShowImportModal(true)}
                        className="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl transition"
                    >
                        <svg className="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Import Excel</span>
                    </button>
                </div>

                {/* Statistics Summary */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {statCards.map((card) => (
                        <div key={card.label} className="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center space-x-3.5">
                            <div className={`w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border ${toneClasses[card.tone].box}`}>
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={card.path} />
                                </svg>
                            </div>
                            <div>
                                <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">{card.label}</span>
                                <div className="flex items-baseline space-x-1.5 mt-0.5">
                                    <span className={`text-2xl font-black ${toneClasses[card.tone].num}`}>{card.value}</span>
                                    <span className={`text-xs font-medium ${toneClasses[card.tone].unit}`}>{card.unit}</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Filters */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col lg:flex-row gap-3">
                    <div className="flex-1 relative">
                        <input
                            type="text"
                            aria-label="Cari artikel" placeholder="Cari judul atau isi artikel..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                applyFilters({ search: e.target.value });
                            }}
                            className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <svg className="w-4 h-4 text-slate-600 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <select
                        value={categoryFilter}
                        onChange={(e) => {
                            setCategoryFilter(e.target.value);
                            applyFilters({ category: e.target.value });
                        }}
                        aria-label="Filter kategori" className="lg:w-52 py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Kategori</option>
                        {categories.map((cat) => (
                            <option key={cat.value} value={cat.value}>{cat.value}</option>
                        ))}
                    </select>
                    <select
                        value={statusFilter}
                        onChange={(e) => {
                            setStatusFilter(e.target.value);
                            applyFilters({ status: e.target.value });
                        }}
                        aria-label="Filter status" className="lg:w-44 py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Status</option>
                        <option value="published">Dipublikasikan</option>
                        <option value="draft">Draf</option>
                    </select>
                </div>

                {/* Posts List View (list, bukan card, tanpa konten) */}
                {posts.data.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                        <h3 className="text-base font-bold text-slate-800">Belum Ada Artikel</h3>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Mulai menulis artikel pertama Anda, impor dari Excel, atau minta AI merancang draf untuk Anda.
                        </p>
                        <button
                            onClick={openCreateModal}
                            className="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition"
                        >
                            + Tulis Artikel
                        </button>
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl border border-slate-200/90 shadow-xs divide-y divide-slate-100">
                        {posts.data.map((post) => (
                            <div key={post.id} className="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50/80 transition-colors">
                                <div className="min-w-0 flex-1 flex items-center gap-3.5">
                                    {post.cover_image_path ? (
                                        <img src={`/storage/${post.cover_image_path}`} alt="" className="w-12 h-12 rounded-lg object-cover shrink-0 border border-slate-200" />
                                    ) : (
                                        <div className="w-12 h-12 rounded-lg bg-linear-to-br from-indigo-500 to-violet-500 text-white font-black flex items-center justify-center shrink-0">
                                            {post.title?.charAt(0).toUpperCase()}
                                        </div>
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <Link
                                            href={`/dashboard/posts/${post.id}`}
                                            className="block font-semibold text-sm text-slate-900 hover:text-indigo-600 transition truncate"
                                        >
                                            {post.title}
                                        </Link>
                                        <div className="mt-1 flex flex-wrap items-center gap-2">
                                            <CategoryBadge value={post.category} categories={categories} />
                                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                                                post.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                                            }`}>
                                                {post.is_published ? 'Dipublikasikan' : 'Draf'}
                                            </span>
                                            <span className="text-[11px] text-slate-500">
                                                {new Date(post.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2 shrink-0 pl-16 sm:pl-0">
                                    {/* Quick toggle status */}
                                    <button
                                        type="button"
                                        onClick={() => togglePublish(post)}
                                        disabled={togglingId === post.id}
                                        className={`inline-flex items-center space-x-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition disabled:opacity-60 ${
                                            post.is_published
                                                ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                                        }`}
                                    >
                                        {togglingId === post.id && <Spinner className="w-3 h-3 text-current" />}
                                        <span>{post.is_published ? 'Jadikan Draf' : 'Publikasikan'}</span>
                                    </button>

                                    {/* Kebab menu */}
                                    <div className="relative post-menu-container">
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setActiveMenuId(activeMenuId === post.id ? null : post.id);
                                            }}
                                            className="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                            title="Menu Tindakan"
                                            aria-label="Menu Tindakan"
                                        >
                                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="5" r="1.8" />
                                                <circle cx="12" cy="12" r="1.8" />
                                                <circle cx="12" cy="19" r="1.8" />
                                            </svg>
                                        </button>

                                        {activeMenuId === post.id && (
                                            <div className="absolute right-0 mt-1 w-36 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-30">
                                                <Link
                                                    href={`/dashboard/posts/${post.id}`}
                                                    className="w-full px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 flex items-center space-x-2 transition"
                                                >
                                                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>Detail</span>
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setActiveMenuId(null);
                                                        openEditModal(post);
                                                    }}
                                                    className="w-full px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 flex items-center space-x-2 transition text-left"
                                                >
                                                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    <span>Ubah Edit</span>
                                                </button>
                                                <div className="border-t border-slate-100 my-1"></div>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setActiveMenuId(null);
                                                        setDeletingPost(post);
                                                    }}
                                                    className="w-full px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 flex items-center space-x-2 transition text-left"
                                                >
                                                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={posts.links} />
            </div>

            {/* Create / Edit Modal (Full Screen) */}
            {showFormModal && (
                <div className="fixed inset-0 z-50 flex flex-col bg-white">
                    <div className={`px-6 md:px-8 py-4 text-white flex items-center justify-between shrink-0 shadow-xs ${editingPost ? 'bg-indigo-700' : 'bg-blue-700'}`}>
                        <div className="flex items-center space-x-3">
                            <span className="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center font-black text-sm">
                                {editingPost ? '✎' : '+'}
                            </span>
                            <div>
                                <h3 className="text-base font-bold leading-tight">{editingPost ? 'Ubah Artikel' : 'Tulis Artikel Baru'}</h3>
                                <p className="text-xs text-white">Lengkapi judul, kategori, ringkasan, dan isi artikel dalam format Markdown</p>
                            </div>
                        </div>
                        <button
                            onClick={() => setShowFormModal(false)}
                            className="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition"
                            aria-label="Tutup"
                        >
                            ✕
                        </button>
                    </div>

                    <form onSubmit={submitForm} className="flex-1 flex flex-col justify-between overflow-hidden">
                        <div className="flex-1 p-6 md:p-8 space-y-5 overflow-y-auto w-full">
                            <div>
                                <label htmlFor="post-form-1" className="block text-xs font-bold uppercase tracking-wider text-slate-700">Judul Artikel *</label>
                                <input id="post-form-1"
                                    type="text"
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    required
                                    className="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Contoh: Memulai Perjalanan dengan Laravel 13"
                                />
                                {form.errors.title && <p className="mt-1 text-xs text-rose-700">{form.errors.title}</p>}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="post-form-2" className="block text-xs font-bold uppercase tracking-wider text-slate-700">Kategori *</label>
                                    <select id="post-form-2"
                                        value={form.data.category}
                                        onChange={(e) => form.setData('category', e.target.value)}
                                        className="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                                    >
                                        {categories.map((cat) => (
                                            <option key={cat.value} value={cat.value}>{cat.label}</option>
                                        ))}
                                    </select>
                                    {form.errors.category && <p className="mt-1 text-xs text-rose-700">{form.errors.category}</p>}
                                </div>
                                <div className="flex items-end">
                                    <label className="flex items-center space-x-2.5 px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 w-full cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={form.data.is_published}
                                            onChange={(e) => form.setData('is_published', e.target.checked)}
                                            className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="text-sm font-semibold text-slate-700">Publikasikan ke umum</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label htmlFor="post-form-3" className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Ringkasan (1-2 kalimat pemikat pembaca)
                                </label>
                                <textarea id="post-form-3"
                                    rows={2}
                                    value={form.data.excerpt}
                                    onChange={(e) => form.setData('excerpt', e.target.value)}
                                    className="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Kosongkan untuk membuat ringkasan otomatis dari isi artikel"
                                />
                                {form.errors.excerpt && <p className="mt-1 text-xs text-rose-700">{form.errors.excerpt}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Isi Artikel * (Toast UI Markdown Editor)
                                </label>
                                <Suspense fallback={<div className="h-40 flex items-center justify-center text-sm text-slate-600">Memuat editor...</div>}>
                                    <MarkdownEditor
                                        key={editingPost ? `edit-${editingPost.id}` : 'create-post'}
                                        value={form.data.content}
                                        onChange={(val) => form.setData('content', val)}
                                        height="480px"
                                        placeholder="Tuliskan artikel Anda dalam format Markdown..."
                                    />
                                </Suspense>
                                {form.errors.content && <p className="mt-1 text-xs text-rose-700">{form.errors.content}</p>}
                            </div>
                        </div>

                        <div className="px-6 md:px-8 py-4 bg-slate-100 border-t border-slate-200 flex items-center justify-end space-x-3 shrink-0">
                            <button
                                type="button"
                                onClick={() => setShowFormModal(false)}
                                className="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-200/70 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs disabled:opacity-50 flex items-center space-x-2 transition"
                            >
                                {form.processing && <Spinner className="w-3.5 h-3.5 text-white" />}
                                <span>{form.processing ? 'Menyimpan...' : editingPost ? 'Simpan Perubahan' : 'Simpan Artikel'}</span>
                            </button>
                        </div>
                    </form>
                </div>
            )}

            {/* Delete Modal */}
            {deletingPost && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
                    <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-rose-100 overflow-hidden">
                        <div className="px-6 py-4 bg-rose-600 text-white flex items-center justify-between">
                            <h3 className="text-sm font-bold">Konfirmasi Hapus Artikel</h3>
                            <button onClick={() => setDeletingPost(null)} className="text-rose-200 hover:text-white p-1" aria-label="Tutup">✕</button>
                        </div>
                        <div className="p-6 text-center">
                            <h3 className="text-base font-bold text-slate-900">Hapus Artikel Ini?</h3>
                            <p className="text-xs text-slate-500 mt-1">
                                Artikel "{deletingPost.title}" beserta foto covernya akan dihapus secara permanen.
                            </p>
                        </div>
                        <div className="px-6 py-3.5 bg-rose-50 border-t border-rose-100 flex items-center justify-end space-x-2">
                            <button onClick={() => setDeletingPost(null)} className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/70 rounded-xl">
                                Batal
                            </button>
                            <button
                                onClick={executeDelete}
                                disabled={isDeleting}
                                className="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-xs disabled:opacity-50 flex items-center space-x-1.5"
                            >
                                {isDeleting && <Spinner className="w-3.5 h-3.5 text-white" />}
                                <span>{isDeleting ? 'Menghapus...' : 'Ya, Hapus Artikel'}</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Import Excel Modal */}
            {showImportModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
                    <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
                        <div className="px-6 py-4 bg-slate-800 text-white flex items-center justify-between">
                            <h3 className="text-base font-bold">Import Artikel dari Excel</h3>
                            <button onClick={() => setShowImportModal(false)} className="text-slate-300 hover:text-white" aria-label="Tutup">✕</button>
                        </div>
                        <form onSubmit={submitImport}>
                            <div className="p-6 space-y-4">
                                <div>
                                    <label htmlFor="post-form-4" className="block text-xs font-semibold text-slate-700 mb-1">Pilih File Excel (.xlsx, .xls, .csv)</label>
                                    <input id="post-form-4"
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        onChange={(e) => importForm.setData('importFile', e.target.files[0])}
                                        required
                                        className="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    />
                                    {importForm.errors.importFile && <p className="mt-1 text-xs text-rose-700">{importForm.errors.importFile}</p>}
                                </div>
                                <p className="text-[11px] text-slate-600">
                                    Kolom yang dibaca: A No, B Title, C Category, D Excerpt, E Content (Markdown), F Status (Published / Draft).
                                    Gunakan hasil Export Excel sebagai templat.
                                </p>
                            </div>
                            <div className="px-6 py-3.5 bg-slate-100 border-t border-slate-200 flex items-center justify-end space-x-2">
                                <button type="button" onClick={() => setShowImportModal(false)} className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/70 rounded-xl">
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={importForm.processing}
                                    className="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl disabled:opacity-50 flex items-center space-x-1.5"
                                >
                                    {importForm.processing && <Spinner className="w-3.5 h-3.5 text-white" />}
                                    <span>{importForm.processing ? 'Mengimpor...' : 'Import Data'}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* AI Generator Modal */}
            {showAiModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
                    <div className="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-purple-100 overflow-hidden">
                        <div className="px-6 py-4 bg-purple-700 text-white flex items-center justify-between">
                            <div className="flex items-center space-x-2">
                                <div className="w-7 h-7 rounded-lg bg-white text-purple-700 flex items-center justify-center font-bold text-xs shadow-xs">AI</div>
                                <h3 className="text-base font-bold">Rancang Artikel dengan OpenAI</h3>
                            </div>
                            <button onClick={() => setShowAiModal(false)} className="text-purple-200 hover:text-white" aria-label="Tutup">✕</button>
                        </div>

                        <div className="p-6 space-y-3">
                            <div>
                                <label htmlFor="post-form-5" className="block text-xs font-semibold text-slate-700 mb-1">Topik / Instruksi Artikel</label>
                                <textarea id="post-form-5"
                                    rows={3}
                                    value={aiPrompt}
                                    onChange={(e) => setAiPrompt(e.target.value)}
                                    placeholder="Contoh: Tulis artikel tentang manfaat belajar pemrograman web untuk mahasiswa..."
                                    className="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
                                />
                            </div>

                            <div className="flex items-center space-x-3">
                                <label className="text-xs font-semibold text-slate-700">Jumlah Artikel:</label>
                                <div className="w-24">
                                    <input
                                        type="number"
                                        min="1"
                                        max="10"
                                        value={aiCount}
                                        onChange={(e) => setAiCount(e.target.value === '' ? '' : clampCount(e.target.value))}
                                        onBlur={() => setAiCount(clampCount(aiCount))}
                                        className="w-full px-3 py-1.5 rounded-xl border border-slate-300 text-xs text-center font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                        aria-label="Jumlah artikel" placeholder="1"
                                    />
                                </div>
                                <span className="text-xs text-slate-500">(1 - 10)</span>

                                <button
                                    type="button"
                                    onClick={generateAi}
                                    disabled={aiIsGenerating || !aiPrompt.trim()}
                                    className="ml-auto px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-xl transition disabled:opacity-50 flex items-center space-x-1.5"
                                >
                                    {aiIsGenerating && <Spinner className="w-3.5 h-3.5 text-white" />}
                                    <span>{aiIsGenerating ? 'Sedang Menulis...' : 'Generate Artikel'}</span>
                                </button>
                            </div>

                            {aiError && (
                                <div className="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl">
                                    <p className="font-semibold text-rose-800">Gagal Memproses Permintaan AI:</p>
                                    <p className="mt-0.5 leading-relaxed">{aiError}</p>
                                </div>
                            )}

                            {aiItems.length > 0 && (
                                <div className="mt-4 pt-4 border-t border-slate-100 space-y-2">
                                    <span className="text-xs font-bold text-slate-700">
                                        Pilih artikel yang ingin disimpan sebagai draf ({aiSelected.length}/{aiItems.length}):
                                    </span>
                                    <div className="max-h-60 overflow-y-auto space-y-2 pr-1">
                                        {aiItems.map((item, idx) => (
                                            <div
                                                key={idx}
                                                onClick={() => toggleAiSelect(idx)}
                                                className={`p-3 rounded-xl border cursor-pointer transition text-xs flex items-start space-x-2.5 ${
                                                    aiSelected.includes(idx) ? 'border-purple-500 bg-purple-50/50' : 'border-slate-200 bg-white hover:border-slate-300'
                                                }`}
                                            >
                                                <input type="checkbox" checked={aiSelected.includes(idx)} onChange={() => {}} className="mt-0.5 rounded text-purple-600" />
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-bold text-slate-800">{item.title}</p>
                                                    <span className="inline-block mt-1 text-[10px] bg-slate-200/70 text-slate-700 px-1.5 py-0.5 rounded">{item.category}</span>
                                                    {item.excerpt && <p className="mt-1 text-slate-500 line-clamp-2">{item.excerpt}</p>}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="px-6 py-3.5 bg-purple-50 border-t border-purple-100 flex items-center justify-end space-x-2">
                            <button type="button" onClick={() => setShowAiModal(false)} className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/70 rounded-xl">
                                Tutup
                            </button>
                            {aiItems.length > 0 && (
                                <button
                                    type="button"
                                    onClick={saveAiGenerated}
                                    disabled={isSavingAi || aiSelected.length === 0}
                                    className="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs flex items-center space-x-1.5 disabled:opacity-50"
                                >
                                    {isSavingAi && <Spinner className="w-3.5 h-3.5 text-white" />}
                                    <span>{isSavingAi ? 'Menyimpan...' : `Simpan Artikel Terpilih (${aiSelected.length})`}</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
