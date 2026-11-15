import React, { useEffect, useRef } from 'react';
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

export default function MarkdownEditor({
    value = '',
    onChange,
    height = '300px',
    placeholder = 'Tuliskan deskripsi / catatan dalam format Markdown...',
}) {
    const editorElRef = useRef(null);
    const editorInstanceRef = useRef(null);

    useEffect(() => {
        if (!editorElRef.current) return;
        // TOAST UI renders into its own child node that React does not manage;
        // this avoids "removeChild" errors when the component unmounts.
        const mountNode = document.createElement('div');
        editorElRef.current.appendChild(mountNode);

        editorInstanceRef.current = new Editor({
            el: mountNode,
            height: height,
            initialEditType: 'markdown',
            previewStyle: 'tab',
            initialValue: value || '',
            placeholder: placeholder,
            usageStatistics: false,
            events: {
                change: () => {
                    if (editorInstanceRef.current && onChange) {
                        const md = editorInstanceRef.current.getMarkdown();
                        onChange(md);
                    }
                },
            },
        });

        return () => {
            if (editorInstanceRef.current) {
                editorInstanceRef.current.destroy();
                editorInstanceRef.current = null;
            }
            mountNode.remove();
        };
    }, []);

    useEffect(() => {
        if (editorInstanceRef.current) {
            const current = editorInstanceRef.current.getMarkdown();
            if (value !== undefined && value !== current) {
                editorInstanceRef.current.setMarkdown(value || '', false);
            }
        }
    }, [value]);

    return (
        <div className="toastui-editor-container border border-slate-300 rounded-xl overflow-hidden bg-white">
            <div ref={editorElRef} />
        </div>
    );
}

