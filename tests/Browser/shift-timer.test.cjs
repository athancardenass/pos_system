const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(require('node:path').join(__dirname,'../../public/js/shift-timer.js'),'utf8');
test('uses server opening time across midnight and does not wrap at 24 hours',()=>{
    const context={module:{exports:{}}}; vm.runInNewContext(source,context);
    assert.equal(context.module.exports.elapsed('2026-10-12T23:59:00+08:00','2026-10-13T00:01:05+08:00'),125);
    assert.equal(context.module.exports.format(90061),'25:01:01');
    assert.equal(context.module.exports.elapsed('2026-10-12T09:00:00+08:00','2026-10-12T08:59:00+08:00'),0);
});
test('ticks from server elapsed time and replaces old intervals after refresh',()=>{
    let now=0,tick,cleared=0;
    const context={module:{exports:{}},performance:{now:()=>now},setInterval:f=>{tick=f; return 1},clearInterval:()=>cleared++};
    vm.runInNewContext(source,context);
    const timer=context.module.exports, element={textContent:''};
    timer.start(element,'2026-10-12T09:00:00+08:00','2026-10-12T11:35:18+08:00');
    assert.equal(element.textContent,'Shift: 02:35:18');
    now=5000; tick(); assert.equal(element.textContent,'Shift: 02:35:23');
    timer.start(element,'2026-10-12T09:00:00+08:00','2026-10-12T11:35:25+08:00');
    assert.equal(element.textContent,'Shift: 02:35:25'); assert.equal(cleared,1);
    timer.stop(); assert.equal(cleared,2);
});
