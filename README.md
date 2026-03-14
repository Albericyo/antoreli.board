# Shooting Board

Application web pour gérer des plans vidéo : import de reels, découpe avec marqueurs IN/OUT, et visualisation par catégories sous forme de board.

---

## Présentation

**Shooting Board** est un outil léger et autonome destiné aux créateurs vidéo qui travaillent avec des reels (Instagram ou locaux). Il permet de :

- **Importer** des vidéos et organiser les plans par catégories
- **Découper** chaque vidéo en segments avec des points IN/OUT précis
- **Visualiser** un board par catégorie pour valider les plans avant tournage ou montage

L’application fonctionne entièrement dans le navigateur, sans backend ni compte utilisateur. Les métadonnées (catégories, clips, validations) sont conservées dans le stockage local du navigateur.

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

L’application utilise des **modules ES6** (`import`/`export`). Le navigateur impose qu’ils soient servis via HTTP (pas `file://`).

### 1. Cloner ou récupérer le projet

```bash
cd /srv/antoreli
```

### 2. Lancer un serveur HTTP local

**Python 3 :**
```bash
python3 -m http.server 8765
```

**Node.js (via npx) :**
```bash
npx serve
```

**PHP :**
```bash
php -S localhost:8765
```

### 3. Ouvrir dans le navigateur

```
http://localhost:8765
```

Avec `serve` (Node.js), le port par défaut est souvent 3000 : `http://localhost:3000`.

### Alternative (fichier local)

Sur certains navigateurs (Chrome, Edge), vous pouvez ouvrir `index.html` directement avec `file://`.  
Firefox et Safari bloquent généralement l’exécution des modules ES6 en `file://`, le serveur local reste donc recommandé.

---

## Structure du projet

```
antoreli/
├── index.html          # Point d'entrée HTML
├── app.html            # Variante (si présente)
├── css/
│   └── styles.css      # Feuilles de style
├── js/
│   ├── app.js          # Initialisation (chargement, bindEvents)
│   ├── model.js        # État et opérations (MVC — Model)
│   ├── view.js         # Rendu DOM (MVC — View)
│   ├── controller.js   # Événements et logique (MVC — Controller)
│   ├── storage.js      # Persistance localStorage
│   └── utils.js        # Utilitaires
├── .gitignore
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
