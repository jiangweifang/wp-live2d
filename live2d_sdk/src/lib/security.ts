import DOMPurify from 'dompurify';

export function sanitizeMessageHtml(html: string): string {
    return DOMPurify.sanitize(html, { USE_PROFILES: { html: true } });
}

export function getSafeNavigationUrl(value: unknown): string | null {
    if (typeof value !== 'string' || !value.trim()) return null;
    try {
        const url = new URL(value, document.baseURI);
        return url.protocol === 'https:' || url.protocol === 'http:' ? url.href : null;
    } catch {
        return null;
    }
}

export function navigateToolbar(value: unknown, newWindow = false): void {
    const url = getSafeNavigationUrl(value);
    if (!url) {
        console.warn('[Live2D] Navigation target is not an HTTP(S) URL.');
        return;
    }
    if (newWindow) window.open(url, '_blank', 'noopener,noreferrer');
    else window.location.assign(url);
}

export function escapeHtmlAttribute(value: unknown): string {
    const entities: Record<string, string> = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(value ?? '').replace(/[&<>"']/g, character => entities[character]);
}
