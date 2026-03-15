import {
  state,
  getActiveReel,
  getCatClips,
  addReel,
  removeReel,
  uploadReel,
  setActiveReel,
  addCategory,
  removeCategory,
  setInPt,
  setOutPt,
  resetInOut,
  addClip,
  removeClip,
  setClipName,
  setClipCat,
  toggleClipDone,
  reorderClips,
  setFilter,
  detectSimilars,
  openViewer as modelOpenViewer,
  getStateForSave,
  saveNow
} from './model.js';
import {
  renderCats,
  renderReels,
  renderTabs,
  renderCList,
  renderMarks,
  renderBoard,
  renderFps,
  loadVid,
  loadCV,
  updateBar,
  updateCBar,
  resetMarksUI,
  updateMarksUI,
  startAnim,
  stopAnim,
  startCV,
  stopCV,
  openViewerModal,
  closeViewerModal,
  setPlayBtnLabel,
  setCVPlayBtnLabel
} from './view.js';

let dragSrc = null;

export function go(pg) {
  document.querySelectorAll('.page').forEach((p) => p.classList.remove('on'));
  document.querySelectorAll('.nb').forEach((b) => b.classList.remove('on'));
  const page = document.getElementById('page-' + pg);
  const nb = document.getElementById('nb-' + pg);
  if (page) page.classList.add('on');
  if (nb) nb.classList.add('on');
  if (pg === 'cut') initCut();
  if (pg === 'board') renderBoard();
}

function initCut() {
  renderTabs();
  renderCats();
  renderCList();
  loadVid();
}

export function openSnap() {
  const u = (document.getElementById('snap-url')?.value || '').trim();
  window.open('https://snapinsta.app/' + (u ? '?url=' + encodeURIComponent(u) : ''), '_blank');
}

export function addCat() {
  const inp = document.getElementById('cat-inp');
  const v = (inp?.value || '').trim();
  if (v) addCategory(v);
  if (inp) inp.value = '';
  renderCats();
}

export function rmCat(cat) {
  removeCategory(cat);
  renderCats();
}

function setUploadMsg(text, isError = false) {
  const el = document.getElementById('upload-msg');
  if (!el) return;
  el.textContent = text || '';
  el.className = 'upload-msg' + (isError ? ' err' : text ? ' ok' : '');
  if (text && !isError) setTimeout(() => { el.textContent = ''; el.className = 'upload-msg'; }, 3000);
}

export async function addFiles(inp) {
  if (!inp?.files || !inp.files.length) return;
  const rawId = typeof window !== 'undefined' ? window.__BOARD_ID__ : null;
  const boardId = rawId != null && Number(rawId) >= 1 ? Number(rawId) : null;
  const files = Array.from(inp.files);
  inp.value = '';
  if (boardId !== null) {
    const finp = document.getElementById('finp');
    if (finp) finp.disabled = true;
    setUploadMsg('Import en cours…');
    let okCount = 0;
    for (const file of files) {
      try {
        const result = await uploadReel(file);
        addReel(result);
        okCount += 1;
      } catch (err) {
        const msg = (err.message === 'Failed to fetch' || err.message === 'Load failed')
          ? 'Connexion impossible. Vérifiez le réseau et que l\'application est bien déployée.'
          : (err.message || 'Erreur lors de l\'upload.');
        setUploadMsg(msg, true);
        alert(msg);
      }
    }
    if (finp) finp.disabled = false;
    if (okCount > 0) setUploadMsg(okCount === 1 ? '1 vidéo enregistrée.' : okCount + ' vidéos enregistrées.');
  } else {
    files.forEach((f) => addReel(f));
    setUploadMsg('');
  }
  renderReels();
}

export function selReel(id) {
  setActiveReel(id);
  renderReels();
}

export async function rmReel(id) {
  await removeReel(id);
  renderReels();
  renderTabs();
  loadVid();
  renderCList();
}

export function switchTab(id) {
  setActiveReel(id);
  resetInOut();
  resetMarksUI();
  renderTabs();
  renderCList();
  loadVid();
}

export function togglePlay() {
  const vid = document.getElementById('vid');
  if (!vid?.src || !getActiveReel()) return;
  if (vid.paused) vid.play().catch(() => {});
  else vid.pause();
}

