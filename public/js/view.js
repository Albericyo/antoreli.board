import { state, getActiveReel, getCatClips, catMatch } from './model.js';
import { fmt, fmtS, esc } from './utils.js';

let raf = null;
let craf = null;
let cClip = null;

export function renderCats() {
  const el = document.getElementById('cats-row');
  if (!el) return;
  el.innerHTML = state.cats
    .map(
      (c) =>
        `<div class="cpill">${esc(c)}<span class="x" data-action="rmCat" data-cat="${esc(c)}">×</span></div>`
    )
    .join('');
  const sel = document.getElementById('ccat');
  if (sel) {
    sel.innerHTML =
      '<option value="">— catégorie —</option>' +
      state.cats.map((c) => `<option value="${esc(c)}">${esc(c)}</option>`).join('');
  }
}

export function renderReels() {
  const countEl = document.getElementById('rcount');
  const listEl = document.getElementById('rlist');
  if (!countEl || !listEl) return;
  countEl.textContent = state.reels.length;
  if (!state.reels.length) {
    listEl.innerHTML = '<div class="empty">Aucun reel importé.</div>';
    return;
  }
  listEl.innerHTML = state.reels
    .map((r) => {
      const n = state.clips.filter((c) => c.rid == r.id).length;
      return `<div class="ritem ${state.activeId == r.id ? 'sel' : ''}" data-action="selReel" data-id="${r.id}">
        <div class="rthumb">🎬</div>
        <div class="rinfo"><div class="rname">${esc(r.name)}</div><div class="rmeta">${n} plan${n !== 1 ? 's' : ''}</div></div>
        <span class="del" data-action="rmReel" data-id="${r.id}">×</span>
      </div>`;
    })
    .join('');
}

export function renderTabs() {
  const el = document.getElementById('reel-tabs');
  if (!el) return;
  if (!state.reels.length) {
    el.innerHTML = "<div style='font-size:12px;color:var(--t3)'>Importe des reels d'abord.</div>";
    return;
  }
  el.innerHTML = state.reels
    .map(
      (r) =>
        `<div class="tab ${state.activeId == r.id ? 'on' : ''}" data-action="switchTab" data-id="${r.id}">${esc(r.name)}</div>`
    )
    .join('');
}

export function renderCList() {
  const el = document.getElementById('clist');
  if (!el) return;
  const arr = state.clips.filter((c) => c.rid == state.activeId);
  if (!arr.length) {
    el.innerHTML = '<div class="empty">Marque IN → OUT puis ajoute un plan.</div>';
    return;
  }
  el.innerHTML = arr
    .map((c) => {
      const opts = state.cats
        .map((cat) => `<option value="${esc(cat)}"${c.cat === cat ? ' selected' : ''}>${esc(cat)}</option>`)
        .join('');
      const catForViewer = c.cat || 'Sans catégorie';
      return `<div class="crow" draggable="true" data-action="openViewer" data-cat="${esc(catForViewer)}" data-clip-id="${c.id}"
        data-drag-id="${c.id}">
        <span class="dh" data-action="dragHandle">⠿</span>
        <div class="ctime">${fmt(c.in)} → ${fmt(c.out)}</div>
        <div class="clbl"><input value="${esc(c.name)}" data-action="clipName" data-id="${c.id}"/></div>
        <div data-action="clipCatWrap"><select data-action="clipCat" data-id="${c.id}"><option value="">—</option>${opts}</select></div>
        <span class="del" data-action="rmClip" data-id="${c.id}">×</span>
      </div>`;
    })
    .join('');
}

export function renderMarks() {
  const vid = document.getElementById('vid');
  const pmarks = document.getElementById('pmarks');
  if (!vid || !vid.duration || !pmarks) return;
  const clips = state.clips.filter((c) => c.rid == state.activeId);
  pmarks.innerHTML = clips
    .map((c) => {
      const l = (c.in / vid.duration) * 100;
      const w = Math.max(0.5, ((c.out - c.in) / vid.duration) * 100);
      return `<div class="mseg" style="left:${l}%;width:${w}%"></div>`;
    })
    .join('');
}

export function resetMarksUI() {
  const inV = document.getElementById('in-v');
  const outV = document.getElementById('out-v');
  const inBtn = document.getElementById('in-btn');
  const outBtn = document.getElementById('out-btn');
  if (inV) inV.textContent = '--';
  if (outV) outV.textContent = '--';
  if (inBtn) inBtn.classList.remove('set');
  if (outBtn) outBtn.classList.remove('set');
}

