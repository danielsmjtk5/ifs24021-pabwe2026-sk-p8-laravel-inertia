import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import CategoryBadge from '../../Components/CategoryBadge';
import MarkdownViewer from '../../Components/MarkdownViewer';

export default function PostShow({ post }) {
    const { auth } = usePage().props;

    return (
        <PublicLayout>
            <Head title={post.title}>
                {post.excerpt && <meta name="description" content={post.excerpt} />}
            </Head>

            <article className="max-w-3xl mx-auto px-4 sm:px-6 py-10">
                <Link href="/articles" className="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                    ← Kembali ke daftar artikel
                </Link>

                <header className="mt-6">
                    <div className="flex flex-wrap items-center gap-2 text-sm text-slate-600">
                        <CategoryBadge category={post.category} />
                        <span>{post.reading_minutes} menit baca</span>
                    </div>
                    <h1 className="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">{post.title}</h1>
                    {post.excerpt && <p className="mt-4 text-lg text-slate-600">{post.excerpt}</p>}
                    <p className="mt-5 text-sm text-slate-600">
                        Ditulis oleh <span className="font-semibold text-slate-900">{post.author.name}</span>
                        {post.author.username && <span> (@{post.author.username})</span>} · Dipublikasikan {post.published_at}
                    </p>
                </header>

                {post.cover_image_url && (
                    <img src={post.cover_image_url} alt={`Sampul artikel ${post.title}`} className="mt-8 w-full rounded-2xl object-cover max-h-[460px]" />
                )}

                <div className="mt-8 prose-article">
                    <MarkdownViewer content={post.content} />
                </div>

                {/* Community call-to-action */}
                <aside className="mt-14 rounded-2xl bg-indigo-50 border border-indigo-200 p-6 sm:p-8">
                    <h2 className="text-xl font-bold text-slate-900">Punya gagasan untuk dibagikan?</h2>
                    <p className="mt-2 text-sm text-slate-700">
                        Bergabunglah dengan komunitas penulis BlogVerse. Tulis artikel dengan editor Markdown, simpan sebagai draf, dan publikasikan kapan saja.
                    </p>
                    {auth?.user ? (
                        <Link
                            href={auth.user.role === 'admin' ? '/admin/users' : '/dashboard/posts'}
                            className="mt-5 inline-block px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition"
                        >
                            {auth.user.role === 'admin' ? 'Buka Panel Admin' : 'Buka Dashboard Penulis'}
                        </Link>
                    ) : (
                        <div className="mt-5 flex flex-wrap gap-3">
                            <Link href="/login" className="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition">
                                Masuk (Login)
                            </Link>
                            <Link
                                href="/register"
                                className="px-5 py-2.5 rounded-xl border border-indigo-300 bg-white text-indigo-800 text-sm font-bold hover:bg-indigo-100 transition"
                            >
                                Daftar (Register)
                            </Link>
                        </div>
                    )}
                </aside>
            </article>
        </PublicLayout>
    );
}
