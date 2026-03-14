export function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
}

export function fmt(s) {
  if (s === null || s === undefined || isNaN(s)) return '--:--';
  const m = Math.floor(s / 60);
  let sec = (s % 60).toFixed(1);
  if (sec.length < 4) sec = '0' + sec;
  return m + ':' + sec;
}

export function fmtS(s) {
  if (!s && s !== 0) return '0:00';
  return Math.floor(s / 60) + ':' + String(Math.round(s % 60)).padStart(2, '0');
}

export function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