export function markIn() {
  const vid = document.getElementById('vid');
  const t = vid?.readyState >= 1 ? vid.currentTime : parseFloat(prompt('IN (secondes) :') || '');
  if (isNaN(t)) return;
  setInPt(t);
  updateMarksUI();
}

export function markOut() {
  const vid = document.getElementById('vid');
  const t = vid?.readyState >= 1 ? vid.currentTime : parseFloat(prompt('OUT (secondes) :') || '');
  if (isNaN(t)) return;
  setOutPt(t);
  updateMarksUI();
}

export function addClipHandler() {
  if (!state.activeId) return alert('Sélectionne un reel.');
  if (state.inPt === null) return alert('Marque un IN.');
  if (state.outPt === null) return alert('Marque un OUT.');
  if (state.outPt <= state.inPt) return alert('OUT doit être après IN.');
  const r = getActiveReel();
  if (!r) return;
  const name = (document.getElementById('cname')?.value || '').trim() || 'Plan sans nom';
  const cat = document.getElementById('ccat')?.value || '';
  addClip(r, name, cat);
  const cnameInp = document.getElementById('cname');
  if (cnameInp) cnameInp.value = '';
  resetInOut();
  resetMarksUI();
  renderCList();
  updateBar();
  renderReels();
}

export function rmClipHandler(id) {
  removeClip(id);
  renderCList();
  updateBar();
  renderReels();
}

export function rmClipBoard(id) {
  removeClip(id);
  renderBoard();
}

export function openViewer(cat, clipId) {
  const realCat = cat || 'Sans catégorie';
  const arr = getCatClips(realCat);
  if (!arr.length) return;
  modelOpenViewer(realCat, clipId || null);
  openViewerModal();
  loadCV();
}

export function closeV() {
  closeViewerModal();
}

export function vNav(dir) {
  const arr = getCatClips(state.vCat);
  state.vIdx = (state.vIdx + dir + arr.length) % arr.length;
  loadCV();
}

export function toggleCV() {
  const cvid = document.getElementById('cvid');
  if (!cvid?.src) return;
  const arr = getCatClips(state.vCat);
  const c = arr[state.vIdx];
  if (cvid.paused) {
    if (c && cvid.currentTime >= c.out) cvid.currentTime = c.in;
    cvid.play().catch(() => {});
    startCV();
  } else {
    cvid.pause();
  }
}

export function toggleDone() {
  const arr = getCatClips(state.vCat);
  const c = arr[state.vIdx];
  if (!c) return;
  toggleClipDone(c.id);
  loadCV();
  renderBoard();
}

export function detectSim() {
  detectSimilars();
  renderBoard();
}

export function handleFilter(filter) {
  setFilter(filter);
  renderBoard();
}

function dStart(e, id) {
  dragSrc = id;
  e.dataTransfer.effectAllowed = 'move';
}

function dOver(e, id) {
  e.preventDefault();
  if (!dragSrc || dragSrc === id) return;
  reorderClips(dragSrc, id);
  dragSrc = id;
  renderCList();
}

function handleCBarClick(e) {
  const cvid = document.getElementById('cvid');
  const arr = getCatClips(state.vCat);
  const c = arr[state.vIdx];
  if (!c || !cvid?.duration) return;
  const rect = e.currentTarget.getBoundingClientRect();
  const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
  cvid.currentTime = c.in + pct * (c.out - c.in);
  updateCBar();
}

