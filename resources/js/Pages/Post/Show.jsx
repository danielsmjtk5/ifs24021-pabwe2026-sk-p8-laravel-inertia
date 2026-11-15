import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import CategoryBadge, { StatusBadge } from '../../Components/CategoryBadge';
import MarkdownViewer from '../../Components/MarkdownViewer';
import PostFormModal from '../../Components/PostFormModal';
import Spinner from '../../Components/Spinner';

export default function PostShow({ post, categories = [] }) {
    const [formOpen, setFormOpen] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [busy, setBusy] = useState('');
    const coverForm = useForm({ coverPhoto: null });

    const togglePublish = () => {
        setBusy('publish');
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, { preserveScroll: true, onFinish: () => setBusy('') });
    };

    const uploadCover = (e) => {
        e.preventDefault();
        coverForm.post(`/dashboard/posts/${post.id}/cover`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => coverForm.reset(),
        });
    };

    const deleteCover = () => {
        setBusy('cover');
        router.delete(`/dashboard/posts/${post.id}/cover`, { preserveScroll: true, onFinish: () => setBusy('') });
    };

    const deletePost = () => {
        setBusy('delete');
        router.delete(`/dashboard/posts/${post.id}`, { onFinish: () => setBusy('') });
    };

    return (
        <AppLayout>
            <Head title={post.title} />

            <div className="max-w-5xl space-y-6">
                <Link href="/dashboard/posts" className="text-sm font-semibold text-indigo-700 hover:text-indigo-900">← Kembali ke Artikel Saya</Link>

                <div className="bg-white rounded-2xl border border-slate-200 p-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <CategoryBadge category={post.category} />
                        <StatusBadge published={post.is_published} />
                        <span className="text-xs text-slate-600">{post.reading_minutes} menit baca</span>
                    </div>
                    <h1 className="mt-3 text-2xl sm:text-3xl font-extrabold text-slate-900">{post.title}</h1>
                    {post.excerpt && <p className="mt-2 text-slate-600">{post.excerpt}</p>}
                    <dl className="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-slate-600">
                        <div><dt className="font-semibold text-slate-800">Dibuat</dt><dd>{post.created_at}</dd></div>
                        <div><dt className="font-semibold text-slate-800">Diperbarui</dt><dd>{post.updated_at}</dd></div>
                        <div><dt className="font-semibold text-slate-800">Dipublikasikan</dt><dd>{post.published_at || '— (masih draf)'}</dd></div>
                    </dl>

                    <div className="mt-5 flex flex-wrap gap-2">
                        <button type="button" onClick={() => setFormOpen(true)} className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl">Ubah Artikel</button>
                        <button type="button" onClick={togglePublish} disabled={busy === 'publish'} className="px-4 py-2 border border-slate-300 hover:bg-slate-100 text-slate-800 text-xs font-semibold rounded-xl flex items-center gap-1.5 disabled:opacity-60">
                            {busy === 'publish' && <Spinner className="w-3.5 h-3.5 text-slate-700" />}
                            <span>{post.is_published ? 'Jadikan Draf' : 'Publikasikan'}</span>
                        </button>
                        {post.is_published && (
                            <a href={`/articles/${post.slug}`} target="_blank" rel="noreferrer" className="px-4 py-2 border border-emerald-300 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl hover:bg-emerald-100">
                                Lihat di Halaman Publik ↗
                            </a>
                        )}
                        <button type="button" onClick={() => setConfirmDelete(true)} className="px-4 py-2 border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-semibold rounded-xl ml-auto">Hapus</button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
                        <h2 className="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4">Isi Artikel</h2>
                        <MarkdownViewer content={post.content} />
                    </div>

                    <div className="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 h-fit">
                        <h2 className="text-sm font-bold uppercase tracking-wider text-slate-700">Foto Sampul</h2>
                        {post.cover_image_url ? (
                            <img src={post.cover_image_url} alt={`Sampul artikel ${post.title}`} className="w-full rounded-xl object-cover aspect-video" />
                        ) : (
                            <div className="aspect-video rounded-xl bg-slate-100 flex items-center justify-center text-xs text-slate-600">Belum ada sampul</div>
                        )}
                        <form onSubmit={uploadCover} className="space-y-2">
                            <label htmlFor="cover-photo" className="block text-xs font-semibold text-slate-700">Unggah gambar (maks. 5 MB)</label>
                            <input id="cover-photo" type="file" accept="image/*" required onChange={(e) => coverForm.setData('coverPhoto', e.target.files[0])} className="w-full text-xs text-slate-700 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-800" />
                            {coverForm.errors.coverPhoto && <p className="text-xs text-rose-700">{coverForm.errors.coverPhoto}</p>}
                            <button type="submit" disabled={coverForm.processing} className="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl flex items-center justify-center gap-1.5 disabled:opacity-60">
                                {coverForm.processing && <Spinner />}
                                <span>{coverForm.processing ? 'Mengunggah...' : 'Simpan Sampul'}</span>
                            </button>
                        </form>
                        {post.cover_image_url && (
                            <button type="button" onClick={deleteCover} disabled={busy === 'cover'} className="w-full px-4 py-2 border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-semibold rounded-xl flex items-center justify-center gap-1.5 disabled:opacity-60">
                                {busy === 'cover' && <Spinner className="w-3.5 h-3.5 text-rose-700" />}
                                <span>Hapus Sampul</span>
                            </button>
                        )}
                    </div>
                </div>
            </div>

            <PostFormModal open={formOpen} post={post} categories={categories} onClose={() => setFormOpen(false)} />

            {confirmDelete && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" role="dialog" aria-modal="true" aria-label="Konfirmasi hapus artikel">
                    <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
                        <div className="px-6 py-4 bg-rose-700 text-white"><h2 className="text-base font-bold">Hapus Artikel?</h2></div>
                        <p className="p-6 text-sm text-slate-700">Artikel “{post.title}” akan dihapus permanen.</p>
                        <div className="px-6 py-3.5 bg-rose-50 border-t border-rose-100 flex justify-end gap-2">
                            <button type="button" onClick={() => setConfirmDelete(false)} className="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/70 rounded-xl">Batal</button>
                            <button type="button" onClick={deletePost} disabled={busy === 'delete'} className="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white text-xs font-semibold rounded-xl flex items-center gap-1.5 disabled:opacity-60">
                                {busy === 'delete' && <Spinner />}
                                <span>{busy === 'delete' ? 'Menghapus...' : 'Ya, Hapus'}</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
