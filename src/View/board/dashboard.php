<?php
$boards = $boards ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tableau de bord — Shooting Board</title>
  <link rel="stylesheet" href="css/styles.css"/>
</head>
<body>
  <div id="app">
    <div class="topbar">
      <span class="logo">Shooting Board</span>
      <a href="index.php?action=logout" class="btn btn-sm" style="margin-left: auto;">Déconnexion</a>
    </div>

    <div class="card">
      <div class="ctitle">Tableau de bord</div>
      <p class="hint" style="margin-bottom: 1rem;">Créez un shooting board ou ouvrez-en un existant.</p>
      <form method="post" action="index.php?action=new-board" class="flex-row" style="margin-bottom: 1.25rem;">
        <input type="text" name="name" placeholder="Nom du board…" value="Shooting sans titre" class="flex-1"/>
        <button type="submit" class="btn btn-w">+ Nouveau shooting board</button>
      </form>

      <div class="ctitle" style="margin-bottom: 10px;">Mes shooting boards</div>
      <?php if (empty($boards)): ?>
        <div class="empty">Aucun board. Créez-en un avec le formulaire ci-dessus.</div>
      <?php else: ?>
        <div class="clist">
          <?php foreach ($boards as $b): ?>
            <?php
            $date = date('d/m/Y H:i', strtotime($b['created_at']));
            $done = !empty($b['finished']);
            ?>
            <div class="bclip <?= $done ? 'done' : '' ?>" style="cursor: default; justify-content: space-between; align-items: center;">
              <div class="btime"><?= htmlspecialchars($date) ?></div>
              <div class="binfo" style="flex: 1;">
                <div class="bname"><?= htmlspecialchars($b['name']) ?></div>
                <?php if ($done): ?>
                  <div class="bsrc">Terminé</div>
                <?php endif; ?>
              </div>
              <a href="index.php?action=board&id=<?= (int) $b['id'] ?>" class="btn btn-sm btn-w">Ouvrir</a>
              <a href="index.php?action=toggle-finished&id=<?= (int) $b['id'] ?>" class="btn btn-sm <?= $done ? '' : 'btn-g' ?>"><?= $done ? 'En cours' : 'Terminé' ?></a>
              <a href="index.php?action=delete-board&id=<?= (int) $b['id'] ?>" class="btn btn-sm" onclick="return confirm('Supprimer ce board ?');">Supprimer</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div style="margin-top: 1rem;">
      <a href="index.php?action=dashboard" class="btn btn-sm">Rafraîchir</a>
    </div>
  </div>
</body>
</html>