export function updateMarksUI() {
  const inV = document.getElementById('in-v');
  const outV = document.getElementById('out-v');
  const inBtn = document.getElementById('in-btn');
  const outBtn = document.getElementById('out-btn');
  if (state.inPt !== null && inV) inV.textContent = fmt(state.inPt);
  if (state.outPt !== null && outV) outV.textContent = fmt(state.outPt);
  if (inBtn) inBtn.classList.toggle('set', state.inPt !== null);
  if (outBtn) outBtn.classList.toggle('set', state.outPt !== null);
}

export function loadVid() {
  const vid = document.getElementById('vid');
  const msg = document.getElementById('vmsg');
  if (!vid || !msg) return;
  const r = getActiveReel();
  if (!r) {
    vid.pause();
    vid.removeAttribute('src');
    vid.style.display = 'none';
    msg.style.display = 'flex';
    msg.innerHTML = '<div class="vmsg-icon">▶</div><div>Importe et sélectionne un reel</div>';
    stopAnim();
    return;
  }
  msg.style.display = 'none';
  vid.style.display = 'block';
  if (vid.dataset.src !== r.src) {
    vid.dataset.src = r.src;
    vid.src = r.src;
    vid.muted = true;
    vid.load();
    const muteBtn = document.getElementById('mute-btn');
    if (muteBtn) {
      muteBtn.textContent = '🔇';
      muteBtn.title = 'Son coupé (cliquer pour activer)';
    }
  }
  stopAnim();
  updateBar();
}

export function updateBar() {
  const vid = document.getElementById('vid');
  const pfill = document.getElementById('pfill');
  const phead = document.getElementById('phead');
  const tc = document.getElementById('tc');
  if (!vid || !vid.duration) return;
  const pct = (vid.currentTime / vid.duration) * 100;
  if (pfill) pfill.style.width = pct + '%';
  if (phead) phead.style.left = pct + '%';
  if (tc) tc.textContent = fmtS(vid.currentTime) + ' / ' + fmtS(vid.duration);
  renderMarks();
}

export function startAnim() {
  stopAnim();
  function lp() {
    updateBar();
    raf = requestAnimationFrame(lp);
  }
  raf = requestAnimationFrame(lp);
}

export function stopAnim() {
  if (raf) {
    cancelAnimationFrame(raf);
    raf = null;
  }
}

export function renderBoard() {
  renderFps();
  const el = document.getElementById('bgrid');
  if (!el) return;
  const allCats = state.cats.concat(['Sans catégorie']);
  const vis = state.filter === 'all' ? allCats : [state.filter];
  el.innerHTML = vis
    .map((cat) => {
      const arr = state.clips.filter((c) => catMatch(c, cat));
      const dn = arr.filter((c) => c.done).length;
      const catEsc = esc(cat);
      let html =
        `<div class="bcol" data-cat="${catEsc}"><div class="bcolhd"><div><div class="bcoltitle">${catEsc}</div>` +
        `<div class="bcolcount">${arr.length} plan${arr.length !== 1 ? 's' : ''}${dn ? ' · ' + dn + ' validé' + (dn > 1 ? 's' : '') : ''}</div></div>` +
        `<button class="btn btn-sm" data-action="openViewer" data-cat="${catEsc}" data-clip-id="" ${arr.length ? '' : 'disabled style="opacity:.4"'}>▶ Voir tout</button></div>`;
      if (arr.length) {
        html += arr
          .map(
            (c) =>
              `<div class="bclip ${c.done ? 'done' : ''}" draggable="true" data-action="openViewer" data-cat="${esc(c.cat || 'Sans catégorie')}" data-clip-id="${c.id}">
                <div class="btime">${fmt(c.in)}<br/>${fmt(c.out)}</div>
                <div class="binfo"><div class="bname">${c.done ? '<span style="color:var(--green)">✓ </span>' : ''}${esc(c.name)}${c.sim ? ' <span class="badge b-warn">similaire</span>' : ''}</div>
                <div class="bsrc">${esc(c.rname)}</div></div>
                <span class="del" data-action="rmClipBoard" data-id="${c.id}">×</span>
              </div>`
          )
          .join('');
      } else {
        html += '<div class="empty">Aucun plan</div>';
      }
      html += '</div>';
      return html;
    })
    .join('');
}

export function renderFps() {
  const el = document.getElementById('fps');
  if (!el) return;
  const all = ['all'].concat(state.cats).concat(['Sans catégorie']);
  el.innerHTML = all
    .map(
      (f) =>
        `<div class="fp ${state.filter === f ? 'on' : ''}" data-action="setFilter" data-filter="${esc(f)}">${f === 'all' ? 'Tout' : esc(f)}</div>`
    )
    .join('');
}

