# CyberGames

Application web locale de gestion de tournois e-sport, réalisée pour la mission 1 du BTS SIO.

## Fonctionnalités

- Création de compte, connexion et déconnexion des utilisateurs.
- Consultation des tournois à venir et passés.
- Inscription aux tournois pendant la période prévue, dans la limite des places disponibles.
- Espace d'administration pour créer, modifier et supprimer des utilisateurs et des tournois.

## Technologies

- PHP
- HTML et CSS
- JavaScript pour les onglets et les confirmations dans l'administration
- MySQL

## Prérequis

- Un environnement local Apache, PHP et MySQL, par exemple UniServer.
- PHP avec PDO MySQL activé.

## Installation locale

1. Placez le projet dans le répertoire web de votre environnement local, par exemple `www/CyberGames Bloc3`.
2. Démarrez Apache et MySQL.
3. Dans phpMyAdmin, créez une base de données nommée `cybergames`, avec l'encodage `utf8mb4`.
4. Importez [`database/cybergames-db_2026-10-15.sql`](database/cybergames-db_2026-10-15.sql) dans cette base. Ce fichier contient la structure des tables et leurs relations ; il ne contient pas de comptes ni de données de démonstration.
5. Créez à la racine du projet un fichier local nommé `config.PHP` avec les paramètres de connexion de votre MySQL :

   ```php
   <?php
   $host = 'localhost';
   $dbname = 'cybergames';
   $username = 'VOTRE_UTILISATEUR_MYSQL';
   $password = 'VOTRE_MOT_DE_PASSE_MYSQL';
   $charset = 'utf8mb4';

   $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
   $options = [
       PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
   ];

   try {
       $pdo = new PDO($dsn, $username, $password, $options);
   } catch (PDOException $exception) {
       error_log($exception->getMessage());
       die('La connexion à la base de données est indisponible.');
   }
   ```

   Remplacez les valeurs d'exemple par les paramètres de votre installation locale. Ne publiez pas ce fichier ni vos identifiants.

6. Ouvrez `http://localhost/CyberGames%20Bloc3/` dans votre navigateur (adaptez le chemin au nom du dossier choisi).
7. Créez un compte depuis la page d'inscription. Les comptes créés par le formulaire ont le rôle utilisateur par défaut (`permission = 1`). Pour attribuer le rôle administrateur (`permission = 2`) à un compte de test, modifiez sa valeur `permission` dans la table `users` depuis phpMyAdmin.

## Schémas et base de données

- [Schéma de la base de données](docs/schema-bdd.drawio)
- [Schéma du parcours du site](docs/schema-site.drawio)
- [Export SQL de la structure](database/cybergames-db_2026-10-15.sql)

Les schémas Draw.io peuvent être ouverts et modifiés sur [diagrams.net](https://app.diagrams.net/).

## Structure principale

```text
actions/       Traitements des inscriptions aux tournois et actions d'administration
assets/        CSS et JavaScript
database/      Export SQL de la structure de la base
docs/          Schémas Draw.io
includes/      Authentification, navigation, styles et fonctions partagées
admin.PHP      Administration des utilisateurs et tournois
dashboard.PHP  Espace utilisateur
index.PHP      Accueil et consultation des tournois
login.PHP      Connexion
logout.PHP     Déconnexion
register.PHP   Création de compte
```
