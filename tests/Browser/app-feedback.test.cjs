const { test } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/app-feedback.js'), 'utf8');
const context = { module: { exports: {} } };
vm.runInNewContext(source, context);
const feedbackModule = context.module.exports;

class FakeElement {
    constructor() {
        this.textContent = '';
        this.hidden = true;
        this.className = '';
        this.attributes = {};
        this.listeners = new Map();
        this.returnValue = '';
        this.open = false;
    }

    setAttribute(name, value) { this.attributes[name] = value; }
    addEventListener(type, listener) {
        const listeners = this.listeners.get(type) || [];
        listeners.push(listener);
        this.listeners.set(type, listeners);
    }
    dispatch(type) {
        for (const listener of this.listeners.get(type) || []) listener({ target: this });
    }
    click() { this.dispatch('click'); }
    showModal() { this.open = true; }
    close(value = '') {
        this.open = false;
        this.returnValue = value;
        this.dispatch('close');
    }
}

class FakeDocument {
    constructor(elements) {
        this.elements = elements;
        this.listeners = new Map();
    }
    getElementById(id) { return this.elements.get(id); }
    addEventListener(type, listener) {
        const listeners = this.listeners.get(type) || [];
        listeners.push(listener);
        this.listeners.set(type, listeners);
    }
    dispatch(type, event) {
        for (const listener of this.listeners.get(type) || []) listener(event);
    }
}

function createFixture() {
    const elements = new Map([
        ['app-feedback', new FakeElement()],
        ['app-confirm-dialog', new FakeElement()],
        ['app-confirm-message', new FakeElement()],
        ['app-confirm-accept', new FakeElement()],
        ['app-confirm-cancel', new FakeElement()],
    ]);
    return { elements, document: new FakeDocument(elements) };
}

test('shows feedback in the app instead of opening a browser alert', () => {
    assert.equal(typeof feedbackModule?.createAppFeedback, 'function');
    const { elements, document } = createFixture();
    let nativeAlertCalled = false;
    const originalAlert = global.alert;
    global.alert = () => { nativeAlertCalled = true; };

    try {
        const feedback = feedbackModule.createAppFeedback(document);
        feedback.notify('<unsafe text>', 'error');

        const notice = elements.get('app-feedback');
        assert.equal(notice.textContent, '<unsafe text>');
        assert.equal(notice.hidden, false);
        assert.equal(notice.attributes.role, 'alert');
        assert.equal(notice.attributes['aria-live'], 'assertive');
        assert.equal(nativeAlertCalled, false);
    } finally {
        if (originalAlert === undefined) delete global.alert;
        else global.alert = originalAlert;
    }
});

test('resolves a confirmation from the in-app dialog buttons', async () => {
    assert.equal(typeof feedbackModule?.createAppFeedback, 'function');
    const { elements, document } = createFixture();
    const feedback = feedbackModule.createAppFeedback(document);
    const result = feedback.confirm('Delete this item?', { confirmLabel: 'Delete' });

    assert.equal(elements.get('app-confirm-message').textContent, 'Delete this item?');
    assert.equal(elements.get('app-confirm-accept').textContent, 'Delete');
    assert.equal(elements.get('app-confirm-dialog').open, true);
    elements.get('app-confirm-accept').click();
    assert.equal(await result, true);

    const cancelled = feedback.confirm('Continue with the action?');
    elements.get('app-confirm-cancel').click();
    assert.equal(await cancelled, false);
});

test('holds a confirmed form submission until the in-app choice is accepted', async () => {
    const { elements, document } = createFixture();
    const browser = { document };
    vm.runInNewContext(source, { window: browser });

    let submissions = 0;
    const form = {
        dataset: { confirm: 'Delete this product?', confirmLabel: 'Delete' },
        requestSubmit() { submissions += 1; },
    };
    const event = {
        target: form,
        defaultPrevented: false,
        preventDefault() { this.defaultPrevented = true; },
    };

    document.dispatch('submit', event);
    assert.equal(event.defaultPrevented, true);
    assert.equal(elements.get('app-confirm-dialog').open, true);
    assert.equal(submissions, 0);
    elements.get('app-confirm-accept').click();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(submissions, 1);
});
