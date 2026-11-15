import React from 'react';

const CLASSES = {
    Teknologi: 'bg-blue-100 text-blue-800',
    Pendidikan: 'bg-emerald-100 text-emerald-800',
    'Gaya Hidup': 'bg-pink-100 text-pink-800',
    Bisnis: 'bg-amber-100 text-amber-800',
    Opini: 'bg-purple-100 text-purple-800',
    Umum: 'bg-slate-200 text-slate-800',
};

export default function CategoryBadge({ category }) {
    return (
        <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${CLASSES[category] || CLASSES.Umum}`}>
            {category}
        </span>
    );
}

export function StatusBadge({ published }) {
    return published ? (
        <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Published</span>
    ) : (
        <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Draf</span>
    );
}
