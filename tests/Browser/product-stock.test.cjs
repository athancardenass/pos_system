const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../resources/views/products/index.blade.php'), 'utf8').split('<script>')[1].split("    document.querySelectorAll('.print-barcode-btn')")[0];
test('stock polling updates cells, preserves values on failure, and refreshes when visible', async () => {
    let tick, visibility, requests = 0, fail = false, warning;
    const cell = {textContent: '10', classList: {toggle: (name, value) => { warning = value; }}};
    const document = {hidden: false, querySelector: () => cell, addEventListener: (event, handler) => {visibility = handler;}};
    const context = {document, AbortSignal, window: {location: {href: 'http://pos/products?page=2'}, setInterval: (fn, ms) => {assert.equal(ms, 5000); tick = fn;}}, fetch: async (url, options) => {
        requests++;
        assert.equal(url, 'http://pos/products?page=2');
        assert.equal(options.cache, 'no-store');
        if (fail) throw new Error('offline');
        return {ok: true, status: 200, json: async () => ({stocks: [{id: 1, quantity: 2.5, low: true}]})};
    }};
    vm.runInNewContext(source, context);
    await tick(); assert.equal(cell.textContent, '2.5'); assert.equal(warning, true);
    document.hidden = true; await tick(); assert.equal(requests, 1);
    document.hidden = false; fail = true; await visibility(); assert.equal(cell.textContent, '2.5');
    fail = false; await tick(); assert.equal(requests, 3);
});
