# Shooting Board

Application pour gérer des plans vidéo : import de reels (Instagram ou locaux), découpe avec marqueurs IN/OUT, et board par catégories.

## Structure du projet

```
antoreli/
├── index.html          # Point d'entrée HTML
├── css/
│   └── styles.css      # Styles
├── js/
│   ├── app.js          # Initialisation
│   ├── model.js        # État et opérations sur les données (MVC - Model)
│   ├── view.js         # Rendu DOM (MVC - View)
│   ├── controller.js   # Logique et événements (MVC - Controller)
│   └── utils.js        # Utilitaires (uid, fmt, esc)
├── .gitignore
└── README.md
```

## Démarrage

### Option 1 : Serveur local (recommandé)

Les modules ES6 nécessitent d’être servis via HTTP. Par exemple :

```bash
# Python 3
python3 -m http.server 8765

# Node.js (npx)
npx serve

# PHP
php -S localhost:8765
```

Puis ouvrir `http://localhost:8765` dans le navigateur.

### Option 2 : Fichier local

Sur certains navigateurs (Chrome, Edge), `index.html` peut être ouvert directement avec `file://`. Firefox et Safari bloquent en général les modules ES6 en `file://`.

## Fonctionnalités

1. **Reels** : Import de vidéos locales (MP4, MOV, WebM), création de catégories, lien vers SnapInsta pour télécharger des reels Instagram.
2. **Découpe** : Lecture vidéo avec marqueurs IN/OUT, ajout de plans nommés par catégorie, réordonnancement par glisser-déposer.
3. **Board** : Visualisation des plans par catégorie, filtre, détection de noms similaires, prévisualisation en modal avec validation.

## Persistance des données

Les **catégories** et **clips** (noms, IN/OUT, catégories, validations) sont sauvegardés automatiquement dans `localStorage`. Les vidéos ne sont pas stockées (limitation des URLs blob). Après rechargement, réimporte les mêmes fichiers pour réactiver la lecture des clips ; la correspondance se fait par nom de fichier.

## Technologies

- Vanilla JavaScript (ES6 modules)
- CSS variables
- localStorage (persistance)
- Pas de dépendances externes
# antoreli.board
