import { uid } from './utils.js';
import * as storage from './storage.js';

export const state = {
  cats: ['Main levée', 'Trépied', 'Sol / Canapé'],
  reels: [],
  clips: [],
  activeId: null,
  inPt: null,
  outPt: null,
  filter: 'all',
  vCat: null,
  vIdx: 0
};

let persistToServerTimeout = null;

function persist() {
  const data = {
    cats: state.cats,
    clips: state.clips.map(({ id, rid, rname, in: i, out: o, name, cat, sim, done }) => ({
      id, rid, rname, in: i, out: o, name, cat, sim, done
    }))
  };
  storage.save(data);
  if (typeof window !== 'undefined' && typeof window.__BOARD_ID__ === 'number') {
    if (persistToServerTimeout) clearTimeout(persistToServerTimeout);
    persistToServerTimeout = setTimeout(() => {
      persistToServerTimeout = null;
      sendStateToServer(data);
    }, 500);
  }
}

function sendStateToServer(data) {
  if (typeof window === 'undefined' || typeof window.__BOARD_ID__ !== 'number') return;
  const sname = document.getElementById('sname');
  const name = sname ? sname.value.trim() || 'Shooting sans titre' : 'Shooting sans titre';
  fetch('index.php?action=save-board', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: window.__BOARD_ID__, name, state: data })
  }).catch(() => {});
}

/** Sauvegarde immédiate sur le serveur (ex. avant de quitter la page). */
export function saveNow() {
  if (persistToServerTimeout) {
    clearTimeout(persistToServerTimeout);
    persistToServerTimeout = null;
  }
  const data = {
    cats: state.cats,
    clips: state.clips.map(({ id, rid, rname, in: i, out: o, name, cat, sim, done }) => ({
      id, rid, rname, in: i, out: o, name, cat, sim, done
    }))
  };
  storage.save(data);
  if (typeof window !== 'undefined' && typeof window.__BOARD_ID__ === 'number') {
    sendStateToServer(data);
  }
}

function saveOnPageHide() {
  if (typeof window === 'undefined' || typeof window.__BOARD_ID__ !== 'number') return;
  const data = getStateForSave();
  const sname = document.getElementById('sname');
  const name = sname ? sname.value.trim() || 'Shooting sans titre' : 'Shooting sans titre';
  const payload = JSON.stringify({ id: window.__BOARD_ID__, name, state: data });
  const url = new URL('index.php?action=save-board', window.location.href).href;
  navigator.sendBeacon(url, new Blob([payload], { type: 'application/json' }));
}

if (typeof window !== 'undefined') {
  window.addEventListener('pagehide', saveOnPageHide);
}

export function loadFromStorage() {
  if (typeof window !== 'undefined' && window.__BOARD_STATE__ != null) {
    const data = window.__BOARD_STATE__;
    state.cats = Array.isArray(data.cats) ? data.cats : [];
    state.clips = Array.isArray(data.clips)
      ? data.clips.map((c) => ({ ...c, rsrc: null }))
      : [];
    state.reels = Array.isArray(window.__BOARD_REELS__)
      ? window.__BOARD_REELS__.map((r) => ({ id: r.id, name: r.name, src: r.url }))
      : [];
    state.clips.forEach((c) => {
      const reel = state.reels.find((r) => r.id === c.rid || r.id === Number(c.rid));
      if (reel) c.rsrc = reel.src;
    });
    state.activeId = state.reels.length ? (state.activeId && state.reels.some((r) => r.id === state.activeId || r.id === Number(state.activeId)) ? state.activeId : state.reels[0].id) : null;
    return;
  }
  const data = storage.load();
  if (!data) return;
  if (Array.isArray(data.cats) && data.cats.length) state.cats = data.cats;
  if (Array.isArray(data.clips)) {
    state.clips = data.clips.map((c) => ({ ...c, rsrc: null }));
  }
}

/** État sérialisable pour sauvegarde serveur (sans rsrc). */
export function getStateForSave() {
  return {
    cats: state.cats,
    clips: state.clips.map(({ id, rid, rname, in: i, out: o, name, cat, sim, done }) => ({
      id, rid, rname, in: i, out: o, name, cat, sim, done
    }))
  };
}

export function getActiveReel() {
  return state.reels.find((r) => r.id === state.activeId || r.id === Number(state.activeId));
}

export function getCatClips(cat) {
  return state.clips.filter((c) => catMatch(c, cat));
}

export function catMatch(c, cat) {
  const cc = c.cat || '';
  return cat === 'Sans catégorie' ? cc === '' : cc === cat;
}

