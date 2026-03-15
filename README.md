# Shooting Board

Application web pour gérer des plans vidéo : import de reels, découpe avec marqueurs IN/OUT, et visualisation par catégories sous forme de board.

---

## Présentation

**Shooting Board** est un outil léger et autonome destiné aux créateurs vidéo qui travaillent avec des reels (Instagram ou locaux). Il permet de :

- **Importer** des vidéos et organiser les plans par catégories
- **Découper** chaque vidéo en segments avec des points IN/OUT précis
- **Visualiser** un board par catégorie pour valider les plans avant tournage ou montage

Une version **PHP MVC** avec authentification est disponible : document root `public/`, login par session, base MySQL. Les métadonnées du board restent en localStorage côté client ; la base sert aux utilisateurs (connexion).

L’application se lance depuis le dossier `public/` (voir « Version PHP » ci-dessous).

---

## Fonctionnalités

| Étape | Description |
|-------|-------------|
| **1 — Reels** | Import de vidéos locales (MP4, MOV, WebM), gestion des catégories, lien vers SnapInsta pour télécharger des reels Instagram |
| **2 — Découpe** | Lecture vidéo, marqueurs IN/OUT, ajout de plans nommés par catégorie, réordonnancement par glisser-déposer |
| **3 — Board** | Affichage des plans par catégorie, filtre, détection de noms similaires, prévisualisation en modal avec validation |

### Détails

- **Import vidéo** : Glisser-déposer ou sélection multiple de fichiers vidéo
- **SnapInsta** : Colle l’URL d’un reel Instagram → ouverture de SnapInsta pour récupérer le MP4
- **Marqueurs IN/OUT** : Clic sur le bouton au moment voulu ou saisie manuelle du temps en secondes
- **Vitesse de lecture** : ×0.25, ×0.5, ×1, ×1.5, ×2
- **Plan similaire** : Détection automatique des plans portant le même nom (risque de doublon)
- **Validation** : Marquer un plan comme validé directement depuis la prévisualisation

---

## Fonctionnement

### Workflow

```
Import Reels → Création de catégories → Découpe (IN/OUT) → Plans nommés → Board par catégorie → Validation
```

1. **Reels** : Créez des catégories (ex. Main levée, Trépied), importez vos vidéos, éventuellement via SnapInsta pour Instagram.
2. **Découpe** : Ouvrez un reel, posez les marqueurs IN et OUT, donnez un nom au plan et choisissez sa catégorie.
3. **Board** : Consultez les plans par catégorie, filtrez, prévisualisez et validez.

### Architecture

L’application suit une architecture **MVC** (Model-View-Controller) en JavaScript vanilla :

| Fichier | Rôle |
|---------|------|
| `model.js` | État global (`cats`, `reels`, `clips`) et logique métier |
| `view.js` | Rendu DOM, mise à jour de l’interface |
| `controller.js` | Gestion des événements et coordination model/view |
| `storage.js` | Persistance des données dans `localStorage` |
| `utils.js` | Utilitaires (uid, formatage temps, échappement HTML) |

### Persistance des données

Les **catégories** et **clips** (noms, IN/OUT, catégories, validations) sont sauvegardés automatiquement dans `localStorage`.

⚠️ Les **vidéos ne sont pas stockées**. Après un rechargement de page, il faut réimporter les mêmes fichiers. La correspondance se fait par nom de fichier.

---

## Démarrage en local

L’application (PHP MVC + login) doit être servie depuis le dossier **`public/`**. Les modules ES6 côté client imposent HTTP (pas `file://`).

### Démarrage (PHP + MySQL)

1. **Créer la base** : importer `database/schema.sql` dans MySQL.
2. **Configurer** : copier `.env.example` en `.env` à la racine du projet puis éditer `.env` (DB_HOST, DB_NAME, DB_USER, DB_PASS).
3. **Créer un utilisateur** (CLI) :
   ```bash
   php database/create_user.php votre@email.com VotreMotDePasse
   ```
4. **Lancer le serveur** en prenant `public/` comme racine :
   ```bash
   cd public && php -S localhost:8765
   ```
   Ou configurer Apache/Nginx avec document root = `public/`.
5. **Ouvrir** `http://localhost:8765` → page de login, puis tableau de bord.

**Tableau de bord** : liste des shooting boards, création, ouverture, marquer terminé, supprimer.  
Routes : `?action=login`, `?action=logout`, `?action=dashboard` (accueil après login), `?action=board&id=X` (éditer un board), `?action=new-board` (POST), `?action=delete-board`, `?action=toggle-finished`.

---

## Structure du projet

```
antoreli/
├── public/                 # Racine web (document root)
│   ├── index.php           # Front controller PHP (login + board)
│   ├── css/styles.css
│   └── js/                 # App board (app.js, model, view, controller, storage, utils)
├── src/
│   ├── Controller/         # AuthController, BoardController
│   ├── Model/              # User, Board
│   ├── View/               # auth/login.php, board/dashboard.php, board/index.php
│   ├── Core/               # Router, Database, Session
│   └── config/database.php
├── database/
│   ├── schema.sql          # BDD : users, boards
│   └── create_user.php     # CLI : créer un utilisateur
├── .env.example            # Exemple pour .env (à la racine)
└── README.md
```

---

## Technologies

- **JavaScript** (ES6 modules, vanilla)
- **CSS** (variables, mise en page moderne)
- **localStorage** pour la persistance
- **Aucune dépendance externe**

---

## Formats vidéo supportés

MP4 (recommandé H.264), MOV, WebM. En cas d’erreur de lecture, privilégier le MP4 H.264.
