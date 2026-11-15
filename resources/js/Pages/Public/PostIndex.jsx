import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import CategoryBadge from '../../Components/CategoryBadge';
import Pagination from '../../Components/Pagination';

export default function PostIndex({ posts, categories = [], filters = {}, isHome = false }) {
    const { auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const firstRender = useRef(true);

    // Debounced search (300 ms) so we do not send a request on every key press
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = setTimeout(() => applyFilters({ search }), 300);
        return () => clearTimeout(timer);
    }, [search]);

    const applyFilters = (changes) => {
        router.get('/articles', { search: filters.search, category: filters.category, ...changes }, { preserveState: true, replace: true });
    };

    return (
        <PublicLayout>
            <Head title={isHome ? 'Beranda' : 'Artikel'} />

            {/* Hero banner */}
            <section className="bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-900 text-white">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
                    <p className="text-xs font-bold uppercase tracking-[0.2em] text-indigo-200">BlogVerse Pro</p>
                    <h1 className="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight max-w-3xl leading-tight">
                        Baca, tulis, dan bagikan gagasan terbaik Anda.
                    </h1>
                    <p className="mt-4 text-base sm:text-lg text-indigo-100 max-w-2xl">
                        Kumpulan artikel teknologi, pendidikan, gaya hidup, bisnis, dan opini dari komunitas penulis. Semua artikel bisa dibaca
                        tanpa login.
                    </p>
                    {!auth?.user ? (
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href="/login" className="px-5 py-3 rounded-xl bg-white text-indigo-800 font-bold text-sm hover:bg-indigo-50 transition">
                                Masuk ke Akun
                            </Link>
                            <Link
                                href="/register"
                                className="px-5 py-3 rounded-xl border border-white/40 text-white font-bold text-sm hover:bg-white/10 transition"
                            >
                                Daftar sebagai Penulis
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-8">
                            <Link
                                href={auth.user.role === 'admin' ? '/admin/users' : '/dashboard/posts'}
                                className="px-5 py-3 rounded-xl bg-white text-indigo-800 font-bold text-sm hover:bg-indigo-50 transition"
                            >
                                {auth.user.role === 'admin' ? 'Buka Panel Admin' : 'Tulis Artikel Baru'}
                            </Link>
                        </div>
                    )}
                </div>
            </section>

            <section className="max-w-6xl mx-auto px-4 sm:px-6 py-10">
                {/* Search + category filter */}
                <div className="flex flex-col md:flex-row md:items-center gap-4 justify-between">
                    <h2 className="text-2xl font-bold text-slate-900">Artikel Terbaru</h2>
                    <div className="relative md:w-80">
                        <label htmlFor="public-search" className="sr-only">Cari artikel</label>
                        <input
                            id="public-search"
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari judul atau isi artikel..."
                            className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <svg className="w-4 h-4 text-slate-500 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <div className="mt-5 flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
                    <button
                        type="button"
                        onClick={() => applyFilters({ category: '' })}
                        aria-pressed={!filters.category}
                        className={`px-3.5 py-1.5 rounded-full text-xs font-semibold border transition ${
                            !filters.category ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'
                        }`}
                    >
                        Semua Kategori
                    </button>
                    {categories.map((cat) => (
                        <button
                            key={cat.value}
                            type="button"
                            onClick={() => applyFilters({ category: cat.value })}
                            aria-pressed={filters.category === cat.value}
                            className={`px-3.5 py-1.5 rounded-full text-xs font-semibold border transition ${
                                filters.category === cat.value
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'
                            }`}
                        >
                            {cat.label}
                        </button>
                    ))}
                </div>

                {posts.data.length === 0 ? (
                    <div className="mt-10 text-center py-16 rounded-2xl border border-dashed border-slate-300">
                        <p className="font-semibold text-slate-800">Belum ada artikel yang cocok.</p>
                        <p className="text-sm text-slate-600 mt-1">Coba kata kunci atau kategori lain.</p>
                    </div>
                ) : (
                    <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((post) => (
                            <article key={post.id} className="group rounded-2xl border border-slate-200 overflow-hidden bg-white hover:shadow-lg transition flex flex-col">
                                <Link href={`/articles/${post.slug}`} aria-label={`Baca artikel: ${post.title}`} className="block aspect-[16/9] bg-gradient-to-br from-indigo-100 to-slate-100 overflow-hidden">
                                    {post.cover_image_url ? (
                                        <img
                                            src={post.cover_image_url}
                                            alt={`Sampul artikel ${post.title}`}
                                            loading="lazy"
                                            className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                        />
                                    ) : (
                                        <span className="w-full h-full flex items-center justify-center text-4xl font-black text-indigo-300" aria-hidden="true">
                                            {post.title.charAt(0)}
                                        </span>
                                    )}
                                </Link>
                                <div className="p-5 flex flex-col flex-1">
                                    <div className="flex items-center gap-2 text-xs text-slate-600">
                                        <CategoryBadge category={post.category} />
                                        <span>{post.reading_minutes} menit baca</span>
                                    </div>
                                    <h3 className="mt-3 text-lg font-bold text-slate-900 leading-snug group-hover:text-indigo-700 transition">
                                        <Link href={`/articles/${post.slug}`}>{post.title}</Link>
                                    </h3>
                                    {post.excerpt && <p className="mt-2 text-sm text-slate-600 line-clamp-3">{post.excerpt}</p>}
                                    <p className="mt-auto pt-4 text-xs text-slate-600">
                                        Oleh <span className="font-semibold text-slate-800">{post.author.name}</span> · {post.published_at}
                                    </p>
                                </div>
                            </article>
                        ))}
                    </div>
                )}

                <Pagination links={posts.links} />
            </section>
        </PublicLayout>
    );
}
