import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import MarkdownEditor from './MarkdownEditor';
import Spinner from './Spinner';

/**
 * Full-screen modal for creating (post = null) or editing an article.
 * Create uses an indigo header, edit uses an amber header; the footer is a contrasting slate
 * bar with Batal & Simpan aligned to the right.
 */
export default function PostFormModal({ open, post = null, categories = [], onClose }) {
    const isEdit = Boolean(post);
    const form = useForm({
        title: '',
        category: 'Umum',
        excerpt: '',
        content: '',
        is_published: false,
    });

    useEffect(() => {
        if (!open) return;
        form.clearErrors();
        form.setData({
            title: post?.title ?? '',
            category: post?.category ?? 'Umum',
            excerpt: post?.excerpt ?? '',
            content: post?.content ?? '',
            is_published: Boolean(post?.is_published),
        });
    }, [open, post?.id]);

    if (!open) return null;

    const submit = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => onClose() };
        if (isEdit) {
            form.put(`/dashboard/posts/${post.id}`, options);
        } else {
            form.post('/dashboard/posts', options);
        }
    };

    const headerClass = isEdit ? 'bg-amber-500 text-slate-900' : 'bg-indigo-700 text-white';

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-white" role="dialog" aria-modal="true" aria-labelledby="post-form-title">
            <div className={`px-6 md:px-8 py-4 flex items-center justify-between shrink-0 ${headerClass}`}>
                <div>
                    <h2 id="post-form-title" className="text-base font-bold leading-tight">
                        {isEdit ? 'Ubah Artikel' : 'Tulis Artikel Baru'}
                    </h2>
                    <p className="text-xs opacity-90">Lengkapi judul, kategori, ringkasan, dan isi artikel dalam format Markdown.</p>
                </div>
                <button type="button" onClick={onClose} className="p-2 rounded-xl hover:bg-black/10 transition" aria-label="Tutup formulir">
                    ✕
                </button>
            </div>

            <form onSubmit={submit} className="flex-1 flex flex-col overflow-hidden">
                <div className="flex-1 overflow-y-auto p-6 md:p-8 space-y-5">
                    <div>
                        <label htmlFor="post-title" className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Judul Artikel *
                        </label>
                        <input
                            id="post-title"
                            type="text"
                            value={form.data.title}
                            onChange={(e) => form.setData('title', e.target.value)}
                            required
                            maxLength={255}
                            className="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            placeholder="Contoh: Belajar Laravel Inertia dari Nol"
                        />
                        {form.errors.title && <p className="mt-1 text-xs text-rose-700">{form.errors.title}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="post-category" className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Kategori *
                            </label>
                            <select
                                id="post-category"
                                value={form.data.category}
                                onChange={(e) => form.setData('category', e.target.value)}
                                className="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            >
                                {categories.map((cat) => (
                                    <option key={cat.value} value={cat.value}>
                                        {cat.label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.category && <p className="mt-1 text-xs text-rose-700">{form.errors.category}</p>}
                        </div>

                        <div className="flex items-end">
                            <label htmlFor="post-published" className="flex items-center gap-3 p-3 w-full rounded-xl border border-slate-300 cursor-pointer">
                                <input
                                    id="post-published"
                                    type="checkbox"
                                    checked={form.data.is_published}
                                    onChange={(e) => form.setData('is_published', e.target.checked)}
                                    className="w-4 h-4 rounded text-indigo-600"
                                />
                                <span className="text-sm text-slate-800">
                                    <span className="font-semibold">Publikasikan sekarang</span> — biarkan kosong untuk menyimpan sebagai draf
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label htmlFor="post-excerpt" className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Ringkasan (1–2 kalimat)
                        </label>
                        <textarea
                            id="post-excerpt"
                            rows={2}
                            maxLength={500}
                            value={form.data.excerpt}
                            onChange={(e) => form.setData('excerpt', e.target.value)}
                            className="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            placeholder="Kosongkan untuk dibuat otomatis dari isi artikel"
                        />
                        {form.errors.excerpt && <p className="mt-1 text-xs text-rose-700">{form.errors.excerpt}</p>}
                    </div>

                    <div>
                        <span className="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Isi Artikel * (TOAST UI Markdown Editor)</span>
                        <MarkdownEditor
                            key={isEdit ? `edit-${post.id}` : 'create'}
                            value={form.data.content}
                            onChange={(val) => form.setData('content', val)}
                            height="460px"
                            placeholder="Tulis isi artikel dalam format Markdown..."
                        />
                        {form.errors.content && <p className="mt-1 text-xs text-rose-700">{form.errors.content}</p>}
                    </div>
                </div>

                <div className="px-6 md:px-8 py-4 bg-slate-800 flex items-center justify-end gap-3 shrink-0">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-5 py-2.5 text-xs font-semibold text-slate-100 hover:bg-slate-700 rounded-xl transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl disabled:opacity-60 flex items-center gap-2 transition"
                    >
                        {form.processing && <Spinner />}
                        <span>{form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan Artikel'}</span>
                    </button>
                </div>
            </form>
        </div>
    );
}
