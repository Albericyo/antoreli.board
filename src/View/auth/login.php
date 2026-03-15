<?php
$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> — Shooting Board</title>
  <link rel="stylesheet" href="/css/styles.css"/>
  <style>
    .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .login-card { width: 100%; max-width: 360px; }
    .login-card .card { margin-bottom: 0; }
    .login-error { font-size: 12px; color: #f87171; margin-bottom: 12px; }
  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="card">
        <div class="ctitle">Shooting Board</div>
        <p class="hint" style="margin-bottom: 1rem;">Connectez-vous pour accéder à l'application.</p>
        <?php if (!empty($error)): ?>
          <div class="login-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="index.php?action=do-login" class="flex-row" style="flex-direction: column; gap: 12px;">
          <input type="email" name="email" placeholder="Email" required autofocus autocomplete="email" class="flex-1"/>
          <input type="password" name="password" placeholder="Mot de passe" required autocomplete="current-password" class="flex-1"/>
          <button type="submit" class="btn btn-w" style="width: 100%;">Se connecter</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
