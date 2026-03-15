<?php
$boardId = isset($boardId) ? (int) $boardId : null;
$boardName = isset($boardName) ? $boardName : 'Shooting sans titre';
$state = isset($state) ? $state : null;
$reels = isset($reels) ? $reels : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($boardName) ?> — Shooting Board</title>
  <link rel="stylesheet" href="/css/styles.css"/>
  <script>
    window.__BOARD_ID__ = <?= $boardId ? (int) $boardId : 'null' ?>;
    window.__BOARD_NAME__ = <?= json_encode($boardName) ?>;
    window.__BOARD_STATE__ = <?= $state !== null ? json_encode($state) : 'null' ?>;
    window.__BOARD_REELS__ = <?= json_encode($reels) ?>;
  </script>
</head>
<body>
  <div id="app">
    <div class="topbar">
      <span class="logo">Shooting Board</span>
      <a href="index.php?action=dashboard" class="btn btn-sm">Tableau de bord</a>
      <input class="sname" id="sname" value="<?= htmlspecialchars($boardName) ?>" aria-label="Nom du projet"/>
      <button type="button" class="btn btn-sm btn-w" id="save-board-btn">Sauvegarder</button>
      <a href="index.php?action=logout" class="btn btn-sm" style="margin-left: auto;">Déconnexion</a>
    </div>

    <div class="nav">
      <button class="nb on" id="nb-import" type="button">1 — Reels</button>
      <button class="nb" id="nb-cut" type="button">2 — Découpe</button>
      <button class="nb" id="nb-board" type="button">3 — Board</button>
    </div>

    <!-- PAGE IMPORT -->
    <div class="page on" id="page-import">
      <div class="two">
        <div class="card">
          <div class="ctitle">Catégories de plans</div>
          <div class="cats-row" id="cats-row"></div>
          <div class="flex-row">
            <input type="text" id="cat-inp" placeholder="Nouvelle catégorie…" class="flex-1"/>
            <button class="btn btn-sm" id="addcat-btn" type="button">+ Ajouter</button>
          </div>
        </div>
        <div class="card">
          <div class="ctitle">Reel Instagram → MP4</div>
          <p class="snap-desc">Colle l'URL → SnapInsta te donne le MP4 à télécharger.</p>
          <div class="flex-row">
            <input type="text" id="snap-url" placeholder="https://www.instagram.com/reel/…" class="flex-1"/>
            <button class="btn btn-sm btn-w" id="snap-btn" type="button">Ouvrir ↗</button>
          </div>
          <div class="hint">snapinsta.app · gratuit · 2 clics</div>
        </div>
      </div>

      <div class="card">
        <div class="ctitle">Import vidéo locale</div>
        <label class="upload-label" for="finp">
          <div class="vmsg-icon" style="margin-bottom:8px">+</div>
          <div>Clique ici pour importer une ou plusieurs vidéos</div>
          <div class="hint">MP4 · MOV · WebM</div>
        </label>
        <input type="file" id="finp" accept="video/*" multiple class="input-hidden"/>
      </div>

      <div class="card">
        <div class="flex-between" style="margin-bottom:12px">
          <div class="ctitle" style="margin:0">Reels importés (<span id="rcount">0</span>)</div>
          <button class="btn btn-sm btn-w" data-goto="cut" type="button">Découper →</button>
        </div>
        <div id="rlist"><div class="empty">Aucun reel importé.</div></div>
      </div>
    </div>

    <!-- PAGE CUT -->
    <div class="page" id="page-cut">
      <div class="tabs" id="reel-tabs"></div>
      <div class="pwrap">
        <div class="pscreen">
          <div id="vmsg">
            <div class="vmsg-icon">▶</div>
            <div>Importe et sélectionne un reel</div>
          </div>
          <video id="vid" style="display:none" preload="auto" playsinline muted></video>
        </div>
        <div class="pctrl">
          <button class="ppbtn" id="pbtn" type="button">▶</button>
          <button class="ppbtn" id="mute-btn" type="button" title="Son coupé (cliquer pour activer)" aria-label="Basculer le son">🔇</button>
          <div class="tc" id="tc">0:00 / 0:00</div>
          <div class="pbar" id="pbar">
            <div class="pbg"></div>
            <div class="pfill" id="pfill"></div>
            <div class="pmarks" id="pmarks"></div>
            <div class="phead" id="phead"></div>
          </div>
          <select class="rate-select w-64" aria-label="Vitesse de lecture">
            <option value="0.25">×0.25</option>
            <option value="0.5">×0.5</option>
            <option value="1" selected>×1</option>
            <option value="1.5">×1.5</option>
            <option value="2">×2</option>
          </select>
        </div>
      </div>
      <div class="toolbar">
        <button class="mbtn" id="in-btn" type="button">[ IN&nbsp;<span id="in-v">--</span></button>
        <button class="mbtn" id="out-btn" type="button">OUT ]&nbsp;<span id="out-v">--</span></button>
        <input type="text" id="cname" placeholder="Nom du plan…" class="flex-1 min-w-130"/>
        <select id="ccat" class="min-w-130"><option value="">— catégorie —</option></select>
        <button class="btn btn-w" id="addclip-btn" type="button">+ Ajouter plan</button>
      </div>
      <div class="clist" id="clist"></div>
      <div style="display:flex;justify-content:flex-end;margin-top:1rem">
        <button class="btn btn-w" data-goto="board" type="button">Voir le board →</button>
      </div>
    </div>

    <!-- PAGE BOARD -->
    <div class="page" id="page-board">
      <div class="bbar">
        <div class="fps" id="fps"></div>
        <button class="btn btn-sm" id="detectsim-btn" type="button">Détecter similaires</button>
      </div>
      <div class="bgrid" id="bgrid"></div>
    </div>
  </div>

  <!-- VIEWER MODAL -->
  <div class="vbg" id="vbg" aria-modal="true" aria-hidden="true">
    <div class="vmodal" id="vmodal">
      <div class="vhd">
        <div>
          <div class="vcat" id="vcat-lbl"></div>
          <div class="vn" id="vname-lbl"></div>
          <div class="vcnt" id="vcnt-lbl"></div>
        </div>
        <button class="vcls" id="vcls" type="button" aria-label="Fermer">×</button>
      </div>
      <div class="vscreen">
        <div id="cvmsg"></div>
        <video id="cvid" preload="auto" playsinline muted></video>
        <button class="varr vpl" type="button" aria-label="Précédent">‹</button>
        <button class="varr vpr" type="button" aria-label="Suivant">›</button>
      </div>
      <div class="vctrl">
        <button class="cpbtn" id="cpbtn" type="button">▶</button>
        <button class="cpbtn" id="cv-mute-btn" type="button" title="Son coupé (cliquer pour activer)" aria-label="Basculer le son">🔇</button>
        <div class="ctc" id="ctc">0.0s / 0.0s</div>
        <div class="cbar" id="cbar">
          <div class="cbarbg"></div>
          <div class="cbarfill" id="cbarfill"></div>
          <div class="cbarhead" id="cbarhead"></div>
        </div>
        <select class="rate-select v w-60" aria-label="Vitesse de lecture">
          <option value="0.25">×0.25</option>
          <option value="0.5">×0.5</option>
          <option value="1" selected>×1</option>
          <option value="1.5">×1.5</option>
          <option value="2">×2</option>
        </select>
      </div>
      <div class="vft">
        <div class="vdots" id="vdots"></div>
        <button class="btn btn-sm" id="vvalbtn" type="button">✓ Valider</button>
      </div>
    </div>
  </div>

  <script type="module" src="/js/app.js"></script>
</body>
</html>