export function bindEvents() {
  const vid = document.getElementById('vid');
  const cvid = document.getElementById('cvid');
  const finp = document.getElementById('finp');
  const catInp = document.getElementById('cat-inp');
  const pbar = document.getElementById('pbar');
  const vbg = document.getElementById('vbg');
  const cbar = document.getElementById('cbar');

  if (catInp) {
    catInp.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') addCat();
    });
  }

  if (finp) {
    finp.addEventListener('change', (e) => addFiles(e.target));
  }

  if (vid) {
    vid.addEventListener('play', () => {
      setPlayBtnLabel('⏸');
      startAnim();
    });
    vid.addEventListener('pause', () => {
      setPlayBtnLabel('▶');
      stopAnim();
    });
    vid.addEventListener('ended', () => {
      setPlayBtnLabel('▶');
      stopAnim();
    });
    vid.addEventListener('timeupdate', updateBar);
    vid.addEventListener('loadedmetadata', updateBar);
    vid.addEventListener('click', togglePlay);
    vid.addEventListener('error', () => {
      const msg = document.getElementById('vmsg');
      if (msg) {
        msg.style.display = 'flex';
        msg.innerHTML =
          '<div style="color:#f87171;font-size:13px">Format non supporté. Essaie un MP4 H.264.</div>';
      }
      if (vid) vid.style.display = 'none';
    });
  }

  const muteBtn = document.getElementById('mute-btn');
  if (muteBtn && vid) {
    function updateMuteBtnLabel() {
      muteBtn.textContent = vid.muted ? '🔇' : '🔊';
      muteBtn.title = vid.muted ? 'Son coupé (cliquer pour activer)' : 'Son activé (cliquer pour couper)';
    }
    updateMuteBtnLabel();
    muteBtn.addEventListener('click', () => {
      vid.muted = !vid.muted;
      updateMuteBtnLabel();
    });
  }

  if (pbar) {
    pbar.addEventListener('click', (e) => {
      if (!vid?.duration) return;
      const rect = pbar.getBoundingClientRect();
      vid.currentTime = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)) * vid.duration;
      updateBar();
    });
  }

  const rateSelect = document.querySelector('.pctrl select');
  if (rateSelect && vid) {
    rateSelect.addEventListener('change', (e) => {
      vid.playbackRate = parseFloat(e.target.value);
    });
  }

  const cRateSelect = document.querySelector('.vctrl select');
  if (cRateSelect && cvid) {
    cRateSelect.addEventListener('change', (e) => {
      cvid.playbackRate = parseFloat(e.target.value);
    });
  }

  if (cvid) {
    cvid.addEventListener('play', () => setCVPlayBtnLabel('⏸'));
    cvid.addEventListener('pause', () => {
      setCVPlayBtnLabel('▶');
      stopCV();
    });
  }

  if (cbar) cbar.addEventListener('click', handleCBarClick);

  if (vbg) {
    vbg.addEventListener('click', (e) => {
      if (e.target === vbg) closeV();
    });
  }

  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-action]');
    if (!el) return;
    const action = el.dataset.action;
    const id = el.dataset.id;
    const cat = el.dataset.cat;
    const clipId = el.dataset.clipId;

    if (action === 'rmCat' && cat !== undefined) rmCat(cat);
    else if (action === 'selReel' && id) selReel(id);
    else if (action === 'rmReel' && id) {
      e.stopPropagation();
      rmReel(id).then(() => {}).catch(() => {});
    } else if (action === 'switchTab' && id) switchTab(id);
    else if (action === 'rmClip' && id) {
      e.stopPropagation();
      rmClipHandler(id);
    } else if (action === 'rmClipBoard' && id) {
      e.stopPropagation();
      rmClipBoard(id);
    } else if (action === 'openViewer' && cat !== undefined) {
      e.stopPropagation();
      openViewer(cat, clipId || null);
    } else if (action === 'setFilter' && el.dataset.filter !== undefined) {
      handleFilter(el.dataset.filter);
    } else if (action === 'vdot' && el.dataset.idx !== undefined) {
      state.vIdx = parseInt(el.dataset.idx, 10);
      loadCV();
    }
  });

  document.addEventListener('change', (e) => {
    const el = e.target.closest('[data-action]');
    if (!el) return;
    if (el.dataset.action === 'clipName' && el.dataset.id) {
      setClipName(el.dataset.id, el.value);
    } else if (el.dataset.action === 'clipCat' && el.dataset.id) {
      setClipCat(el.dataset.id, el.value);
    }
  });

  document.addEventListener('dragstart', (e) => {
    const bclip = e.target.closest('.bclip');
    if (bclip && bclip.dataset.clipId) {
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('application/x-board-clip', bclip.dataset.clipId);
      e.stopPropagation();
      return;
    }
    const el = e.target.closest('[data-drag-id]');
    if (!el) return;
    e.stopPropagation();
    dStart(e, el.dataset.dragId);
  });

  document.addEventListener('dragover', (e) => {
    if (e.dataTransfer.types.includes('application/x-board-clip')) {
      const col = e.target.closest('.bcol');
      if (col) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        document.querySelectorAll('.bcol').forEach((c) => c.classList.remove('bcol--drag-over'));
        col.classList.add('bcol--drag-over');
        return;
      }
    }
    const el = e.target.closest('[data-drag-id]');
    if (!el) return;
    const id = el.dataset.dragId;
    if (id) dOver(e, id);
  });

  document.addEventListener('dragleave', (e) => {
    if (!e.target.closest('.bcol') || e.relatedTarget?.closest('.bcol')) return;
    document.querySelectorAll('.bcol').forEach((c) => c.classList.remove('bcol--drag-over'));
  });

  document.addEventListener('drop', (e) => {
    const col = e.target.closest('.bcol');
    if (col && e.dataTransfer.types.includes('application/x-board-clip')) {
      e.preventDefault();
      const clipId = e.dataTransfer.getData('application/x-board-clip');
      const cat = col.dataset.cat;
      if (clipId && cat !== undefined) {
        setClipCat(clipId, cat);
        renderBoard();
      }
      document.querySelectorAll('.bcol').forEach((c) => c.classList.remove('bcol--drag-over'));
      return;
    }
    e.preventDefault();
  });

  document.addEventListener('dragend', () => {
    document.querySelectorAll('.bcol').forEach((c) => c.classList.remove('bcol--drag-over'));
  });

  document.querySelectorAll('a[href*="action=dashboard"]').forEach((a) => {
    a.addEventListener('click', () => saveNow());
  });

  document.getElementById('nb-import')?.addEventListener('click', () => go('import'));
  document.getElementById('nb-cut')?.addEventListener('click', () => go('cut'));
  document.getElementById('nb-board')?.addEventListener('click', () => go('board'));
  document.querySelectorAll('[data-goto="cut"]').forEach((b) => b.addEventListener('click', () => go('cut')));
  document.querySelectorAll('[data-goto="board"]').forEach((b) => b.addEventListener('click', () => go('board')));
  document.getElementById('snap-btn')?.addEventListener('click', openSnap);
  document.getElementById('addcat-btn')?.addEventListener('click', addCat);
  document.getElementById('addclip-btn')?.addEventListener('click', addClipHandler);
  document.getElementById('pbtn')?.addEventListener('click', togglePlay);
  document.getElementById('in-btn')?.addEventListener('click', markIn);
  document.getElementById('out-btn')?.addEventListener('click', markOut);
  document.getElementById('detectsim-btn')?.addEventListener('click', detectSim);
  document.getElementById('vcls')?.addEventListener('click', closeV);
  document.getElementById('vvalbtn')?.addEventListener('click', toggleDone);
  document.querySelector('.vpl')?.addEventListener('click', () => vNav(-1));
  document.querySelector('.vpr')?.addEventListener('click', () => vNav(1));
  document.getElementById('cpbtn')?.addEventListener('click', toggleCV);

  const cvMuteBtn = document.getElementById('cv-mute-btn');
  if (cvMuteBtn && cvid) {
    cvMuteBtn.addEventListener('click', () => {
      cvid.muted = !cvid.muted;
      cvMuteBtn.textContent = cvid.muted ? '🔇' : '🔊';
      cvMuteBtn.title = cvid.muted ? 'Son coupé (cliquer pour activer)' : 'Son activé (cliquer pour couper)';
    });
  }

  const saveBoardBtn = document.getElementById('save-board-btn');
  if (saveBoardBtn && typeof window.__BOARD_ID__ === 'number') {
    saveBoardBtn.addEventListener('click', async () => {
      const sname = document.getElementById('sname');
      const payload = {
        id: window.__BOARD_ID__,
        name: sname ? sname.value.trim() || 'Shooting sans titre' : 'Shooting sans titre',
        state: getStateForSave()
      };
      try {
        const res = await fetch('index.php?action=save-board', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json().catch(() => ({}));
        if (data.ok) {
          saveBoardBtn.textContent = 'Sauvegardé ✓';
          setTimeout(() => { saveBoardBtn.textContent = 'Sauvegarder'; }, 2000);
        } else {
          alert(data.error || 'Erreur lors de la sauvegarde.');
        }
      } catch (err) {
        alert('Erreur lors de la sauvegarde.');
      }
    });
  }
}
