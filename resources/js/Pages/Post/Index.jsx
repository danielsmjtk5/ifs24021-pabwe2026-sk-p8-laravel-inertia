import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import CategoryBadge, { StatusBadge } from '../../Components/CategoryBadge';
import Pagination from '../../Components/Pagination';
import PostFormModal from '../../Components/PostFormModal';
import Spinner from '../../Components/Spinner';

const AI_MIN = 1;
const AI_MAX = 5;

export default function PostIndex({ posts, stats = {}, categories = [], filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const firstRender = useRef(true);
    const [activeMenuId, setActiveMenuId] = useState(null);

    // Modals & action states
    const [formOpen, setFormOpen] = useState(false);
    const [editingPost, setEditingPost] = useState(null);
    const [deletingPost, setDeletingPost] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const importForm = useForm({ importFile: null });

    // AI generator
    const [aiOpen, setAiOpen] = useState(false);
    const [aiPrompt, setAiPrompt] = useState('');
    const [aiCount, setAiCount] = useState(1);
    const [aiGenerating, setAiGenerating] = useState(false);
    const [aiItems, setAiItems] = useState([]);
    const [aiSelected, setAiSelected] = useState([]);
    const [aiError, setAiError] = useState('');
    const [aiSaving, setAiSaving] = useState(false);

    useEffect(() => {
        const close = (e) => {
            if (!e.target.closest('.post-menu')) setActiveMenuId(null);
        };
        document.addEventListener('click', close);
        return () => document.removeEventListener('click', close);
    }, []);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = setTimeout(() => applyFilters({ search }), 300);
        return () => clearTimeout(timer);
    }, [search]);

    const applyFilters = (changes) => {
        router.get('/dashboard/posts', { ...filters, ...changes }, { preserveState: true, replace: true });
    };

    const openCreate = () => {
        setEditingPost(null);
        setFormOpen(true);
    };

    const openEdit = (post) => {
        setEditingPost(post);
        setFormOpen(true);
    };

    const togglePublish = (post) => {
        setTogglingId(post.id);
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, { preserveScroll: true, onFinish: () => setTogglingId(null) });
    };

    const executeDelete = () => {
        setIsDeleting(true);
        router.delete(`/dashboard/posts/${deletingPost.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingPost(null),
            onFinish: () => setIsDeleting(false),
        });
    };

    const submitImport = (e) => {
        e.preventDefault();
        importForm.post('/dashboard/posts/import', {
            forceFormData: true,
            onSuccess: () => {
                setImportOpen(false);
                importForm.reset();
            },
        });
    };

    const aiCountValid = Number.isInteger(Number(aiCount)) && Number(aiCount) >= AI_MIN && Number(aiCount) <= AI_MAX;

    const generateAi = async () => {
        if (aiPrompt.trim().length < 3 || !aiCountValid) return;
        setAiGenerating(true);
        setAiError('');
        try {
            const res = await fetch('/dashboard/posts/ai-generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ prompt: aiPrompt, count: Number(aiCount) }),
            });
            const data = await res.json();
            if (!res.ok || data.success === false) {
                const validation = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                setAiError(validation || data.message || 'Gagal membuat artikel dengan AI.');
            } else {
                setAiItems(data.items);
                setAiSelected(data.items.map((_, i) => i));
            }
        } catch (err) {
            setAiError(err.message || 'Server tidak merespons.');
        } finally {
            setAiGenerating(false);
        }
    };

    const toggleAiItem = (idx) =>
        setAiSelected((sel) => (sel.includes(idx) ? sel.filter((i) => i !== idx) : [...sel, idx]));

    const saveAi = () => {
        const items = aiSelected.map((i) => aiItems[i]);
        if (items.length === 0) return;
        setAiSaving(true);
        router.post(
            '/dashboard/posts/ai-save',
            { items },
            {
                onSuccess: () => {
                    setAiOpen(false);
                    setAiItems([]);
                    setAiSelected([]);
                    setAiPrompt('');
                },
                onFinish: () => setAiSaving(false),
            },
        );
    };

    return (
        <AppLayout>
            <Head title="Artikel Saya" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Artikel Saya</h1>
                    <p className="text-sm text-slate-600 mt-1">Tulis, kelola, dan publikasikan artikel Anda ke halaman publik.</p>
                </div>

                {/* Toolbar */}
                <div className="flex flex-wrap items-center gap-2.5">
                    <button type="button" onClick={openCreate} className="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
                        <span>Tulis Artikel</span>
                    </button>
                    <button type="button" onClick={() => setAiOpen(true)} className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 text-xs font-semibold rounded-xl transition">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        <span>AI Generator</span>
                    </button>
                    <a href="/dashboard/posts/export" className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-semibold rounded-xl transition">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <span>Export Excel</span>
                    </a>
                    <button type="button" onClick={() => setImportOpen(true)} className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 text-xs font-semibold rounded-xl transition">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                        <span>Import Excel</span>
                    </button>
                </div>

                {/* Statistic cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <StatCard label="Total Artikel" value={stats.total ?? 0} unit="tulisan" color="indigo" />
                    <StatCard label="Total Selesai" value={stats.published ?? 0} unit="dipublikasikan" color="emerald" />
                    <StatCard label="Belum Selesai" value={stats.draft ?? 0} unit="masih draf" color="amber" />
                </div>

                {/* Filters */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200 flex flex-col sm:flex-row gap-3">
                    <div className="flex-1">
                        <label htmlFor="post-search" className="sr-only">Cari artikel</label>
                        <input
                            id="post-search"
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari judul atau isi artikel..."
                            className="w-full px-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                    <div className="sm:w-44">
                        <label htmlFor="post-status-filter" className="sr-only">Filter status</label>
                        <select id="post-status-filter" value={filters.status || ''} onChange={(e) => applyFilters({ status: e.target.value })} className="w-full py-2 px-3 rounded-xl border border-slate-300 text-sm">
                            <option value="">Semua Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draf</option>
                        </select>
                    </div>
                    <div className="sm:w-56">
                        <label htmlFor="post-category-filter" className="sr-only">Filter kategori</label>
                        <select id="post-category-filter" value={filters.category || ''} onChange={(e) => applyFilters({ category: e.target.value })} className="w-full py-2 px-3 rounded-xl border border-slate-300 text-sm">
                            <option value="">Semua Kategori</option>
                            {categories.map((cat) => (
                                <option key={cat.value} value={cat.value}>{cat.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* List view (not cards, no description) */}
                {posts.data.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                        <h2 className="text-base font-bold text-slate-800">Belum ada artikel</h2>
                        <p className="text-sm text-slate-600 mt-1">Mulai tulis artikel baru atau gunakan AI Generator.</p>
                        <button type="button" onClick={openCreate} className="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition">
                            + Tulis Artikel
                        </button>
                    </div>
                ) : (
                    <ul className="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
                        {posts.data.map((post) => (
                            <li key={post.id} className="p-4 flex flex-col sm:flex-row sm:items-center gap-3 hover:bg-slate-50/80 transition">
                                <div className="min-w-0 flex-1 flex flex-wrap items-center gap-2">
                                    <Link href={`/dashboard/posts/${post.id}`} className="font-semibold text-sm text-slate-900 hover:text-indigo-700 truncate max-w-md">
                                        {post.title}
                                    </Link>
                                    <CategoryBadge category={post.category} />
                                    <StatusBadge published={post.is_published} />
                                    {post.cover_image_path && <span className="text-[11px] font-semibold px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded">📷 Sampul</span>}
                                    <span className="text-[11px] text-slate-600">{post.updated_at?.substring(0, 10)}</span>
                                </div>

                                <div className="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        onClick={() => togglePublish(post)}
                                        disabled={togglingId === post.id}
                                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition disabled:opacity-60 ${
                                            post.is_published
                                                ? 'border-amber-300 text-amber-800 bg-amber-50 hover:bg-amber-100'
                                                : 'border-emerald-300 text-emerald-800 bg-emerald-50 hover:bg-emerald-100'
                                        }`}
                                    >
                                        {togglingId === post.id && <Spinner className="w-3 h-3 text-current" />}
                                        <span>{post.is_published ? 'Jadikan Draf' : 'Publikasikan'}</span>
                                    </button>

                                    <div className="relative post-menu">
                                        <button
                                            type="button"
                                            onClick={() => setActiveMenuId(activeMenuId === post.id ? null : post.id)}
                                            className="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                                            aria-label={`Menu tindakan untuk ${post.title}`}
                                            aria-haspopup="menu"
                                            aria-expanded={activeMenuId === post.id}
                                        >
                                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8" />
                                                <circle cx="12" cy="12" r="1.8" />
                                                <circle cx="12" cy="19" r="1.8" />
                                            </svg>
                                        </button>

                                        {activeMenuId === post.id && (
                                            <div className="absolute right-0 mt-1 w-40 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-30" role="menu">
                                                <Link href={`/dashboard/posts/${post.id}`} role="menuitem" className="w-full px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 flex items-center gap-2">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>Detail</span>
                                                </Link>
                                                <button type="button" role="menuitem" onClick={() => { setActiveMenuId(null); openEdit(post); }} className="w-full px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 flex items-center gap-2 text-left">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    <span>Ubah Edit</span>
                                                </button>
                                                <div className="border-t border-slate-100 my-1"></div>
                                                <button type="button" role="menuitem" onClick={() => { setActiveMenuId(null); setDeletingPost(post); }} className="w-full px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 flex items-center gap-2 text-left">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={posts.links} />
            </div>

            <PostFormModal open={formOpen} post={editingPost} categories={categories} onClose={() => setFormOpen(false)} />

            {/* Delete confirmation */}
            {deletingPost && (
                <Dialog title="Hapus Artikel?" headerClass="bg-rose-700 text-white" footerClass="bg-rose-50 border-rose-100" onClose={() => setDeletingPost(null)}
                    footer={
                        <button type="button" onClick={executeDelete} disabled={isDeleting} className="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white text-xs font-semibold rounded-xl disabled:opacity-60 flex items-center gap-1.5">
                            {isDeleting && <Spinner />}
                            <span>{isDeleting ? 'Menghapus...' : 'Ya, Hapus'}</span>
                        </button>
                    }
                >
                    <p className="text-sm text-slate-700">Artikel “{deletingPost.title}” beserta foto sampulnya akan dihapus permanen.</p>
                </Dialog>
            )}

            {/* Import Excel */}
            {importOpen && (
                <Dialog title="Import Artikel dari Excel" headerClass="bg-slate-800 text-white" footerClass="bg-slate-100 border-slate-200" onClose={() => setImportOpen(false)}
                    footer={
                        <button type="submit" form="import-form" disabled={importForm.processing} className="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl disabled:opacity-60 flex items-center gap-1.5">
                            {importForm.processing && <Spinner />}
                            <span>{importForm.processing ? 'Mengimpor...' : 'Import Data'}</span>
                        </button>
                    }
                >
                    <form id="import-form" onSubmit={submitImport} className="space-y-3">
                        <label htmlFor="import-file" className="block text-xs font-semibold text-slate-700">File Excel (.xlsx, .xls, .csv)</label>
                        <input id="import-file" type="file" accept=".xlsx,.xls,.csv" required onChange={(e) => importForm.setData('importFile', e.target.files[0])} className="w-full text-xs text-slate-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-800" />
                        {importForm.errors.importFile && <p className="text-xs text-rose-700">{importForm.errors.importFile}</p>}
                        <p className="text-xs text-slate-600">Gunakan format yang sama dengan file hasil Export: kolom Judul, Kategori, Ringkasan, Konten, Status.</p>
                    </form>
                </Dialog>
            )}

            {/* AI Generator */}
            {aiOpen && (
                <Dialog title="Rancang Artikel dengan OpenAI" headerClass="bg-purple-800 text-white" footerClass="bg-purple-50 border-purple-100" wide onClose={() => setAiOpen(false)}
                    footer={
                        aiItems.length > 0 && (
                            <button type="button" onClick={saveAi} disabled={aiSaving || aiSelected.length === 0} className="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl disabled:opacity-60 flex items-center gap-1.5">
                                {aiSaving && <Spinner />}
                                <span>{aiSaving ? 'Menyimpan...' : `Simpan sebagai Draf (${aiSelected.length})`}</span>
                            </button>
                        )
                    }
                >
                    <div className="space-y-3">
                        <div>
                            <label htmlFor="ai-prompt" className="block text-xs font-semibold text-slate-700 mb-1">Topik / ide artikel</label>
                            <textarea id="ai-prompt" rows={3} value={aiPrompt} onChange={(e) => setAiPrompt(e.target.value)} placeholder="Contoh: Tips mengatur waktu kuliah sambil bekerja paruh waktu" className="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500" />
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            <label htmlFor="ai-count" className="text-xs font-semibold text-slate-700">Jumlah artikel</label>
                            <input
                                id="ai-count"
                                type="number"
                                min={AI_MIN}
                                max={AI_MAX}
                                step="1"
                                value={aiCount}
                                onChange={(e) => setAiCount(e.target.value)}
                                aria-describedby="ai-count-help"
                                className={`w-20 px-3 py-1.5 rounded-xl border text-sm text-center font-semibold ${aiCountValid ? 'border-slate-300' : 'border-rose-500 bg-rose-50'}`}
                            />
                            <span id="ai-count-help" className={`text-xs ${aiCountValid ? 'text-slate-600' : 'text-rose-700 font-semibold'}`}>
                                antara {AI_MIN} sampai {AI_MAX} artikel
                            </span>
                            <button type="button" onClick={generateAi} disabled={aiGenerating || aiPrompt.trim().length < 3 || !aiCountValid} className="ml-auto px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold rounded-xl disabled:opacity-60 flex items-center gap-1.5">
                                {aiGenerating && <Spinner />}
                                <span>{aiGenerating ? 'Sedang Merancang...' : 'Generate Artikel'}</span>
                            </button>
                        </div>

                        {aiError && <div className="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl" role="alert">{aiError}</div>}

                        {aiItems.length > 0 && (
                            <div className="pt-3 border-t border-slate-100 space-y-2 max-h-72 overflow-y-auto">
                                <p className="text-xs font-bold text-slate-700">Pilih artikel yang ingin disimpan ({aiSelected.length}/{aiItems.length}):</p>
                                {aiItems.map((item, idx) => (
                                    <label key={idx} htmlFor={`ai-item-${idx}`} className={`p-3 rounded-xl border flex items-start gap-2.5 cursor-pointer ${aiSelected.includes(idx) ? 'border-purple-500 bg-purple-50' : 'border-slate-200'}`}>
                                        <input id={`ai-item-${idx}`} type="checkbox" checked={aiSelected.includes(idx)} onChange={() => toggleAiItem(idx)} className="mt-0.5 rounded text-purple-700" />
                                        <span className="min-w-0">
                                            <span className="block font-bold text-sm text-slate-800">{item.title}</span>
                                            <span className="mt-1 inline-block"><CategoryBadge category={item.category} /></span>
                                            {item.excerpt && <span className="block mt-1 text-xs text-slate-600">{item.excerpt}</span>}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        )}
                    </div>
                </Dialog>
            )}
        </AppLayout>
    );
}

function StatCard({ label, value, unit, color }) {
    const colors = {
        indigo: 'bg-indigo-50 text-indigo-700 border-indigo-100',
        emerald: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        amber: 'bg-amber-50 text-amber-800 border-amber-100',
    };
    return (
        <div className="bg-white p-4 rounded-2xl border border-slate-200 flex items-center gap-3.5">
            <div className={`w-11 h-11 rounded-xl flex items-center justify-center font-black border ${colors[color]}`} aria-hidden="true">#</div>
            <div>
                <span className="text-[11px] font-bold text-slate-600 uppercase tracking-wider block">{label}</span>
                <span className="text-2xl font-black text-slate-900">{value}</span> <span className="text-xs text-slate-600">{unit}</span>
            </div>
        </div>
    );
}

/**
 * Small centred dialog with distinct coloured header and footer; actions sit on the right.
 */
function Dialog({ title, headerClass, footerClass, onClose, footer, wide = false, children }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" role="dialog" aria-modal="true" aria-label={title}>
            <div className={`bg-white rounded-2xl w-full shadow-2xl overflow-hidden ${wide ? 'max-w-2xl' : 'max-w-md'}`}>
                <div className={`px-6 py-4 flex items-center justify-between ${headerClass}`}>
                    <h2 className="text-base font-bold">{title}</h2>
                    <button type="button" onClick={onClose} className="p-1 rounded-lg hover:bg-black/10" aria-label="Tutup">✕</button>
                </div>
                <div className="p-6">{children}</div>
                <div className={`px-6 py-3.5 border-t flex items-center justify-end gap-2 ${footerClass}`}>
                    <button type="button" onClick={onClose} className="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/70 rounded-xl">
                        Batal
                    </button>
                    {footer}
                </div>
            </div>
        </div>
    );
}
