# Lancement des conteneurs
- copier (ou adapter) le fichier `.env` puis executer `docker compose up -d --build` depuis le dossier `Site`
- le service `web` attend que MySQL soit pret avant de demarrer (healthcheck)

# Initialisation base de donnees
- les fichiers `BDD/script/script.sql` (structure) et `BDD/data/data-natiora-2803203.sql` (donnees) sont injectes automatiquement dans `site_actualite` au premier demarrage du conteneur MySQL
- pour rejouer l'initialisation, supprimer le volume `siteactualite_site_mysql_data` (`docker volume rm siteactualite_site_mysql_data`) puis relancer `docker compose up`
- commande utile pour se connecter : `docker exec -it siteactualite-db mysql -u root -proot site_actualite`