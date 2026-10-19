import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

const context = {window:{}, URL};
vm.runInNewContext(readFileSync('public/js/offline-resources.js','utf8'), context);
const resources = context.window.InayOfflineResources;
const base = new URL('https://example.test/portal/');
const node = attributes => ({getAttribute:name => attributes[name] || null});

test('mother pages, all child selections and document files are included under a subdirectory', () => {
    for (const path of ['child-health?child=2', 'mother/documents/7/preview', 'mother/documents/7/download', 'mother/settings', 'inay-kaalaman/videos/10', 'storage/learning/image.png']) {
        assert.equal(resources.normalize(path, base.href, base), `${base.href}${path}`);
    }
    assert.equal(resources.normalize('mother/dashboard?source=pwa#section', base.href, base), `${base.href}mother/dashboard`);
    for (const path of ['https://youtube.com/watch?v=1', '../other-account', 'logout', 'mother/profile-photo', 'staff/offline-records/sync']) {
        assert.equal(resources.normalize(path, base.href, base), null);
    }
});

test('discovery includes dropdown children, document data attributes and inert infographic templates', () => {
    const found = [];
    const template = {querySelectorAll: selector => selector.startsWith('script') ? [node({src:'storage/infographic.png'})] : []};
    const document = {querySelectorAll: selector => {
        if (selector === 'a[href], option[value]') return [node({value:'child-health?child=2'})];
        if (selector === '[data-preview], [data-download]') return [node({'data-preview':'mother/documents/7/preview','data-download':'mother/documents/7/download'})];
        if (selector === 'template') return [{content:template}];
        return [];
    }};
    resources.discover(document, value => found.push(value));
    for (const value of ['child-health?child=2','mother/documents/7/preview','mother/documents/7/download','storage/infographic.png']) assert.ok(found.includes(value));
});

test('stylesheets discover local fonts and imported styles without accepting external downloads', () => {
    const urls = [];
    resources.css('@import "./extra.css"; a{background:url(../images/icon.svg)} @font-face{src:url("../fonts/text.woff2")}', value => urls.push(value));
    assert.equal(urls.length, 3);
    assert.equal(resources.normalize(urls[2], `${base.href}css/app.css`, base, true), `${base.href}fonts/text.woff2`);
    assert.equal(resources.normalize('https://fonts.example/font.woff2', base.href, base, true), null);
});
