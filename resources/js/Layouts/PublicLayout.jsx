import React, { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import ProcessingIndicator from '../Components/ProcessingIndicator';

/**
 * Public layout: sticky top navbar, full-width reading area and editorial footer.
 * No sidebar. Guests always see Login (Masuk) and Register (Daftar) links.
 */
export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [avatarError, setAvatarError] = useState(false);

    useEffect(() => setAvatarError(false), [user?.profile_photo_path]);

    const dashboardHref = user?.role === 'admin' ? '/admin/users' : '/dashboard/posts';
    const dashboardLabel = user?.role === 'admin' ? 'Panel Admin' : 'Dashboard Penulis';

    return (
        <div className="min-h-screen flex flex-col bg-white">
            <ProcessingIndicator />

            <header className="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
                    <Link href="/" className="flex items-center gap-2.5 shrink-0">
                        <span className="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-lg">B</span>
                        <span className="font-extrabold text-slate-900 text-lg tracking-tight">
                            BlogVerse <span className="text-indigo-700">Pro</span>
                        </span>
                    </Link>

                    <nav className="flex items-center gap-2 sm:gap-3" aria-label="Navigasi publik">
                        <Link href="/articles" className="hidden sm:inline-block px-3 py-2 text-sm font-semibold text-slate-700 hover:text-indigo-700 transition">
                            Artikel
                        </Link>

                        {user ? (
                            <Link
                                href={dashboardHref}
                                className="inline-flex items-center gap-2 pl-1.5 pr-3.5 py-1.5 rounded-full border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 transition"
                            >
                                <span className="w-7 h-7 rounded-full overflow-hidden bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">
                                    {user.profile_photo_path && !avatarError ? (
                                        <img
                                            src={`/storage/${user.profile_photo_path}`}
                                            alt={`Foto profil ${user.name}`}
                                            className="w-full h-full object-cover"
                                            onError={() => setAvatarError(true)}
                                        />
                                    ) : (
                                        user.name?.charAt(0).toUpperCase()
                                    )}
                                </span>
                                <span className="text-sm font-semibold text-slate-800">{dashboardLabel}</span>
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 rounded-xl transition"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Masuk</span>
                                </Link>
                                <Link
                                    href="/register"
                                    className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 rounded-xl shadow-sm transition"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span>Daftar</span>
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="bg-slate-900 text-slate-300">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-10 grid gap-6 sm:grid-cols-2 items-center">
                    <div>
                        <p className="font-extrabold text-white text-lg">BlogVerse Pro</p>
                        <p className="text-sm text-slate-300 mt-1">Platform publikasi artikel untuk penulis dan pembaca Indonesia.</p>
                    </div>
                    <nav className="flex flex-wrap sm:justify-end gap-x-6 gap-y-2 text-sm font-semibold" aria-label="Navigasi footer">
                        <Link href="/" className="hover:text-white transition">Beranda Publik</Link>
                        <Link href="/login" className="hover:text-white transition">Halaman Login</Link>
                        <Link href="/register" className="hover:text-white transition">Halaman Register</Link>
                    </nav>
                </div>
                <div className="border-t border-slate-800 py-4 text-center text-xs text-slate-400">
                    © {new Date().getFullYear()} BlogVerse Pro · Praktikum PABWE 2026
                </div>
            </footer>
        </div>
    );
}
