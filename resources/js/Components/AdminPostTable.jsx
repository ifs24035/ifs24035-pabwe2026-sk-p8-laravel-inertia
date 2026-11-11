import React from 'react';
import CategoryBadge from './CategoryBadge';
import Avatar from './Avatar';
import Pagination from './Pagination';

/**
 * Read-only table of posts used by the admin monitoring pages.
 */
export default function AdminPostTable({ posts, categories = [], showAuthor = true }) {
    return (
        <>
            <div className="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-semibold">
                            <tr>
                                <th className="px-6 py-3.5">Artikel</th>
                                {showAuthor && <th className="px-6 py-3.5">Penulis</th>}
                                <th className="px-6 py-3.5">Kategori</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5">Dibuat</th>
                                <th className="px-6 py-3.5 text-right">Publik</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {posts.data.length === 0 && (
                                <tr>
                                    <td colSpan={showAuthor ? 6 : 5} className="px-6 py-10 text-center text-slate-400">
                                        Tidak ada artikel ditemukan.
                                    </td>
                                </tr>
                            )}
                            {posts.data.map((post) => (
                                <tr key={post.id} className="hover:bg-slate-50/60 transition">
                                    <td className="px-6 py-4 max-w-xs">
                                        <p className="font-bold text-slate-900 truncate">{post.title}</p>
                                        <p className="text-slate-400 truncate">/articles/{post.slug}</p>
                                    </td>
                                    {showAuthor && (
                                        <td className="px-6 py-4">
                                            <div className="flex items-center space-x-2">
                                                <Avatar name={post.user?.name} path={post.user?.profile_photo_path} className="w-7 h-7 text-[11px]" />
                                                <div>
                                                    <p className="font-semibold text-slate-800">{post.user?.name}</p>
                                                    <p className="text-slate-400">@{post.user?.username}</p>
                                                </div>
                                            </div>
                                        </td>
                                    )}
                                    <td className="px-6 py-4">
                                        <CategoryBadge value={post.category} categories={categories} />
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`inline-block px-2.5 py-0.5 rounded-full font-bold text-[10px] ${
                                            post.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                                        }`}>
                                            {post.is_published ? 'Dipublikasikan' : 'Draf'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-slate-500">
                                        {post.created_at ? new Date(post.created_at).toLocaleDateString('id-ID') : '-'}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        {post.is_published ? (
                                            <a
                                                href={`/articles/${post.slug}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-block px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold rounded-lg transition"
                                            >
                                                Buka
                                            </a>
                                        ) : (
                                            <span className="text-slate-300">-</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
            <Pagination links={posts.links} activeClass="bg-purple-600 text-white border-purple-600" />
        </>
    );
}
