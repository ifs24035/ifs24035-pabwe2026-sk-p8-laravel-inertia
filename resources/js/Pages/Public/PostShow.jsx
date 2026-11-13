import React, { lazy, Suspense } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import CategoryBadge from '../../Components/CategoryBadge';
import Avatar from '../../Components/Avatar';

const MarkdownViewer = lazy(() => import('../../Components/MarkdownViewer'));

export default function PublicPostShow({ post, related = [], categories = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <PublicLayout>
            <Head title={post.title} />

            <article className="w-full">
                {/* Cover */}
                {post.cover_image_path && (
                    <div className="w-full max-h-[460px] overflow-hidden bg-slate-900">
                        <img src={`/storage/${post.cover_image_path}`} alt={post.title} className="w-full h-full max-h-[460px] object-cover" />
                    </div>
                )}

                {/* Full-width reading layout */}
                <div className="max-w-3xl mx-auto px-4 sm:px-6 py-10">
                    <Link href="/articles" className="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
                        &larr; Semua artikel
                    </Link>

                    <div className="mt-5">
                        <CategoryBadge value={post.category} categories={categories} />
                        <h1 className="mt-3 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            {post.title}
                        </h1>
                        {post.excerpt && <p className="mt-4 text-lg text-slate-600 leading-relaxed">{post.excerpt}</p>}

                        <div className="mt-6 flex items-center gap-3 pb-6 border-b border-slate-200">
                            <Avatar name={post.author?.name} path={post.author?.profile_photo_path} className="w-10 h-10 text-sm" />
                            <div className="text-sm">
                                <p className="font-bold text-slate-900">{post.author?.name}</p>
                                <p className="text-slate-500">
                                    @{post.author?.username}
                                    {post.published_at && <span> &middot; {post.published_at}</span>}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8">
                        <Suspense fallback={<p className="text-sm text-slate-400 min-h-40">Memuat artikel...</p>}>
                            <MarkdownViewer content={post.content} />
                        </Suspense>
                    </div>

                    {/* Community CTA Card */}
                    <div className="mt-14 rounded-2xl bg-linear-to-br from-slate-900 via-indigo-900 to-violet-800 text-white p-8 text-center shadow-lg">
                        {user ? (
                            <>
                                <h3 className="text-xl font-extrabold">Punya gagasan untuk dibagikan?</h3>
                                <p className="mt-2 text-sm text-indigo-100">Kelola dan terbitkan artikel Anda sendiri dari dashboard.</p>
                                <Link
                                    href={user.role === 'admin' ? '/admin/users' : '/dashboard/posts'}
                                    className="inline-block mt-5 px-6 py-2.5 rounded-xl bg-white text-indigo-800 font-bold text-sm hover:bg-indigo-50 transition"
                                >
                                    {user.role === 'admin' ? 'Buka Panel Admin' : 'Buka Dashboard Penulis'}
                                </Link>
                            </>
                        ) : (
                            <>
                                <h3 className="text-xl font-extrabold">Suka dengan tulisan ini? Bergabunglah dengan komunitas kami.</h3>
                                <p className="mt-2 text-sm text-indigo-100 max-w-md mx-auto">
                                    Masuk untuk mengelola artikel Anda, atau daftar sebagai penulis dan mulailah menerbitkan karya sendiri.
                                </p>
                                <div className="mt-5 flex flex-wrap items-center justify-center gap-3">
                                    <Link href="/login" className="px-6 py-2.5 rounded-xl bg-white text-indigo-800 font-bold text-sm hover:bg-indigo-50 transition">
                                        Masuk
                                    </Link>
                                    <Link href="/register" className="px-6 py-2.5 rounded-xl bg-violet-500 hover:bg-violet-400 text-white font-bold text-sm transition">
                                        Daftar Penulis
                                    </Link>
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {/* Related */}
                {related.length > 0 && (
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 pb-4">
                        <h2 className="text-xl font-extrabold text-slate-900 mb-5">Artikel Terkait</h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                            {related.map((item) => (
                                <Link
                                    key={item.id}
                                    href={`/articles/${item.slug}`}
                                    className="block bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-indigo-200 transition"
                                >
                                    <CategoryBadge value={item.category} categories={categories} />
                                    <h3 className="mt-2 font-bold text-slate-900 line-clamp-2">{item.title}</h3>
                                    <p className="mt-1 text-sm text-slate-500 line-clamp-2">{item.excerpt}</p>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </article>
        </PublicLayout>
    );
}