export function openViewerModal() {
  document.getElementById('vbg')?.classList.add('open');
}

export function closeViewerModal() {
  const vbg = document.getElementById('vbg');
  const cvid = document.getElementById('cvid');
  if (vbg) vbg.classList.remove('open');
  stopCV();
  if (cvid) {
    cvid.pause();
    cvid.removeAttribute('src');
    cvid.dataset.src = '';
  }
  cClip = null;
}

export function loadCV() {
  const arr = getCatClips(state.vCat);
  const c = arr[state.vIdx];
  const cvid = document.getElementById('cvid');
  const cvmsg = document.getElementById('cvmsg');
  const vcatLbl = document.getElementById('vcat-lbl');
  const vnameLbl = document.getElementById('vname-lbl');
  const vcntLbl = document.getElementById('vcnt-lbl');
  const vvalbtn = document.getElementById('vvalbtn');
  const vmodal = document.getElementById('vmodal');
  const vdots = document.getElementById('vdots');

  if (!c) return;
  cClip = c;

  if (vcatLbl) vcatLbl.textContent = state.vCat;
  if (vnameLbl) vnameLbl.textContent = c.name;
  if (vcntLbl) vcntLbl.textContent = state.vIdx + 1 + ' / ' + arr.length;
  if (vvalbtn) {
    if (c.done) {
      vvalbtn.textContent = '✓ Validé';
      vvalbtn.className = 'btn btn-sm btn-g';
    } else {
      vvalbtn.textContent = '✓ Valider';
      vvalbtn.className = 'btn btn-sm btn-w';
    }
  }
  if (vmodal) vmodal.classList.toggle('done', c.done);
  if (vdots) {
    vdots.innerHTML = arr
      .map(
        (x, i) =>
          `<div class="vdot ${i === state.vIdx ? 'on' : ''} ${x.done ? 'done' : ''}" data-action="vdot" data-idx="${i}" title="${esc(x.name)}"></div>`
      )
      .join('');
  }

  stopCV();
  if (!cvid || !cvmsg) return;

  if (c.rsrc) {
    cvmsg.style.display = 'none';
    cvid.style.display = 'block';
    cvid.muted = true;
    const cvMuteBtn = document.getElementById('cv-mute-btn');
    if (cvMuteBtn) {
      cvMuteBtn.textContent = '🔇';
      cvMuteBtn.title = 'Son coupé (cliquer pour activer)';
    }
    if (cvid.dataset.src !== c.rsrc) {
      cvid.dataset.src = c.rsrc;
      cvid.src = c.rsrc;
      cvid.load();
      cvid.oncanplay = function () {
        cvid.oncanplay = null;
        cvid.currentTime = c.in;
        cvid.play().catch(() => {});
        startCV();
      };
    } else {
      cvid.currentTime = c.in;
      cvid.play().catch(() => {});
      startCV();
    }
  } else {
    cvid.style.display = 'none';
    cvmsg.style.display = 'flex';
    cvmsg.textContent = 'Vidéo non disponible';
  }
  updateCBar();
}

export function updateCBar() {
  if (!cClip) return;
  const cvid = document.getElementById('cvid');
  const cbarfill = document.getElementById('cbarfill');
  const cbarhead = document.getElementById('cbarhead');
  const ctc = document.getElementById('ctc');
  if (!cvid || !cbarfill || !cbarhead || !ctc) return;
  const dur = cClip.out - cClip.in;
  const pos = Math.max(0, Math.min(cvid.currentTime, cClip.out) - cClip.in);
  const pct = dur > 0 ? (pos / dur) * 100 : 0;
  cbarfill.style.width = pct + '%';
  cbarhead.style.left = pct + '%';
  ctc.textContent = pos.toFixed(1) + 's / ' + dur.toFixed(1) + 's';
}

export function startCV() {
  stopCV();
  function lp() {
    const cvid = document.getElementById('cvid');
    if (!cClip || !cvid) return;
    if (cvid.currentTime >= cClip.out) {
      cvid.currentTime = cClip.in;
      cvid.play().catch(() => {});
    }
    updateCBar();
    craf = requestAnimationFrame(lp);
  }
  craf = requestAnimationFrame(lp);
}

export function stopCV() {
  if (craf) {
    cancelAnimationFrame(craf);
    craf = null;
  }
}

export function setPlayBtnLabel(label) {
  const el = document.getElementById('pbtn');
  if (el) el.textContent = label;
}

export function setCVPlayBtnLabel(label) {
  const el = document.getElementById('cpbtn');
  if (el) el.textContent = label;
}
