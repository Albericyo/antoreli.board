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
      const sname = document.getElementById('sname');
      const name = sname ? sname.value.trim() || 'Shooting sans titre' : 'Shooting sans titre';
      fetch('index.php?action=save-board', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: window.__BOARD_ID__, name, state: data })
      }).catch(() => {});
    }, 800);
  }
}

export function loadFromStorage() {
  if (typeof window !== 'undefined' && window.__BOARD_STATE__) {
    const data = window.__BOARD_STATE__;
    if (Array.isArray(data.cats) && data.cats.length) state.cats = data.cats;
    if (Array.isArray(data.clips)) {
      state.clips = data.clips.map((c) => ({ ...c, rsrc: null }));
    }
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
  return state.reels.find((r) => r.id === state.activeId);
}

export function getCatClips(cat) {
  return state.clips.filter((c) => catMatch(c, cat));
}

export function catMatch(c, cat) {
  const cc = c.cat || '';
  return cat === 'Sans catégorie' ? cc === '' : cc === cat;
}

export function addReel(file) {
  const name = file.name.replace(/\.[^.]+$/, '');
  const r = {
    id: uid(),
    name,
    src: URL.createObjectURL(file)
  };
  state.reels.push(r);
  if (!state.activeId) state.activeId = r.id;
  state.clips.forEach((c) => {
    if (c.rname === name) {
      c.rid = r.id;
      c.rsrc = r.src;
    }
  });
  persist();
  return r;
}

export function removeReel(id) {
  state.reels = state.reels.filter((r) => r.id !== id);
  state.clips = state.clips.filter((c) => c.rid !== id);
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
