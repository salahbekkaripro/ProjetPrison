# ⚠️ ATTENTION – NOM DU DOSSIER

> 💥 **Le dossier du projet doit impérativement s’appeler `ProjetPrison`** (avec un P majuscule)  
> ❌ Sinon, certaines fonctionnalités AJAX, les redirections PHP et les chemins d’inclusion (`require`, `href`, `fetch`) **ne fonctionneront pas**  
> 🔗 Tous les chemins absolus sont basés sur `/ProjetPrison/...`

---

## 📁 Initialisation après `git clone`

Certains dossiers dynamiques **ne sont pas versionnés dans Git**, il faut donc les **créer manuellement** après avoir cloné le projet :

### À créer :

```
uploads/comments/
uploads/avatars/
logs/
logs/upload_debug.log
```

---

### 🐧 Linux / macOS

```bash
# Depuis la racine du projet
mkdir -p uploads/comments uploads/avatars logs

# Donne les bons droits à Apache (serveur web)
sudo chown -R www-data:www-data uploads logs
sudo chmod -R 775 uploads logs

# Crée un fichier de log pour les erreurs d’upload
touch logs/upload_debug.log
sudo chown www-data:www-data logs/upload_debug.log
sudo chmod 664 logs/upload_debug.log
```

---

### 🪟 Windows (XAMPP / WAMP / Laragon)

1. Crée manuellement ces dossiers :
   ```
   ProjetPrison/uploads/comments/
   ProjetPrison/uploads/avatars/
   ProjetPrison/logs/
   ```

2. Donne les **droits d'écriture** :
   - Clic droit > **Propriétés** > **Sécurité**
   - Autoriser l'écriture à `Everyone` ou `SYSTEM` (selon ta config Apache)

3. Crée un fichier vide :
   ```
   ProjetPrison/logs/upload_debug.log
   ```

---

# 🧱 Prison Manager – Forum immersif et gestion carcérale (PHP/MySQL)

Plateforme de simulation carcérale mêlant gestion des rôles (prisonnier, admin/gardien, gestionnaire), forum intégré, sanctions dynamiques, messagerie privée, objets interdits et mini-jeux.

---

## 🚀 Fonctionnalités principales

- 👤 **Rôles utilisateurs distincts** : prisonnier, gestionnaire, admin (gardien)
- 📅 **Gestion de planning** : ajout et validation d’activités (travail, promenade, cellule…)
- 💬 **Forum immersif** : discussions contrôlées par les admins/gardiens, réponses par les prisonniers
- 📬 **Messagerie privée** : discussion possible uniquement entre prisonniers présents dans le même lieu
- 🗃️ **Gestion du stock** : ajout/suppression d’objets par le gestionnaire (avec objets interdits)
- 🚨 **Système de fouille** : objets interdits déclenchent fouille, pot-de-vin ou infraction automatique
- ⚖️ **Sanctions dynamiques** : mise au trou, amende, restrictions temporaires
- 🧠 **Mini-jeux** : travail pour gagner de l’argent, attaque entre prisonniers, tentative d’évasion
- 🔒 **Contrôle des accès** : chaque page est protégée selon le rôle + cas spécifique du cachot
- 🔔 **Notifications dynamiques** : alertes (fouilles, sanctions, messages…)

---

## 🗂️ Structure du projet

### 📁 Racine

| Fichier | Rôle |
|--------|------|
| `index.php` | Page d’accueil du site |
| `cellule.php` | Interface de la cellule, lieu principal du prisonnier |
| `evasion.php`, `jouer_attaque.php` | Mini-jeux pour s’évader ou se battre |
| `achat_objet.php`, `gestion_stock.php` | Interface d’achat et gestion des objets |
| `infractions_prisonnier.php` | Historique des infractions reçues |
| `notifications.php` | Liste des notifications reçues |
| `messages/inbox.php`, `new_message.php` | Messagerie privée entre prisonniers |
| `header.php`, `navbar.php`, `functions.php`, `db.php` | Composants réutilisables et config |

---

### 📁 admin/

| Fichier | Rôle |
|--------|------|
| `manage_users.php` | Promouvoir, bannir, supprimer des comptes |
| `manage_posts.php` | Modérer les discussions du forum |
| `manage_comments.php` | Valider ou supprimer les commentaires |
| `dashboard.php` | Vue d’ensemble pour le gardien/admin |
| `logs.php` | Historique des actions de modération |

---

## 🧠 Technologies utilisées

- **PHP 8+** / **MySQL** (PDO sécurisé)
- **JavaScript** (AJAX, interactions fluides)
- **CSS** (glassmorphism, animations, responsive)
- **HTML5** (structure sémantique)
- **Bootstrap (léger)** pour des composants stylisés

---

## 🔐 Sécurité & Gestion des rôles

- Système de session sécurisé (connexion, rôles, statuts)
- Redirections automatiques si accès non autorisé
- Overlay bloquant si l’utilisateur est mis au trou
- Vérifications des objets, des IDs et des rôles dans chaque page critique

---

## 📌 Auteurs

- **Bekkari Salah-Eddine** — gestion de la prison, mini-jeux, sanctions, fouilles, plannings
- **Mohamed Boughmadi** — forum, messagerie, interface stylisée, système de modération

Projet réalisé dans le cadre d’un site immersif de gestion de prison avec composante forum.

---

## 🧩 Un indice...

> **SI TU VEUX T'ÉCHAPPER**, ALORS ACHÈTE EXACTEMENT les objets que le gestionnaire ne peut pas supprimer... et rends-toi sur `cellule.php` et recharge la page quand tu y es.  

Bonne chance... 😈
---

# uploads/comments

Ce dossier stocke les pièces jointes des commentaires.

⚠️ Ce dossier **doit être présent** et accessible en écriture par le serveur (`www-data`).

Si vous clonez ce dépôt :

```bash
mkdir -p uploads/comments
sudo chown -R www-data:www-data uploads/comments
sudo chmod -R 775 uploads/comments
