-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : dim. 18 mai 2025 à 17:31
-- Version du serveur : 10.6.21-MariaDB-0ubuntu0.22.04.2
-- Version de PHP : 8.1.2-1ubuntu2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `forum_prison`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `date_embauche` date DEFAULT curdate(),
  `grade` varchar(50) DEFAULT 'stagiaire'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `admin_username` varchar(255) NOT NULL,
  `target_username` varchar(255) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cellule`
--

CREATE TABLE `cellule` (
  `id` int(11) NOT NULL,
  `numero_cellule` int(11) NOT NULL,
  `capacite` int(11) DEFAULT 1,
  `surveillance` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `author` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0,
  `reported` tinyint(1) DEFAULT 0,
  `tag` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT current_timestamp(),
  `validated_by_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `duels`
--

CREATE TABLE `duels` (
  `id` int(11) NOT NULL,
  `initiateur_id` int(11) NOT NULL,
  `adversaire_id` int(11) NOT NULL,
  `etat` enum('en_attente','accepte','refuse','termine') DEFAULT 'en_attente',
  `resultat_initiateur` enum('victoire','defaite','nul') DEFAULT NULL,
  `resultat_adversaire` enum('victoire','defaite','nul') DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_fin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gardien`
--

CREATE TABLE `gardien` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `date_embauche` date DEFAULT curdate(),
  `grade` varchar(50) DEFAULT 'stagiaire'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gestionnaire`
--

CREATE TABLE `gestionnaire` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `domaine_responsabilite` varchar(255) DEFAULT NULL,
  `date_nomination` date DEFAULT curdate(),
  `date_prise_fonction` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `infraction`
--

CREATE TABLE `infraction` (
  `id` int(11) NOT NULL,
  `prisonnier_id` int(11) NOT NULL,
  `type_infraction` enum('tentative évasion','meurtre','possession objet interdit','mutinerie') NOT NULL,
  `date_infraction` datetime DEFAULT current_timestamp(),
  `sanction` varchar(255) DEFAULT NULL,
  `pot_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('like','dislike') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `recipient_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `type` enum('reply','fouille','message','pot_de_vin','reponse_pot_de_vin','pot_de_vin_gardien','achat','infraction_suggeree','sanction_appliquee','annonce_generale','attaque_subie','duel_invitation','sante_degradee','pot_de_vin_admin') DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `pot_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `objets_disponibles`
--

CREATE TABLE `objets_disponibles` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `interdit` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `type` enum('alimentation','outil','divertissement','autre','arme','chimique') DEFAULT 'autre'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `objets_prisonniers`
--

CREATE TABLE `objets_prisonniers` (
  `id` int(11) NOT NULL,
  `prisonnier_id` int(11) DEFAULT NULL,
  `nom_objet` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `interdit` tinyint(1) DEFAULT 0,
  `objet_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `planning`
--

CREATE TABLE `planning` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `activite` enum('Cellule','Douche','Cantine','Promenade') NOT NULL,
  `validation` enum('en attente','validé','refusé') DEFAULT 'en attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pots_de_vin`
--

CREATE TABLE `pots_de_vin` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `prisonnier_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_demande` datetime DEFAULT current_timestamp(),
  `statut` enum('en_attente','accepté','refusé') DEFAULT 'en_attente',
  `expire_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prisonnier`
--

CREATE TABLE `prisonnier` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `date_entree` date NOT NULL,
  `date_sortie` date DEFAULT NULL,
  `motif_entree` text DEFAULT NULL,
  `cellule_id` int(11) DEFAULT NULL,
  `objet` varchar(100) DEFAULT NULL,
  `etat` enum('sain','malade','blessé','décédé') DEFAULT 'sain',
  `derniere_maj_etat` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `revealed` tinyint(1) DEFAULT 0,
  `self_destruct` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `private_message_replies`
--

CREATE TABLE `private_message_replies` (
  `id` int(11) NOT NULL,
  `message_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sanction`
--

CREATE TABLE `sanction` (
  `id` int(11) NOT NULL,
  `infraction_id` int(11) NOT NULL,
  `prisonnier_id` int(11) NOT NULL,
  `type_sanction` varchar(100) NOT NULL,
  `gravite` enum('faible','moyenne','grave') DEFAULT 'faible',
  `duree_jours` int(11) DEFAULT 0,
  `commentaire` text DEFAULT NULL,
  `date_sanction` datetime DEFAULT current_timestamp(),
  `fin_sanction` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `surveillance`
--

CREATE TABLE `surveillance` (
  `id` int(11) NOT NULL,
  `prisonnier_id` int(11) NOT NULL,
  `gardien_id` int(11) NOT NULL,
  `date_debut` datetime NOT NULL DEFAULT current_timestamp(),
  `date_fin` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('membre','admin','chef','gardien','prisonnier','cuisinier','gestionnaire') DEFAULT 'membre',
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_banned` tinyint(1) DEFAULT 0,
  `ban_until` datetime DEFAULT NULL,
  `status` enum('actif','inactif') DEFAULT 'actif',
  `age` int(11) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `argent` decimal(10,2) NOT NULL DEFAULT 0.00
) ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `cellule`
--
ALTER TABLE `cellule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_cellule` (`numero_cellule`);

--
-- Index pour la table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `duels`
--
ALTER TABLE `duels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `initiateur_id` (`initiateur_id`),
  ADD KEY `adversaire_id` (`adversaire_id`);

--
-- Index pour la table `gardien`
--
ALTER TABLE `gardien`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `gestionnaire`
--
ALTER TABLE `gestionnaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `infraction`
--
ALTER TABLE `infraction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prisonnier_id` (`prisonnier_id`);

--
-- Index pour la table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_comment_vote` (`comment_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `fk_pot` (`pot_id`);

--
-- Index pour la table `objets_disponibles`
--
ALTER TABLE `objets_disponibles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `objets_prisonniers`
--
ALTER TABLE `objets_prisonniers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prisonnier_id` (`prisonnier_id`),
  ADD KEY `objet_id` (`objet_id`);

--
-- Index pour la table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pots_de_vin`
--
ALTER TABLE `pots_de_vin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prisonnier_id` (`prisonnier_id`),
  ADD KEY `fk_pots_de_vin_admin` (`admin_id`);

--
-- Index pour la table `prisonnier`
--
ALTER TABLE `prisonnier`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `cellule_id` (`cellule_id`);

--
-- Index pour la table `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Index pour la table `private_message_replies`
--
ALTER TABLE `private_message_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Index pour la table `sanction`
--
ALTER TABLE `sanction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `infraction_id` (`infraction_id`),
  ADD KEY `prisonnier_id` (`prisonnier_id`);

--
-- Index pour la table `surveillance`
--
ALTER TABLE `surveillance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prisonnier_id` (`prisonnier_id`),
  ADD KEY `gardien_id` (`gardien_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `cellule`
--
ALTER TABLE `cellule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `duels`
--
ALTER TABLE `duels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gardien`
--
ALTER TABLE `gardien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gestionnaire`
--
ALTER TABLE `gestionnaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `infraction`
--
ALTER TABLE `infraction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `objets_disponibles`
--
ALTER TABLE `objets_disponibles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `objets_prisonniers`
--
ALTER TABLE `objets_prisonniers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `planning`
--
ALTER TABLE `planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pots_de_vin`
--
ALTER TABLE `pots_de_vin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `prisonnier`
--
ALTER TABLE `prisonnier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `private_message_replies`
--
ALTER TABLE `private_message_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sanction`
--
ALTER TABLE `sanction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `surveillance`
--
ALTER TABLE `surveillance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `duels`
--
ALTER TABLE `duels`
  ADD CONSTRAINT `duels_ibfk_1` FOREIGN KEY (`initiateur_id`) REFERENCES `prisonnier` (`id`),
  ADD CONSTRAINT `duels_ibfk_2` FOREIGN KEY (`adversaire_id`) REFERENCES `prisonnier` (`id`);

--
-- Contraintes pour la table `gardien`
--
ALTER TABLE `gardien`
  ADD CONSTRAINT `gardien_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `gestionnaire`
--
ALTER TABLE `gestionnaire`
  ADD CONSTRAINT `gestionnaire_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `infraction`
--
ALTER TABLE `infraction`
  ADD CONSTRAINT `infraction_ibfk_1` FOREIGN KEY (`prisonnier_id`) REFERENCES `prisonnier` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_pot` FOREIGN KEY (`pot_id`) REFERENCES `pots_de_vin` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`);

--
-- Contraintes pour la table `objets_prisonniers`
--
ALTER TABLE `objets_prisonniers`
  ADD CONSTRAINT `objets_prisonniers_ibfk_1` FOREIGN KEY (`prisonnier_id`) REFERENCES `prisonnier` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `objets_prisonniers_ibfk_2` FOREIGN KEY (`objet_id`) REFERENCES `objets_disponibles` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `planning`
--
ALTER TABLE `planning`
  ADD CONSTRAINT `planning_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pots_de_vin`
--
ALTER TABLE `pots_de_vin`
  ADD CONSTRAINT `fk_pots_de_vin_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pots_de_vin_ibfk_2` FOREIGN KEY (`prisonnier_id`) REFERENCES `prisonnier` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `prisonnier`
--
ALTER TABLE `prisonnier`
  ADD CONSTRAINT `prisonnier_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prisonnier_ibfk_2` FOREIGN KEY (`cellule_id`) REFERENCES `cellule` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `private_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `private_message_replies`
--
ALTER TABLE `private_message_replies`
  ADD CONSTRAINT `private_message_replies_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `private_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_message_replies_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sanction`
--
ALTER TABLE `sanction`
  ADD CONSTRAINT `sanction_ibfk_1` FOREIGN KEY (`infraction_id`) REFERENCES `infraction` (`id`),
  ADD CONSTRAINT `sanction_ibfk_2` FOREIGN KEY (`prisonnier_id`) REFERENCES `prisonnier` (`id`);

--
-- Contraintes pour la table `surveillance`
--
ALTER TABLE `surveillance`
  ADD CONSTRAINT `surveillance_ibfk_1` FOREIGN KEY (`prisonnier_id`) REFERENCES `prisonnier` (`id`),
  ADD CONSTRAINT `surveillance_ibfk_2` FOREIGN KEY (`gardien_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
