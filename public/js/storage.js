function getStorageKey() {
  if (typeof window !== 'undefined' && window.__BOARD_ID__) {
    return 'shooting-board-' + window.__BOARD_ID__;
  }
  return 'shooting-board';
}

export function load() {
  try {
    const raw = localStorage.getItem(getStorageKey());
    if (!raw) return null;
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

export function save(data) {
  try {
    localStorage.setItem(getStorageKey(), JSON.stringify(data));
  } catch (e) {
    console.warn('Erreur lors de la sauvegarde:', e);
  }
}
