import React from 'react';
import { Link } from '@inertiajs/react';

/**
 * Renders Laravel paginator links. Labels are cleaned instead of using dangerouslySetInnerHTML.
 */
export default function Pagination({ links = [] }) {
    if (links.length <= 3) return null;

    const cleanLabel = (label) =>
        label.replace('&laquo; Previous', '‹ Sebelumnya').replace('Next &raquo;', 'Berikutnya ›').replace(/&[a-z]+;/g, '');

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1 pt-4" aria-label="Navigasi halaman">
            {links.map((link, idx) =>
                link.url ? (
                    <Link
                        key={idx}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition ${
                            link.active
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                        }`}
                    >
                        {cleanLabel(link.label)}
                    </Link>
                ) : (
                    <span key={idx} className="px-3 py-1.5 text-xs font-semibold rounded-lg border bg-slate-50 text-slate-500 border-slate-200">
                        {cleanLabel(link.label)}
                    </span>
                ),
            )}
        </nav>
    );
}
