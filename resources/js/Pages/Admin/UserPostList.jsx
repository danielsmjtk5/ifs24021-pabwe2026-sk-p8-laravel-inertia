import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminPostTable from '../../Components/AdminPostTable';

export default function UserPostList({ targetUser, posts, categories, filters }) {
    return (
        <AppLayout>
            <Head title={`Artikel ${targetUser.name}`} />
            <div className="space-y-6">
                <Link href="/admin/users" className="text-sm font-semibold text-purple-800 hover:text-purple-950">← Kembali ke Kelola Pengguna</Link>
                <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Artikel milik {targetUser.name}</h1>
                        <p className="text-sm text-slate-600 mt-1">
                            @{targetUser.username} · {targetUser.email} · <span className="font-semibold text-purple-800">Mode Lihat Saja</span>
                        </p>
                    </div>
                    <a href={`/admin/users/${targetUser.id}/posts/export`} className="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl self-start">
                        Export Artikel Pengguna Ini
                    </a>
                </div>
                <AdminPostTable posts={posts} categories={categories} filters={filters} baseUrl={`/admin/users/${targetUser.id}/posts`} />
            </div>
        </AppLayout>
    );
}
