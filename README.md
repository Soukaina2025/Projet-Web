# 📚 Plateforme de Gestion de Projets

## 📌 Description

Cette plateforme web permet de faciliter la **gestion et le suivi des projets académiques** au sein d'un établissement universitaire.

Elle propose trois espaces distincts selon le rôle de l'utilisateur :

* 👨‍💼 **Administrateur**
* 👨‍🎓 **Étudiant**
* 👨‍🏫 **Professeur**

Chaque utilisateur dispose de fonctionnalités adaptées à son rôle afin de centraliser la gestion des projets, des étudiants, des enseignants et des différentes étapes de réalisation.

---

## 🎯 Objectifs

La plateforme a pour objectifs de :

* Centraliser les projets académiques dans une seule plateforme.
* Faciliter l'affectation et le suivi des projets.
* Permettre aux étudiants de consulter et gérer leurs projets.
* Permettre aux professeurs d'encadrer et de suivre les projets.
* Donner à l'administrateur une vue globale sur les utilisateurs et les projets.
* Faciliter la communication et le partage de documents liés aux projets.

---

## 👨‍💼 Espace Administrateur

L'administrateur assure la gestion globale de la plateforme.

### Fonctionnalités principales

* Gestion des comptes étudiants.
* Gestion des comptes professeurs.
* Gestion des utilisateurs.
* Création et gestion des projets.
* Affectation des projets aux étudiants et aux professeurs.
* Consultation de l'ensemble des projets.
* Suivi de l'état d'avancement des projets.
* Gestion des informations de la plateforme.

---

## 👨‍🎓 Espace Étudiant

L'étudiant dispose d'un espace personnel lui permettant de suivre son projet.

### Fonctionnalités principales

* Connexion à son espace personnel.
* Consultation de son projet.
* Consultation des informations du projet.
* Suivi de l'état d'avancement.
* Dépôt de documents et fichiers liés au projet.
* Consultation des informations de son encadrant.
* Mise à jour des informations demandées.

---

## 👨‍🏫 Espace Professeur

Le professeur dispose d'un espace dédié à l'encadrement et au suivi des projets.

### Fonctionnalités principales

* Consultation des projets encadrés.
* Consultation des étudiants affectés.
* Suivi de l'avancement des projets.
* Consultation des documents déposés par les étudiants.
* Validation ou suivi des différentes étapes du projet.
* Ajout de remarques et de commentaires.

---

## 🛠️ Technologies utilisées

### Frontend

* HTML5
* CSS3
* JavaScript

### Backend

* PHP

### Base de données

* MySQL

### Outils

* XAMPP
* Git / GitHub

---

## 📂 Structure du projet

```text
ensa_project/
│
├── admin/
│   ├── ...
│   └── ...
│
├── student/
│   ├── ...
│   └── ...
│
├── professor/
│   ├── ...
│   └── ...
│
├── uploads/
│   └── projets/
│
├── css/
├── js/
├── images/
│
├── projet_web.sql
└── README.md
```

---

## ⚙️ Installation

### 1. Cloner le projet

```bash
git clone https://github.com/Soukaina2025/Projet-Web.git
```

### 2. Placer le projet dans XAMPP

Copier le dossier du projet dans :

```text
C:\xampp\htdocs\
```

### 3. Démarrer XAMPP

Lancer :

* Apache
* MySQL

### 4. Créer la base de données

Accéder à **phpMyAdmin** et créer une base de données.

Importer ensuite le fichier :

```text
projet_web.sql
```

### 5. Configurer la connexion à la base de données

Modifier les paramètres de connexion à la base de données dans le fichier de configuration du projet.

Exemple :

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "projet_web";
```

### 6. Accéder à la plateforme

Dans le navigateur :

```text
http://localhost/ensa_project/
```

---

## 🔐 Gestion des rôles

La plateforme distingue trois types d'utilisateurs :

| Rôle                 | Accès                            |
| -------------------- | -------------------------------- |
| 👨‍💼 Administrateur | Gestion globale de la plateforme |
| 👨‍🎓 Étudiant       | Gestion et suivi de son projet   |
| 👨‍🏫 Professeur     | Encadrement et suivi des projets |

Chaque utilisateur est redirigé vers son espace selon son rôle après authentification.

---

## 📊 Fonctionnement général

```text
                 ┌─────────────────────┐
                 │      Plateforme     │
                 │ Gestion de projets  │
                 └──────────┬──────────┘
                            │
          ┌─────────────────┼─────────────────┐
          │                 │                 │
          ▼                 ▼                 ▼
    👨‍💼 Admin          👨‍🎓 Étudiant       👨‍🏫 Professeur
          │                 │                 │
          ▼                 ▼                 ▼
     Gestion          Suivi projet       Encadrement
     utilisateurs     Documents         Évaluation
     et projets       Informations      Commentaires
```

---

## 🚀 Améliorations futures

Quelques fonctionnalités peuvent être ajoutées dans de futures versions :

* Notifications en temps réel.
* Système de messagerie entre étudiants et professeurs.
* Tableau de bord avec statistiques.
* Système de recherche et de filtrage avancé.
* Calendrier des échéances.
* Système d'évaluation des projets.
* Gestion avancée des permissions.

---

## 👥 Équipe

Projet réalisé dans le cadre d'un projet académique à l'**ENSA**.

---

## 📄 Licence

Ce projet est réalisé à des fins **académiques et pédagogiques**.
