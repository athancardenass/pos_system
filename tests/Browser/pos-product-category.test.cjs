const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const view = fs.readFileSync(require('node:path').join(__dirname, '../../resources/views/pos/index.blade.php'), 'utf8');
const source = view.slice(view.indexOf('    function renderSearchResults('), view.indexOf('    // Category chips click listener'));
test('POS cards display category as plain text with a fallback', () => {
    const element = () => ({children: [], classList: {toggle() {}}, append(...items) {this.children.push(...items);}, appendChild(item) {this.children.push(item);}});
    const list = element();
    const context = {products: [{id: 1, name: 'Water', category_name: '<img src=x onerror=alert(1)>', stock: 10, price: 5}, {id: 2, name: 'Other', stock: 0, price: 5}], selectedCategory: 'all', searchResultsList: list, document: {createElement: element}, formatStock: String, formatMoney: String};
    vm.runInNewContext(source + '\nrenderSearchResults();', context);
    const first = list.children[0].children[0].children;
    assert.equal(first.find(e => e.className === 'pos-product-category')?.textContent, '<img src=x onerror=alert(1)>');
    const second = list.children[1].children[0].children;
    assert.equal(second.find(e => e.className === 'pos-product-category')?.textContent, 'General');
});

test('cashier category button filters the catalog and All restores products', () => {
    const element = () => ({children: [], classList: {toggle() {}, add() {}, remove() {}}, append(...items) {this.children.push(...items);}, appendChild(item) {this.children.push(item);}, set innerHTML(value) {this.children = [];}});
    const list = element();
    const chips = ['all', '1', '2'].map(id => ({...element(), dataset: {category: id}, addEventListener(event, fn) {this.click = fn;}}));
    const document = {createElement: element, querySelectorAll: () => chips};
    const handler = view.slice(view.indexOf('    // Category chips click listener'), view.indexOf("    searchInput.addEventListener('input'"));
    const context = {products: [{id: 1, name: 'Water', category_id: 1, category_name: 'Beverages', stock: 10, price: 5}, {id: 2, name: 'Bread', category_id: 2, category_name: 'Bakery', stock: 10, price: 5}], selectedCategory: 'all', searchResultsList: list, searchInput: {value: ''}, document, formatStock: String, formatMoney: String};
    vm.runInNewContext(source + handler, context);
    chips[1].click.call(chips[1]);
    assert.equal(list.children.length, 1);
    assert.equal(list.children[0].children[0].children[0].textContent, 'Water');
    chips[0].click.call(chips[0]);
    assert.equal(list.children.length, 2);
});
