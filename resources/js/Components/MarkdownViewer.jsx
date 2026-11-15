import React, { useEffect, useRef } from 'react';
// The standalone Viewer bundle is much smaller than the full Editor bundle.
import Viewer from '@toast-ui/editor/dist/toastui-editor-viewer';
import '@toast-ui/editor/dist/toastui-editor-viewer.css';

export default function MarkdownViewer({ content = '' }) {
    const viewerElRef = useRef(null);
    const viewerInstanceRef = useRef(null);

    useEffect(() => {
        if (!viewerElRef.current) return;
        // TOAST UI renders into its own child node that React does not manage;
        // this avoids "removeChild" errors when the component unmounts.
        const mountNode = document.createElement('div');
        viewerElRef.current.appendChild(mountNode);

        viewerInstanceRef.current = new Viewer({
            el: mountNode,
            initialValue: content || '',
            usageStatistics: false,
        });

        return () => {
            if (viewerInstanceRef.current) {
                viewerInstanceRef.current.destroy();
                viewerInstanceRef.current = null;
            }
            mountNode.remove();
        };
    }, []);

    useEffect(() => {
        if (viewerInstanceRef.current) {
            viewerInstanceRef.current.setMarkdown(content || '');
        }
    }, [content]);

    return (
        <div className="toastui-viewer-container">
            <div ref={viewerElRef} />
        </div>
    );
}

