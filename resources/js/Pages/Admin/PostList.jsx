import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminPostTable from '../../Components/AdminPostTable';

export default function PostList({ posts, categories, filters }) {
    return (
        <AppLayout>
            <Head title="Semua Artikel" />
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Semua Artikel</h1>
                        <p className="text-sm text-slate-600 mt-1">
                            Pemantauan seluruh artikel di sistem. <span className="font-semibold text-purple-800">Mode Lihat Saja</span> — admin tidak dapat mengubah artikel.
                        </p>
                    </div>
                    <a href="/admin/posts/export" className="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl self-start">
                        Export Semua ke Excel
                    </a>
                </div>
                <AdminPostTable posts={posts} categories={categories} filters={filters} baseUrl="/admin/posts" showAuthor />
            </div>
        </AppLayout>
    );
}
