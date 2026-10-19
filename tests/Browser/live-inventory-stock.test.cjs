const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const view = fs.readFileSync(path.join(__dirname, '../../resources/views/pos/index.blade.php'), 'utf8');
const start = view.indexOf('    let stockRefreshRunning = false;');
const source = view.slice(start, view.indexOf('</script>', start));

test('register polls every five seconds, avoids overlapping requests, and blocks stale stock when offline', async () => {
    let tick, visible, resolveRequest, fail = false, calls = 0, notices = 0, renders = 0;
    const products = [{stock: 95}];
    const context = {
        products, searchInput: {value: ''},
        document: {hidden: false, addEventListener: (event, handler) => { visible = handler; }},
        window: {setInterval: (handler, ms) => { assert.equal(ms, 5000); tick = handler; }, AppFeedback: {notify: () => notices++}},
        renderSearchResults: () => renders++,
        refreshProductStock: async () => {
            calls++;
            if (fail) throw new Error('offline');
            await new Promise(resolve => { resolveRequest = resolve; });
            products[0].stock = 0;
        },
    };
    vm.runInNewContext(source, context);
    assert.equal(calls, 1);
    await tick();
    assert.equal(calls, 1);
    resolveRequest();
    await new Promise(setImmediate);
    assert.equal(products[0].stock, 0);
    context.document.hidden = true;
    await tick();
    assert.equal(calls, 1);
    context.document.hidden = false;
    fail = true;
    products[0].stock = 95;
    visible();
    await new Promise(setImmediate);
    assert.equal(products[0].stock, 0);
    assert.equal(notices, 1);
    await tick();
    assert.equal(notices, 1);
    assert.equal(renders, 2);
});
