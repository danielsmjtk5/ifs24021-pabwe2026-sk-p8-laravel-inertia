import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../Components/Pagination';

export default function UserList({ users, filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = setTimeout(() => router.get('/admin/users', { search }, { preserveState: true, replace: true }), 300);
        return () => clearTimeout(timer);
    }, [search]);

    return (
        <AppLayout>
            <Head title="Kelola Pengguna" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Kelola Pengguna</h1>
                        <p className="text-sm text-slate-600 mt-1">Daftar akun terdaftar beserta jumlah artikel masing-masing penulis.</p>
                    </div>
                    <a href="/admin/posts/export" className="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl transition self-start">
                        Export Semua Artikel
                    </a>
                </div>

                <div className="bg-white p-4 rounded-2xl border border-slate-200">
                    <label htmlFor="user-search" className="sr-only">Cari pengguna</label>
                    <input
                        id="user-search"
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari nama, username, atau email..."
                        className="w-full px-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
                    />
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th scope="col" className="px-6 py-3.5">Pengguna</th>
                                <th scope="col" className="px-6 py-3.5">Role</th>
                                <th scope="col" className="px-6 py-3.5 text-center">Total Artikel</th>
                                <th scope="col" className="px-6 py-3.5 text-center">Published</th>
                                <th scope="col" className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {users.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-6 py-10 text-center text-slate-600">Tidak ada pengguna yang cocok.</td>
                                </tr>
                            )}
                            {users.data.map((user) => (
                                <tr key={user.id} className="hover:bg-slate-50">
                                    <td className="px-6 py-4">
                                        <p className="font-semibold text-slate-900">{user.name}</p>
                                        <p className="text-xs text-slate-600">@{user.username} · {user.email}</p>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-slate-200 text-slate-800'}`}>
                                            {user.role.toUpperCase()}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center font-bold text-slate-900">{user.posts_count ?? 0}</td>
                                    <td className="px-6 py-4 text-center font-bold text-emerald-700">{user.published_posts_count ?? 0}</td>
                                    <td className="px-6 py-4 text-right">
                                        <Link href={`/admin/users/${user.id}/posts`} className="inline-block px-3 py-1.5 text-xs font-semibold text-purple-800 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg">
                                            Lihat Artikel
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination links={users.links} />
            </div>
        </AppLayout>
    );
}
