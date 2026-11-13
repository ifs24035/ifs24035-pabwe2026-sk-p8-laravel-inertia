import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import ProcessingIndicator from '../Components/ProcessingIndicator';
import Avatar from '../Components/Avatar';

const IconLogin = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
    </svg>
);

const IconUserPlus = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
    </svg>
);

/**
 * Layout for public (guest) pages: Sticky Top Navbar, full-width reading area
 * and an editorial footer. No sidebar.
 */
export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [menuOpen, setMenuOpen] = useState(false);
    const path = typeof window !== 'undefined' ? window.location.pathname : '/';

    const navLink = (href, label, active) => (
        <Link
            href={href}
            className={`px-3 py-2 rounded-lg text-sm font-semibold transition ${
                active ? 'text-indigo-700 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
        >
            {label}
        </Link>
    );

    const dashboardHref = user?.role === 'admin' ? '/admin/users' : '/dashboard/posts';
    const dashboardLabel = user?.role === 'admin' ? 'Panel Admin' : 'Dashboard Penulis';

    return (
        <div className="min-h-screen bg-white flex flex-col">
            <ProcessingIndicator />

            {/* Sticky Top Navbar */}
            <header className="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-6">
                        <Link href="/" className="flex items-center gap-2.5">
                            <span className="w-9 h-9 rounded-xl bg-linear-to-br from-slate-900 via-indigo-700 to-violet-600 text-white font-black flex items-center justify-center shadow-sm">
                                B
                            </span>
                            <span className="font-extrabold text-slate-900 text-lg tracking-tight">
                                BlogVerse <span className="text-indigo-600">Pro</span>
                            </span>
                        </Link>
                        <nav aria-label="Navigasi utama" className="hidden md:flex items-center gap-1">
                            {navLink('/', 'Beranda', path === '/')}
                            {navLink('/articles', 'Artikel', path.startsWith('/articles'))}
                        </nav>
                    </div>

                    <div className="hidden md:flex items-center gap-2">
                        {user ? (
                            <>
                                <Link
                                    href={dashboardHref}
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-xs transition"
                                >
                                    <Avatar name={user.name} path={user.profile_photo_path} className="w-6 h-6 text-[10px]" />
                                    <span>{dashboardLabel}</span>
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => router.post('/logout')}
                                    className="px-3 py-2 rounded-xl text-sm font-semibold text-rose-700 hover:bg-rose-50 transition"
                                >
                                    Keluar
                                </button>
                            </>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-sm font-semibold transition"
                                >
                                    <IconLogin />
                                    <span>Masuk</span>
                                </Link>
                                <Link
                                    href="/register"
                                    className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-xs transition"
                                >
                                    <IconUserPlus />
                                    <span>Daftar</span>
                                </Link>
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={() => setMenuOpen(!menuOpen)}
                        className="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                        aria-label="Toggle Navigation"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {menuOpen ? (
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            ) : (
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            )}
                        </svg>
                    </button>
                </div>

                {menuOpen && (
                    <nav aria-label="Navigasi seluler" className="md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1">
                        {navLink('/', 'Beranda', path === '/')}
                        {navLink('/articles', 'Artikel', path.startsWith('/articles'))}
                        <div className="pt-2 flex flex-col gap-2">
                            {user ? (
                                <>
                                    <Link href={dashboardHref} className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold text-center">
                                        {dashboardLabel}
                                    </Link>
                                    <button type="button" onClick={() => router.post('/logout')} className="px-4 py-2 rounded-xl text-rose-700 text-sm font-semibold hover:bg-rose-50">
                                        Keluar
                                    </button>
                                </>
                            ) : (
                                <>
                                    <Link href="/login" className="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold text-center">
                                        Masuk
                                    </Link>
                                    <Link href="/register" className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold text-center">
                                        Daftar
                                    </Link>
                                </>
                            )}
                        </div>
                    </nav>
                )}
            </header>

            <main className="flex-1">{children}</main>

            {/* Editorial Footer */}
            <footer className="bg-slate-900 text-slate-300 mt-16">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <p className="text-white font-extrabold text-lg tracking-tight">
                            BlogVerse <span className="text-indigo-400">Pro</span>
                        </p>
                        <p className="text-sm text-slate-400 mt-1 max-w-sm">
                            Platform publikasi artikel multi-tier untuk para penulis dan pembaca yang haus akan gagasan.
                        </p>
                    </div>
                    <nav aria-label="Navigasi footer" className="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold">
                        <Link href="/" className="hover:text-white transition">Beranda Publik</Link>
                        <Link href="/articles" className="hover:text-white transition">Semua Artikel</Link>
                        <Link href="/login" className="hover:text-white transition">Halaman Login</Link>
                        <Link href="/register" className="hover:text-white transition">Halaman Register</Link>
                    </nav>
                </div>
                <div className="border-t border-slate-800 py-4 text-center text-xs text-slate-400">
                    &copy; {new Date().getFullYear()} BlogVerse Pro &middot; Dibangun dengan Laravel 13, Inertia.js &amp; React
                </div>
            </footer>
        </div>
    );
}
