- mise en cache HTTP côté serveur (pages publiques + API)
- activer gzip/brotli via nginx ou apache
- minifier/concaténer CSS et JS (app.css, scripts TinyMCE)
- charger les images en lazy-load + formats WebP
- configurer un CDN ou au moins cache navigateur long pour /assets
- optimiser requêtes SQL (indexes sur articles.slug, media.id_article)
- utiliser prepared statements réutilisables pour les listings
- ajouter pagination côté API + limiter SELECT *
- surveiller temps de réponse avec logs + metrics (ex: Laravel Telescope/Monolog)
- activer OPCache en production PHP
- nettoyer dépendances TinyMCE inutiles (plugins non utilisés)
- compresser images uploadées et générer vignettes
- ajouter headers de sécurité/cache-control (ETag, Last-Modified)
- mettre en place un worker pour pré-générer les pages les plus vues
- vérifier Lighthouse (perf/accessibilité/SEO) à chaque build



test :
 curl.exe -I http://localhost:8080/site -H "If-None-Match: <ETAG_RÉCUPÉRÉ>"