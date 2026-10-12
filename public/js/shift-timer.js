(function (root) {
    const format = seconds => {
        seconds = Math.max(0, Math.floor(seconds));
        return [Math.floor(seconds / 3600), Math.floor(seconds / 60) % 60, seconds % 60].map(v => String(v).padStart(2,'0')).join(':');
    };
    const elapsed = (opened, current) => Math.max(0, Math.floor((Date.parse(current) - Date.parse(opened)) / 1000)) || 0;
    let timer = null;
    const stop = () => { if (timer !== null) clearInterval(timer); timer = null; };
    const start = (element, opened, serverTime) => {
        stop();
        const base = elapsed(opened, serverTime);
        const monotonicStart = performance.now();
        const tick = () => { element.textContent = 'Shift: ' + format(base + (performance.now() - monotonicStart) / 1000); };
        tick(); timer = setInterval(tick,1000);
    };
    const api = {format,elapsed,start,stop};
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else root.PosShiftTimer = api;
})(typeof window !== 'undefined' ? window : globalThis);
