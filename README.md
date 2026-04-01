<img src="https://alexis-sgl.fr/wp-content/uploads/2026/01/logo_CESI_projet_etudiant_NB.png" alt="Logo CESI" width="100" align="right" />
<br><br>

# 🎓 Stage4All — Plateforme de recherche de stages

> Projet web réalisé dans le cadre du bloc Développement Web de formation CESI.  
> Application web de mise en relation entre étudiants et entreprises proposant des offres de stage.

---

## 🔧 Technologies

![PHP](https://img.shields.io/badge/Backend-PHP_8.3-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=flat-square&logo=mysql)![Apache](https://img.shields.io/badge/Server-Apache-D22128?style=flat-square&logo=apache)
![HTML5](https://img.shields.io/badge/Frontend-HTML5-E34F26?style=flat-square&logo=html5)
![CSS3](https://img.shields.io/badge/Frontend-CSS3-1572B6?style=flat-square&logo=css3)
![JavaScript](https://img.shields.io/badge/Frontend-JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![Twig](https://img.shields.io/badge/Template-Twig-8AA356?style=flat-square)
![Composer](https://img.shields.io/badge/Package_Manager-Composer-885630?style=flat-square&logo=composer)
![PHPUnit](https://img.shields.io/badge/Tests-PHPUnit_12-4A5B7D?style=flat-square&logo=phpunit)

---

## 📋 Sommaire

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Structure du projet](#structure-du-projet)
- [Rôles et permissions](#rôles-et-permissions)
- [Tests unitaires](#tests-unitaires)
- [Équipe](#équipe)

---

## Présentation

**Stage4All** est une application web permettant aux étudiants du CESI de rechercher des offres de stage, de postuler directement en ligne et de gérer leurs candidatures. Les pilotes de promotion peuvent suivre les candidatures de leurs élèves, et les administrateurs disposent d'un accès complet à la gestion de la plateforme.

---

## Fonctionnalités

### 🔐 Gestion des accès
- Authentification par email / mot de passe (hashé en BCRYPT)
- Système de rôles et permissions granulaires
- Sessions sécurisées

### 🏢 Gestion des entreprises
- Recherche multicritères (nom, ville, secteur)
- Fiche détaillée avec offres liées et évaluations
- Création, modification, suppression (avec archivage si offres liées)
- Évaluation des entreprises (note de 0 à 5)

### 📄 Gestion des offres de stage
- Recherche par mots-clés, ville, durée, secteur
- Filtres de durée : < 2 mois / 2-3 mois / 4-6 mois / 6-12 mois / > 1 an
- Tri par date de publication
- Création avec compétences (existantes ou nouvelles)
- Archivage automatique si candidatures existantes
- Tableau de bord statistiques (SFx11)

### 👥 Gestion des utilisateurs
- Création / modification / suppression de comptes Pilote et Étudiant
- Filtrage par campus, promotion, rôle
- Pagination des résultats

### 📬 Candidatures
- Dépôt de candidature avec CV (PDF) et lettre de motivation
- Historique des candidatures pour l'étudiant
- Suivi des candidatures des élèves pour le pilote

### ⭐ Wish-list
- Ajout / retrait d'offres en favoris
- Consultation de la wish-list personnelle

---

## Stack technique

| Composant     | Technologie                    |
|---------------|-------------------------------|
| Serveur       | Apache                        |
| Backend       | PHP 8.3 (POO, PSR-12)         |
| Frontend      | HTML5 / CSS3 / JavaScript     |
| Templates     | Twig                          |
| Base de données | MySQL / MariaDB             |
| Tests         | PHPUnit 12                    |
| Dépendances   | Composer                      |

---

## Architecture

Le projet suit une architecture **MVC stricte** :

```
.
├── README.md
├── static/                    ← Fichiers statiques (Assets)
│   ├── css/                   ← Feuilles de style
│   ├── images/                ← Images statiques
│   └── js/                    ← Scripts JavaScript frontend
│
└── site/                      ← Cœur de l'application web
    ├── composer.json
    ├── composer.lock
    ├── config/                ← Configuration et base de données
    │   ├── db.php             ← Fichier de connexion à la BDD
    │   ├── donnees.sql        ← Données d'initialisation
    │   └── structure.sql      ← Structure des tables (schéma)
    │
    ├── public/                ← Document root Apache
    │   ├── index.php          ← Point d'entrée unique (Front Controller)
    │   ├── manifest.json      ← Manifeste de l'application (PWA)
    │   ├── robots.txt         ← Règles d'indexation
    │   ├── sitemap.xml        ← Plan du site
    │   └── sw.js              ← Service Worker (PWA)
    │
    ├── src/                   ← Code source PHP (Controllers, Models, Utilities)
    ├── templates/             ← Vues Twig (.twig.html)
    ├── tests/                 ← Tests unitaires PHPUnit
    │   ├── EntrepriseModelTest.php
    │   ├── OffreModelTest.php
    │   └── UtilisateurModelTest.php
    │
    ├── uploads/cv/            ← Fichiers téléversés (CV)
    └── vendor/                ← Dépendances Composer
```

---

## Installation

### Prérequis

- PHP >= 8.1
- MySQL
- Apache avec `mod_rewrite` activé
- Composer

### 1. Cloner le dépôt

```bash
git clone [https://github.com/votre-groupe/stage4all.git](https://github.com/votre-groupe/stage4all.git)
cd stage4all/site
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

Créez un fichier `.env` à la racine du dossier `site/` et complétez-le avec vos propres paramètres (base de données et serveur mail) :

```env
DB_HOST=
DB_PORT=
DB_NAME=
DB_USER=
DB_PASS=
 
SMTP_HOST=
SMTP_USER=
SMTP_PASS=
SMTP_PORT=

APP_ENV=DEVELOPPEMENT
```

### 4. Créer la base de données

```bash
mysql -u root -p stage4all < config/structure.sql
mysql -u root -p stage4all < config/donnees.sql
```

### 5. Configurer Apache (VHost)

```apache
<VirtualHost *:80>
    ServerName stage4all.local
    DocumentRoot /var/www/stage4all/site/public

    <Directory /var/www/stage4all/site/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 5bis. Configurer le VHost pour les Assets (Statique)

Ce projet utilise un sous-domaine dédié pour servir les fichiers statiques afin d'optimiser les performances.

```
<VirtualHost *:80>
    ServerName stage4all-static.local
    DocumentRoot /var/www/stage4all/static

    <Directory /var/www/stage4all/static>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
### 6. Accéder au site

```
http://stage4all.local
```

---

## Configuration

### Comptes par défaut

| Rôle          | Email                      | Mot de passe   |
|---------------|---------------------------|-----------------|
| Administrateur | admin@cesi.fr       | password       |
| Pilote        | pilote@cesi.fr       | password       |
| Étudiant      | etudiant@viacesi.fr      | password        |

> ⚠️ Pensez à changer ces mots de passe en production.

---

## Structure du projet

### Base de données

La base de données relationnelle est structurée autour de plusieurs domaines clés. Voici les tables principales générées par le script `structure.sql` :

#### 👥 Utilisateurs et Droits
| Table | Description |
|---|---|
| `UTILISATEUR` | Comptes utilisateurs (nom, prénom, email, mot de passe hashé, rôle). |
| `PROFIL_SCOLAIRE` | Informations spécifiques aux étudiants (lien avec le campus et la promotion). |
| `ROLE` | Rôles disponibles sur la plateforme (ex: Admin, Pilote, Étudiant). |
| `PERMISSION` | Liste des permissions unitaires pour les fonctionnalités (SFx). |
| `ROLE_PERMISSION` | Table de liaison définissant quelles permissions sont accordées à quels rôles. |

#### 🏢 Entreprises et Offres
| Table | Description |
|---|---|
| `ENTREPRISE` | Fiches détaillées des entreprises (nom, contact, présentation, secteur, etc.) avec gestion d'archivage. |
| `OFFRE` | Offres de stage avec leurs caractéristiques (titre, description, dates, rémunération) et archivage. |
| `EVALUATION` | Notes (de 0 à 5) attribuées par les utilisateurs aux entreprises. |

#### 📬 Actions Étudiantes
| Table | Description |
|---|---|
| `CANDIDATURE` | Historique des postulations (date, chemin du CV, lettre de motivation, lien offre/utilisateur). |
| `WISHLIST` | Table de liaison pour les offres sauvegardées en favoris par les étudiants. |

#### 🗂️ Référentiels et Localisation
| Table | Description |
|---|---|
| `CAMPUS` | Liste des campus CESI. |
| `PROMOTION` | Liste des promotions scolaires. |
| `SECTEUR` | Secteurs d'activité des entreprises. |
| `COMPETENCE` | Catalogue des compétences requises pour les offres de stage. |
| `OFFRE_COMPETENCE` | Table de liaison associant les compétences spécifiques à chaque offre. |
| `VILLE` & `CODE_POSTAL` | Référentiels géographiques. |
| `LOCALITE` | Table de liaison normalisée associant une ville et un code postal (utilisée par les offres et les entreprises). |

---

## Rôles et permissions

| Fonctionnalité                        | Admin | Pilote | Étudiant | Anonyme |
|---------------------------------------|:-----:|:------:|:--------:|:-------:|
| Authentification                      | ✅    | ✅     | ✅       | ✅      |
| Voir les entreprises                  | ✅    | ✅     | ✅       | ✅      |
| Créer / Modifier / Supprimer entreprise | ✅  | ✅     | ❌       | ❌      |
| Évaluer une entreprise                | ✅    | ✅     | ❌       | ❌      |
| Voir les offres                       | ✅    | ✅     | ✅       | ✅      |
| Créer / Modifier / Supprimer offre    | ✅    | ✅     | ❌       | ❌      |
| Statistiques                          | ✅    | ✅     | ✅       | ✅      |
| Gérer les pilotes                     | ✅    | ❌     | ❌       | ❌      |
| Gérer les étudiants                   | ✅    | ✅     | ❌       | ❌      |
| Postuler à une offre                  | ❌    | ❌     | ✅       | ❌      |
| Voir ses candidatures                 | ❌    | ❌     | ✅       | ❌      |
| Voir les candidatures des élèves      | ❌    | ✅     | ❌       | ❌      |
| Gérer sa wish-list                    | ❌    | ❌     | ✅       | ❌      |

---

## Tests unitaires

Les tests sont réalisés avec **PHPUnit 12** conformément à la spécification STx14.

### Lancer tous les tests

```bash
./vendor/bin/phpunit --testdox
```

### Lancer un fichier spécifique

```bash
./vendor/bin/phpunit tests/UtilisateurModelTest.php --testdox
./vendor/bin/phpunit tests/OffreModelTest.php --testdox
./vendor/bin/phpunit tests/EntrepriseModelTest.php --testdox
```

### Couverture des tests

| Fichier                    | Ce qui est testé                                                              |
|----------------------------|-------------------------------------------------------------------------------|
| `UtilisateurModelTest.php` | Modèle Utilisateur : Tests unitaires du CRUD (Créer, Modifier, Supprimer) avec mocks PDO |
| `OffreModelTest.php`       | Modèle Offre : Tests unitaires du CRUD (Créer, Modifier, Supprimer) avec mocks PDO       |
| `EntrepriseModelTest.php`  | Modèle Entreprise : Tests unitaires du CRUD (Créer, Modifier, Supprimer) avec mocks PDO  |

---

## Sécurité

- ✅ Mots de passe hashés en **BCRYPT** (`password_hash` / `password_verify`)
- ✅ Requêtes SQL **préparées** (protection injection SQL)
- ✅ Échappement **XSS** via Twig (`{{ variable }}` auto-escaped)
- ✅ IDs GET castés en entier `(int) $_GET['id']`
- ✅ Vérification des **permissions** à chaque route protégée
- ✅ Protocole **HTTPS** recommandé en production

---

## Équipe

| Membres         |
|-----------------|
| [SIEGEL Alexis](https://www.linkedin.com/in/alexis-sgl/)   |
| [JUND Tom](https://www.linkedin.com/in/tom-jund/)        |
| [CROATTO Samuel](https://www.linkedin.com/in/samuel-croatto-b7222a310/)  |
| [HEGY Alexia](https://www.linkedin.com/in/alexia-hegy/)     |

---

Projet réalisé dans le cadre du module Développement Web de l'école d'ingénieurs CESI.