// #region agent log
function _uploadLog(payload) {
  const entry = { sessionId: '3e1bcf', location: 'model.js:uploadReel', timestamp: Date.now(), ...payload };
  try {
    fetch('http://127.0.0.1:7810/ingest/e18b88f1-b5a3-42ff-93b4-2110dc768b1a', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '3e1bcf' }, body: JSON.stringify(entry) }).catch(() => {});
  } catch (_) {}
  try {
    if (typeof window !== 'undefined' && window.location) {
      const logUrl = window.location.origin + window.location.pathname + '?action=upload-debug-log';
      fetch(logUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(entry) }).catch(() => {});
    }
  } catch (_) {}
}
// #endregion

/**
 * Envoie un fichier vidéo au serveur et retourne { id, name, url }. Les reels sont enregistrés en base (table reels + fichier sur disque).
 * @param {File} file
 * @returns {Promise<{ id: number, name: string, url: string }>}
 */
export function uploadReel(file) {
  const rawId = typeof window !== 'undefined' ? window.__BOARD_ID__ : null;
  const boardId = rawId != null && Number(rawId) >= 1 ? Number(rawId) : null;
  if (boardId === null) {
    return Promise.reject(new Error('Ouvrez un board pour enregistrer les vidéos.'));
  }
  const MAX_REEL_SIZE = 100 * 1024 * 1024; // 100 Mo (aligné avec le serveur)
  if (file.size > MAX_REEL_SIZE) {
    return Promise.reject(new Error(
      'Fichier trop volumineux (' + (Math.round(file.size / 1024 / 1024)) + ' Mo). Taille max : 100 Mo. Réduisez la vidéo ou uploadez un fichier plus petit.'
    ));
  }
  const form = new FormData();
  form.append('file', file);
  form.append('board_id', String(boardId));
  // Même page, seul le paramètre action change (évite les erreurs d'URL en sous-dossier)
  const url = (typeof window !== 'undefined' && window.location)
    ? (window.location.origin + window.location.pathname + '?action=upload-reel')
    : 'index.php?action=upload-reel';
  _uploadLog({ message: 'upload start', data: { url, fileSize: file.size, fileName: file.name, fileType: file.type, boardId }, hypothesisId: 'H3,H5' });
  const controller = new AbortController();
  const timeoutMs = 600000; // 10 min pour grosses vidéos / connexion lente
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
  return fetch(url, { method: 'POST', body: form, credentials: 'same-origin', signal: controller.signal })
    .then(async (res) => {
      clearTimeout(timeoutId);
      const text = await res.text();
      _uploadLog({ message: 'upload response', data: { status: res.status, responseLength: text.length, responsePreview: text.trim().slice(0, 400) }, hypothesisId: 'H2,H3,H4,H5' });
      if (typeof window !== 'undefined' && window.console && window.console.log) {
        window.console.log('[upload-reel]', res.status, res.statusText, 'longueur réponse:', text.length);
      }
      if (res.status === 413) {
        const e = new Error('Fichier trop volumineux (413). Réduisez la taille ou augmentez post_max_size / upload_max_filesize sur le serveur.');
        e.debug = { status: 413, response_preview: text.trim().slice(0, 300) };
        throw e;
      }
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        const snippet = text.trim().slice(0, 500);
        const errMsg = text.trim() === ''
          ? 'Réponse serveur vide (code ' + res.status + '). Vérifiez les logs PHP, post_max_size et que l’URL d’upload est correcte.'
          : (res.status + ' — Réponse invalide (pas du JSON). Début : ' + snippet + (snippet.length >= 500 ? '…' : ''));
        const e = new Error(res.status === 403 ? 'Session expirée. Reconnectez-vous.' : errMsg);
        e.debug = { status: res.status, response_preview: text.trim().slice(0, 500), url };
        _uploadLog({ message: 'upload response not json', data: { status: res.status, responsePreview: text.trim().slice(0, 400) }, hypothesisId: 'H5' });
        throw e;
      }
      if (!res.ok) {
        const e = new Error(data.error || 'Erreur lors de l\'upload.');
        if (data.debug) e.debug = data.debug;
        _uploadLog({ message: 'upload server error', data: { status: res.status, error: data.error, debug: data.debug }, hypothesisId: 'H3,H4' });
        throw e;
      }
      if (data.error) {
        const e = new Error(data.error);
        if (data.debug) e.debug = data.debug;
        throw e;
      }
      if (data.id == null || !data.name || !data.url) {
        const e = new Error('Réponse serveur incomplète (id/name/url manquants).');
        if (data.debug) e.debug = data.debug;
        throw e;
      }
      return { id: Number(data.id), name: data.name, url: data.url };
    })
    .catch((err) => {
      clearTimeout(timeoutId);
      _uploadLog({ message: 'upload fetch failed', data: { errName: err.name, errMessage: err.message }, hypothesisId: 'H1,H2' });
      if (err.name === 'AbortError') throw new Error('Délai dépassé (upload trop long).');
      throw err;
    });
}

