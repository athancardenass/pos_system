const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const view = fs.readFileSync(path.join(__dirname, '../../resources/views/pos/index.blade.php'), 'utf8');
const source = view.slice(view.indexOf('    async function removeCartItem('), view.indexOf('    function addToCart('));
const reference = {module: {exports: {}}};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/pos-reference.js'), 'utf8'), reference);

function setup({focusedId = null, approved = true, modal = false} = {}) {
    const calls = [], notices = [];
    const row = focusedId === null ? null : {dataset: {productId: String(focusedId)}};
    const context = {
        cart: [{id: 1, name: 'Rice', price: 20, qty: 2}, {id: 2, name: 'Bread', price: 30, qty: 1}],
        checkoutSubmissionStarted: false,
        document: {activeElement: {closest: () => row}, querySelectorAll: () => modal ? [{hidden: false, getClientRects: () => [1]}] : []},
        cartRows: {contains: candidate => candidate === row && row !== null},
        discountSelect: {selectedOptions: [{dataset: {}}]}, totalVal: {textContent: '70'}, parseNumeric: Number,
        configVatEnabled: false, vatRate: 0.12,
        renderCart: () => {}, clearCheckoutIdempotencyKey: () => {},
        window: {
            requestManagerAuthorization: async options => { calls.push(options); return approved; },
            AppFeedback: {notify: message => notices.push(message)},
            PosReference: reference.module.exports,
        },
    };
    vm.runInNewContext(source + '\nglobalThis.voidLine = () => voidFocusedCartLine();', context);
    return {context, calls, notices};
}

test('void shortcut targets the focused row and retains manager approval and reason', async () => {
    const {context, calls} = setup({focusedId: 1});
    await context.voidLine();
    assert.deepEqual(Array.from(context.cart, item => item.id), [2]);
    assert.equal(calls[0].action, 'cart_item_void');
    assert.equal(calls[0].details.product_id, 1);
    assert.equal(calls[0].requiresReason, true);
    assert.equal(calls[0].presentation.totalAfter, 30);
});

test('void shortcut falls back to the last cart line and cancellation preserves it', async () => {
    const {context, calls} = setup({approved: false});
    await context.voidLine();
    assert.equal(calls[0].details.product_id, 2);
    assert.equal(context.cart.length, 2);
});

test('void shortcut ignores open dialogs, checkout submission, and an empty cart', async () => {
    const open = setup({modal: true});
    await open.context.voidLine();
    assert.equal(open.calls.length, 0);
    const submitting = setup();
    submitting.context.checkoutSubmissionStarted = true;
    await submitting.context.voidLine();
    assert.equal(submitting.calls.length, 0);
    const empty = setup();
    empty.context.cart.length = 0;
    await empty.context.voidLine();
    assert.equal(empty.calls.length, 0);
    assert.equal(empty.notices.length, 1);
});

test('void shortcut opens only one authorization while a decision is pending', async () => {
    const {context, calls} = setup();
    let decide;
    context.window.requestManagerAuthorization = async options => {
        calls.push(options);
        return new Promise(resolve => { decide = resolve; });
    };
    const first = context.voidLine();
    await context.voidLine();
    assert.equal(calls.length, 1);
    decide(false);
    await first;
    assert.equal(context.cart.length, 2);
});

test('F6 dispatches the void action and suppresses repeated or modified keypresses', () => {
    let handler, calls = 0, prevented = 0;
    const keyboard = view.slice(view.indexOf('    // POS Global Keyboard Shortcuts'), view.indexOf("    window.addEventListener('pageshow'"));
    vm.runInNewContext(keyboard, {
        window: {addEventListener: (name, fn) => { handler = fn; }},
        document: {activeElement: null}, lastScanKeyTimestamp: 0, barcodeScanBuffer: '',
        voidFocusedCartLine: () => calls++,
    });
    const key = extra => ({key: 'F6', preventDefault: () => prevented++, ...extra});
    handler(key({}));
    handler(key({repeat: true}));
    handler(key({ctrlKey: true}));
    assert.equal(calls, 1);
    assert.equal(prevented, 2);
});
