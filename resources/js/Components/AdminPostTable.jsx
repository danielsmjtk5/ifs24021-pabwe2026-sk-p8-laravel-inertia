import React, { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import CategoryBadge, { StatusBadge } from './CategoryBadge';
import Pagination from './Pagination';

/**
 * Read-only post table with search, status and category filters (admin pages).
 * There are no edit / delete buttons on purpose.
 */
export default function AdminPostTable({ posts, categories = [], filters = {}, baseUrl, showAuthor = false }) {
    const [search, setSearch] = useState(filters.search || '');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = setTimeout(() => applyFilters({ search }), 300);
        return () => clearTimeout(timer);
    }, [search]);

    const applyFilters = (changes) => router.get(baseUrl, { ...filters, ...changes }, { preserveState: true, replace: true });

    return (
        <div className="space-y-4">
            <div className="bg-white p-4 rounded-2xl border border-slate-200 flex flex-col sm:flex-row gap-3">
                <div className="flex-1">
                    <label htmlFor="admin-post-search" className="sr-only">Cari artikel</label>
                    <input id="admin-post-search" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari judul atau isi artikel..." className="w-full px-4 py-2 rounded-xl border border-slate-300 text-sm" />
                </div>
                <div className="sm:w-44">
                    <label htmlFor="admin-post-status" className="sr-only">Filter status</label>
                    <select id="admin-post-status" value={filters.status || ''} onChange={(e) => applyFilters({ status: e.target.value })} className="w-full py-2 px-3 rounded-xl border border-slate-300 text-sm">
                        <option value="">Semua Status</option>
                        <option value="published">Published</option>
                        <option value="draft">Draf</option>
                    </select>
                </div>
                <div className="sm:w-56">
                    <label htmlFor="admin-post-category" className="sr-only">Filter kategori</label>
                    <select id="admin-post-category" value={filters.category || ''} onChange={(e) => applyFilters({ category: e.target.value })} className="w-full py-2 px-3 rounded-xl border border-slate-300 text-sm">
                        <option value="">Semua Kategori</option>
                        {categories.map((cat) => (
                            <option key={cat.value} value={cat.value}>{cat.label}</option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th scope="col" className="px-5 py-3">Judul</th>
                            {showAuthor && <th scope="col" className="px-5 py-3">Penulis</th>}
                            <th scope="col" className="px-5 py-3">Kategori</th>
                            <th scope="col" className="px-5 py-3">Status</th>
                            <th scope="col" className="px-5 py-3">Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {posts.data.length === 0 && (
                            <tr>
                                <td colSpan={showAuthor ? 5 : 4} className="px-5 py-10 text-center text-slate-600">Belum ada artikel.</td>
                            </tr>
                        )}
                        {posts.data.map((post) => (
                            <tr key={post.id} className="hover:bg-slate-50">
                                <td className="px-5 py-3.5">
                                    {post.is_published ? (
                                        <a href={`/articles/${post.slug}`} target="_blank" rel="noreferrer" className="font-semibold text-slate-900 hover:text-indigo-700">
                                            {post.title}
                                        </a>
                                    ) : (
                                        <span className="font-semibold text-slate-900">{post.title}</span>
                                    )}
                                </td>
                                {showAuthor && <td className="px-5 py-3.5 text-slate-700">{post.user?.name} <span className="text-xs text-slate-600">@{post.user?.username}</span></td>}
                                <td className="px-5 py-3.5"><CategoryBadge category={post.category} /></td>
                                <td className="px-5 py-3.5"><StatusBadge published={post.is_published} /></td>
                                <td className="px-5 py-3.5 text-xs text-slate-600">{post.updated_at?.substring(0, 10)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination links={posts.links} />
        </div>
    );
}
