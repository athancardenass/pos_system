const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const context = {module:{exports:{}}};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../../public/js/pos-reference.js'), 'utf8'), context);
const {countCash} = context.module.exports;
const previewTotal = (...args) => context.module.exports.previewTotal(...args);

test('denomination count includes bills and a decimal coin total without floating point drift', () => {
    assert.equal(countCash([{value:1000, count:1}, {value:500, count:1}, {value:200, count:1}, {value:100, count:1}, {value:20, count:1}, {value:1, count:5.35}]), 1825.35);
    assert.equal(countCash([{value:1, count:0.1}, {value:1, count:0.2}]), 0.3);
});
test('invalid or negative denomination entries cannot become a cash total', () => {
    for (const count of [-1, Infinity, 'abc']) assert.equal(countCash([{value:500, count}]), null);
    assert.equal(countCash([{value:500, count:1.5}]), null);
    assert.equal(countCash([{value:500, count:''}]), 0);
    assert.equal(countCash([{value:1, count:1.999}]), null);
    assert.equal(countCash([{value:500, count:1000000}]), null);
});
test('void preview recalculates discounts and VAT exemption on the remaining subtotal', () => {
    assert.equal(previewTotal(899.04 - 237.44, {}, false, 0.12), 661.6);
    assert.equal(previewTotal(100, {type:'percentage', value:10}, false, 0.12), 90);
    assert.equal(previewTotal(20, {type:'fixed', value:50}, false, 0.12), 0);
    assert.equal(previewTotal(112, {specialType:'senior'}, true, 0.12), 80);
    assert.equal(previewTotal(0, {specialType:'senior'}, true, 0.12), 0);
});
