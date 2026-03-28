Tu es un expert en développement web PHP et SEO.

Je crée un site d’information sur la guerre en Iran avec les contraintes suivantes :

---

# 1️⃣ Back-office : TinyDocs

* **TinyDocs** sera utilisé comme éditeur WYSIWYG pour saisir les articles.
* Les rédacteurs saisissent du texte, TinyDocs génère du **contenu HTML propre** (`<p>`, `<h1>`, `<strong>`, `<em>`, etc.).
* Le contenu doit être stocké tel quel dans la table `articles` (`content LONGTEXT`).
* Upload d’images possible via TinyDocs 

# 2️⃣ Front-office

* PHP récupère le contenu HTML depuis la base et l’affiche tel quel.
* Les balises HTML sont interprétées par le navigateur.
* Les titres des articles (`<h1>`) et meta descriptions sont générés automatiquement depuis le contenu ou les champs dédiés.
* Les slugs doivent être créés automatiquement depuis le titre pour générer des URLs SEO-friendly.

# 3️⃣ SEO et URL Rewriting

* Utiliser **.htaccess** pour réécrire les URLs dynamiques en URLs SEO-friendly.
* Exemples d’URL réécrites :

```text
/economie/guerre-iran-123-5.html
```

* Exemple de `.htaccess` fourni :

```apache
Options +FollowSymlinks
RewriteEngine on
RewriteRule ^/?./?.-([_a-z0-9])-([_a-z0-9]).neymar$ pages/modules.php?id=$1&idcat=$2 [L]
RewriteRule ^/?./?.-([_a-z0-9])-([_a-z0-9]).neymars$ pages/test.php?id=$1&idcat=$2 [L]
```

* URL Rewriting = transformer les URLs dynamiques PHP en URLs lisibles et SEO-friendly.

# 4️⃣ Workflow général

1. Saisie dans TinyDocs → contenu HTML
2. Insertion en base  via PHP
3. Affichage front-office 
4. URLs SEO avec slug + ID + catégorie
5. Upload d’images 

# 5️⃣ Architecture projet (PHP brut)

* `/site`
  * `index.php` : page d’accueil
  * `.htaccess` : URL rewriting
  * `/pages` :  layout/header.php, layout/footer.php, article.php, home.php…
  * `/config/db.php` : connexion MySQL
  * `/helpers` : slug.php, url.php  et kes autres fonctions (on peut mettre dans des pakcages si necesaires _> article , category) ....
  * `/assets` : css/, js/, images/
  * `/uploads` : images TinyDocs
  * dockerfile
  * docker-yml


# 6️⃣ Technique et bonnes pratiques

* PHP brut + mysqli avec requêtes préparées
* Fonctions helper : `getUrl()`, `createSlug()`
* Protection XSS et validation du contenu HTML
* Déploiement Docker : PHP + Apache + MySQL
* Tout doit fonctionner avec `docker compose up -d`

# 7️⃣ À générer

1. La structure complète du projet `/site`
2. un petit test si la connexion docker-> bae -> affichage fonctionne

---

🎯 **Objectif final** :
Avoir une base solide pour un site d’information SEO-friendly, similaire à Le Monde, en PHP brut, avec TinyDocs et Docker.
