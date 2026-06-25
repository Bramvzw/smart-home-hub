import { safeHttpUrl, renderReview } from '../../Modules/Deals/resources/assets/js/dealtracker.js';

describe('dealtracker url-scheme guard', () => {
    it('passes through http(s) links unchanged', () => {
        expect(safeHttpUrl('https://bol.com/ams')).toBe('https://bol.com/ams');
        expect(safeHttpUrl('http://example.com/x')).toBe('http://example.com/x');
        expect(safeHttpUrl('  https://example.com/x  ')).toBe('https://example.com/x');
    });

    it('rejects dangerous and non-http schemes', () => {
        expect(safeHttpUrl('javascript:alert(1)')).toBeNull();
        expect(safeHttpUrl('JavaScript:alert(1)')).toBeNull();
        expect(safeHttpUrl('data:text/html,<script>alert(1)</script>')).toBeNull();
        expect(safeHttpUrl('vbscript:msgbox(1)')).toBeNull();
        expect(safeHttpUrl('file:///etc/passwd')).toBeNull();
        expect(safeHttpUrl('not a url')).toBeNull();
        expect(safeHttpUrl('')).toBeNull();
        expect(safeHttpUrl(null)).toBeNull();
        expect(safeHttpUrl(undefined)).toBeNull();
    });
});

describe('dealtracker review rendering', () => {
    it('does not emit a malicious listing url as an active link', () => {
        const html = renderReview({
            id: 1,
            name: 'Bambu Lab AMS',
            listings: [
                {
                    id: 10,
                    retailer: 'bol',
                    title: 'Bambu Lab AMS',
                    url: 'javascript:alert(document.cookie)',
                    image_url: 'javascript:alert(1)',
                    current_price: 319,
                },
            ],
        });

        expect(html).not.toContain('javascript:');
        // No href is rendered for the unsafe url; the title falls back to a div.
        expect(html).not.toContain('href=');
        expect(html).not.toMatch(/<img[^>]+src=/);
    });

    it('emits safe http(s) url and image as link/src', () => {
        const html = renderReview({
            id: 1,
            name: 'Bambu Lab AMS',
            listings: [
                {
                    id: 10,
                    retailer: 'bol',
                    title: 'Bambu Lab AMS',
                    url: 'https://bol.com/ams',
                    image_url: 'https://cdn.example.com/ams.jpg',
                    current_price: 319,
                },
            ],
        });

        expect(html).toContain('href="https://bol.com/ams"');
        expect(html).toContain('rel="noopener noreferrer"');
        expect(html).toContain('src="https://cdn.example.com/ams.jpg"');
    });

    it('escapes dynamic text instead of injecting markup', () => {
        const html = renderReview({
            id: 1,
            name: '<b>x</b>',
            listings: [
                {
                    id: 10,
                    retailer: 'bol',
                    title: '<script>alert(1)</script>',
                    url: 'https://bol.com/ams',
                    current_price: 319,
                },
            ],
        });

        expect(html).not.toContain('<script>alert(1)</script>');
        expect(html).toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
        expect(html).toContain('&lt;b&gt;x&lt;/b&gt;');
    });
});
