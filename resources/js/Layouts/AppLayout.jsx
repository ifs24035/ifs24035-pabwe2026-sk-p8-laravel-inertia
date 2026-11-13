import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import ProcessingIndicator from '../Components/ProcessingIndicator';
import Avatar from '../Components/Avatar';

const Icon = ({ d }) => (
    <svg className="w-5 h-5 text-current shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={d} />
    </svg>
);

/**
 * Layout for authenticated pages (author dashboard, profile and admin panel).
 */
export default function AppLayout({ children }) {
    const { auth, flash } = usePage().props;
    const user = auth?.user;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const path = typeof window !== 'undefined' ? window.location.pathname : '';

    const activeTones = {
        indigo: 'bg-indigo-50 text-indigo-700 font-semibold shadow-xs',
        purple: 'bg-purple-50 text-purple-700 font-semibold shadow-xs',
    };

    const linkClass = (active, tone = 'indigo') =>
        `flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition ${
            active ? activeTones[tone] : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
        }`;

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col md:flex-row">
            <ProcessingIndicator />

            {/* Mobile Header */}
            <header className="md:hidden bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between sticky top-0 z-30">
                <div className="flex items-center space-x-2">
                    <div className="w-8 h-8 rounded-lg bg-linear-to-br from-slate-900 via-indigo-700 to-violet-600 flex items-center justify-center text-white font-black text-sm">B</div>
                    <span className="font-bold text-slate-800 text-lg">BlogVerse Pro</span>
                </div>
                <button
                    onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    className="p-2 text-slate-600 hover:text-slate-900 rounded-lg focus:outline-none"
                    aria-label="Toggle Navigation"
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {mobileMenuOpen ? (
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        ) : (
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        )}
                    </svg>
                </button>
            </header>

            {/* Sidebar Navigation */}
            <aside
                aria-label="Sidebar"
                className={`fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out md:sticky md:top-0 md:h-screen md:translate-x-0 ${
                    mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="p-6 border-b border-slate-100">
                    <Link href="/" className="flex items-center space-x-3">
                        <div className="w-9 h-9 rounded-xl bg-linear-to-br from-slate-900 via-indigo-700 to-violet-600 flex items-center justify-center text-white font-black text-lg shadow-sm">B</div>
                        <div>
                            <span className="font-bold text-slate-900 text-lg tracking-tight block">BlogVerse Pro</span>
                            <span className="text-[10px] font-semibold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">
                                Inertia React
                            </span>
                        </div>
                    </Link>
                </div>

                {user && (
                    <div className="p-4 mx-4 my-4 bg-slate-50 rounded-xl border border-slate-200/70 flex items-center space-x-3">
                        <Avatar name={user.name} path={user.profile_photo_path} className="w-10 h-10 text-sm" />
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-semibold text-slate-800 truncate">{user.name}</p>
                            <p className="text-xs text-slate-500 truncate">@{user.username}</p>
                            <span className={`inline-block text-[10px] font-bold px-2 py-0.5 mt-1 rounded-full ${
                                user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-slate-200 text-slate-700'
                            }`}>
                                {user.role?.toUpperCase()}
                            </span>
                        </div>
                    </div>
                )}

                <nav aria-label="Navigasi dashboard" className="flex-1 px-4 space-y-1.5 overflow-y-auto">
                    <Link href="/dashboard/posts" className={linkClass(path.startsWith('/dashboard'))}>
                        <Icon d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        <span>Artikel Saya</span>
                    </Link>

                    <Link href="/" className={linkClass(false)}>
                        <Icon d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        <span>Lihat Blog Publik</span>
                    </Link>

                    <Link href="/profile" className={linkClass(path.startsWith('/profile'))}>
                        <Icon d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        <span>Profil Saya</span>
                    </Link>

                    {user?.role === 'admin' && (
                        <div className="pt-4 mt-4 border-t border-slate-100 space-y-1.5">
                            <span className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-600 block mb-2">Admin Area</span>
                            <Link href="/admin/users" className={linkClass(path.startsWith('/admin/users'), 'purple')}>
                                <Icon d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                <span>Manajemen User</span>
                            </Link>
                            <Link href="/admin/posts" className={linkClass(path.startsWith('/admin/posts'), 'purple')}>
                                <Icon d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2zM9 12h6m-6 4h6m-6-8h2" />
                                <span>Semua Artikel</span>
                            </Link>
                        </div>
                    )}
                </nav>

                <div className="p-4 border-t border-slate-100">
                    <button
                        onClick={() => router.post('/logout')}
                        className="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-rose-700 hover:bg-rose-50 transition"
                    >
                        <Icon d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        <span>Keluar (Logout)</span>
                    </button>
                </div>
            </aside>

            {mobileMenuOpen && (
                <div onClick={() => setMobileMenuOpen(false)} className="fixed inset-0 bg-slate-900/40 z-30 md:hidden" />
            )}

            <main className="flex-1 flex flex-col min-w-0">
                {flash?.success && (
                    <div className="m-4 mb-0 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center space-x-2 text-sm shadow-xs">
                        <svg className="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="m-4 mb-0 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-center space-x-2 text-sm shadow-xs">
                        <svg className="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{flash.error}</span>
                    </div>
                )}

                <div className="p-4 sm:p-6 lg:p-8 flex-1">{children}</div>
            </main>
        </div>
    );
}
