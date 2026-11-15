import React, { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import ProcessingIndicator from '../Components/ProcessingIndicator';

/**
 * Dashboard layout (with sidebar) for logged-in writers (member) and admins.
 * Public pages use PublicLayout instead (no sidebar).
 */
export default function AppLayout({ children }) {
    const { auth, flash } = usePage().props;
    const url = usePage().url;
    const user = auth?.user;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [avatarError, setAvatarError] = useState(false);

    useEffect(() => setAvatarError(false), [user?.profile_photo_path]);

    const handleLogout = (e) => {
        e.preventDefault();
        router.post('/logout');
    };

    const navClass = (prefix, admin = false) =>
        `flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition ${
            url.startsWith(prefix)
                ? admin
                    ? 'bg-purple-50 text-purple-700 font-semibold'
                    : 'bg-indigo-50 text-indigo-700 font-semibold'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
        }`;

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col md:flex-row">
            <ProcessingIndicator />

            {/* Mobile header */}
            <header className="md:hidden bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between sticky top-0 z-30">
                <Link href="/" className="flex items-center gap-2">
                    <span className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">B</span>
                    <span className="font-bold text-slate-800 text-lg">BlogVerse</span>
                </Link>
                <button
                    type="button"
                    onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    className="p-2 text-slate-600 hover:text-slate-900 rounded-lg"
                    aria-label="Buka atau tutup navigasi"
                    aria-expanded={mobileMenuOpen}
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        {mobileMenuOpen ? (
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        ) : (
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        )}
                    </svg>
                </button>
            </header>

            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 md:sticky md:top-0 md:h-screen md:translate-x-0 ${
                    mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="p-6 border-b border-slate-100">
                    <Link href="/" className="flex items-center gap-3">
                        <span className="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-lg">B</span>
                        <span>
                            <span className="font-bold text-slate-900 text-lg tracking-tight block">BlogVerse Pro</span>
                            <span className="text-[11px] font-semibold uppercase tracking-wider text-indigo-700">
                                {user?.role === 'admin' ? 'Panel Admin' : 'Dashboard Penulis'}
                            </span>
                        </span>
                    </Link>
                </div>

                {user && (
                    <div className="p-4 mx-4 my-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full overflow-hidden bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0">
                            {user.profile_photo_path && !avatarError ? (
                                <img
                                    src={`/storage/${user.profile_photo_path}`}
                                    alt={`Foto profil ${user.name}`}
                                    className="w-full h-full object-cover"
                                    onError={() => setAvatarError(true)}
                                />
                            ) : (
                                <span>{user.name?.charAt(0).toUpperCase()}</span>
                            )}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-semibold text-slate-800 truncate">{user.name}</p>
                            <p className="text-xs text-slate-600 truncate">@{user.username}</p>
                            <span
                                className={`inline-block text-[10px] font-bold px-2 py-0.5 mt-1 rounded-full ${
                                    user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-slate-200 text-slate-800'
                                }`}
                            >
                                {user.role?.toUpperCase()}
                            </span>
                        </div>
                    </div>
                )}

                <nav className="flex-1 px-4 space-y-1.5 overflow-y-auto" aria-label="Navigasi utama">
                    {user?.role === 'member' && (
                        <Link href="/dashboard/posts" className={navClass('/dashboard/posts')}>
                            <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Artikel Saya</span>
                        </Link>
                    )}

                    {user?.role === 'admin' && (
                        <>
                            <span className="px-3 pt-1 text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Admin Area</span>
                            <Link href="/admin/users" className={navClass('/admin/users', true)}>
                                <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Kelola Pengguna</span>
                            </Link>
                            <Link href="/admin/posts" className={navClass('/admin/posts', true)}>
                                <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                                <span>Semua Artikel</span>
                            </Link>
                        </>
                    )}

                    <div className="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                        <Link href="/" className={navClass('/articles')}>
                            <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Beranda Blog Publik</span>
                        </Link>
                        <Link href="/profile" className={navClass('/profile')}>
                            <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Profil Saya</span>
                        </Link>
                    </div>
                </nav>

                <div className="p-4 border-t border-slate-100">
                    <button
                        type="button"
                        onClick={handleLogout}
                        className="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-rose-700 hover:bg-rose-50 transition"
                    >
                        <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Keluar (Logout)</span>
                    </button>
                </div>
            </aside>

            {mobileMenuOpen && <div onClick={() => setMobileMenuOpen(false)} className="fixed inset-0 bg-slate-900/40 z-30 md:hidden" />}

            <main className="flex-1 flex flex-col min-w-0">
                {flash?.success && (
                    <div className="m-4 mb-0 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm" role="status">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="m-4 mb-0 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm" role="alert">
                        {flash.error}
                    </div>
                )}
                <div className="p-4 sm:p-6 lg:p-8 flex-1">{children}</div>
            </main>
        </div>
    );
}