/**
 * Ajoute un reel : soit un File (blob URL, sans board), soit { id, name, url } après upload serveur.
 * @param {File|{ id: number, name: string, url: string }} fileOrData
 */
export function addReel(fileOrData) {
  const isServerReel = fileOrData && typeof fileOrData === 'object' && 'id' in fileOrData && 'url' in fileOrData;
  let r;
  if (isServerReel) {
    const { id, name, url } = fileOrData;
    r = { id, name, src: url };
  } else {
    const file = fileOrData;
    const name = file.name.replace(/\.[^.]+$/, '');
    r = { id: uid(), name, src: URL.createObjectURL(file) };
  }
  state.reels.push(r);
  if (!state.activeId) state.activeId = r.id;
  state.clips.forEach((c) => {
    if (c.rname === r.name) {
      c.rid = r.id;
      c.rsrc = r.src;
    }
  });
  persist();
  return r;
}

/**
 * Supprime un reel (et ses plans). Si reel serveur (id numérique + __BOARD_ID__), appelle l'API delete-reel.
 * @param {string|number} id
 * @returns {Promise<void>}
 */
export async function removeReel(id) {
  const reel = state.reels.find((r) => r.id === id || r.id === Number(id));
  const numId = Number(id);
  const isServerReel = typeof window !== 'undefined' && typeof window.__BOARD_ID__ === 'number' && !Number.isNaN(numId) && numId === Math.floor(numId) && numId > 0;
  if (reel && isServerReel) {
    try {
      const res = await fetch('index.php?action=delete-reel&id=' + numId, { method: 'POST' });
      if (!res.ok) return;
    } catch (_) {
      return;
    }
  }
  state.reels = state.reels.filter((r) => r.id !== id && r.id !== numId);
  state.clips = state.clips.filter((c) => c.rid !== id && c.rid !== numId);
  if (state.activeId === id) {
    state.activeId = state.reels[0] ? state.reels[0].id : null;
  }
  persist();
}

export function setActiveReel(id) {
  state.activeId = id;
}

export function addCategory(name) {
  if (name && !state.cats.includes(name)) {
    state.cats.push(name);
    persist();
  }
}

export function removeCategory(name) {
  state.cats = state.cats.filter((x) => x !== name);
  persist();
}

export function setInPt(t) {
  state.inPt = t;
}

export function setOutPt(t) {
  state.outPt = t;
}

export function resetInOut() {
  state.inPt = null;
  state.outPt = null;
}

export function addClip(reel, name, cat) {
  const clip = {
    id: uid(),
    rid: reel.id,
    rname: reel.name,
    rsrc: reel.src,
    in: state.inPt,
    out: state.outPt,
    name: name || 'Plan sans nom',
    cat: cat || '',
    sim: false,
    done: false
  };
  state.clips.push(clip);
  persist();
  return clip;
}

export function removeClip(id) {
  state.clips = state.clips.filter((c) => c.id !== id);
  persist();
}

export function setClipName(id, v) {
  const c = state.clips.find((x) => x.id === id);
  if (c) { c.name = v; persist(); }
}

export function setClipCat(id, v) {
  const c = state.clips.find((x) => x.id === id);
  if (c) { c.cat = v; persist(); }
}

export function toggleClipDone(id) {
  const c = state.clips.find((x) => x.id === id);
  if (c) { c.done = !c.done; persist(); }
}

export function reorderClips(dragId, dropId) {
  const arr = state.clips.filter((c) => c.rid === state.activeId);
  const oth = state.clips.filter((c) => c.rid !== state.activeId);
  const si = arr.findIndex((c) => c.id === dragId);
  const ti = arr.findIndex((c) => c.id === dropId);
  if (si < 0 || ti < 0) return;
  arr.splice(ti, 0, arr.splice(si, 1)[0]);
  state.clips = oth.concat(arr);
  persist();
}

export function setFilter(f) {
  state.filter = f;
}

export function detectSimilars() {
  state.clips.forEach((c) => (c.sim = false));
  const map = {};
  state.clips.forEach((c) => {
    const k = c.name.toLowerCase().trim();
    if (!map[k]) map[k] = [];
    map[k].push(c);
  });
  Object.values(map).forEach((g) => {
    if (g.length > 1) g.forEach((c) => (c.sim = true));
  });
  persist();
}

export function openViewer(cat, clipId) {
  state.vCat = cat || 'Sans catégorie';
  const arr = getCatClips(state.vCat);
  if (clipId) {
    const idx = arr.findIndex((c) => c.id === clipId);
    state.vIdx = idx >= 0 ? idx : 0;
  } else {
    state.vIdx = 0;
  }
}
