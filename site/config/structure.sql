SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- STRUCTURE DES TABLES
-- --------------------------------------------------------

CREATE TABLE `CAMPUS` (
  `campus_id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_nom` varchar(100) NOT NULL,
  PRIMARY KEY (`campus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `ROLE` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_nom` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `PROMOTION` (
  `promotion_id` int(11) NOT NULL AUTO_INCREMENT,
  `promotion_nom` varchar(100) NOT NULL,
  PRIMARY KEY (`promotion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `SECTEUR` (
  `secteur_id` int(11) NOT NULL AUTO_INCREMENT,
  `secteur_nom` varchar(100) NOT NULL,
  PRIMARY KEY (`secteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `VILLE` (
  `ville_id` int(11) NOT NULL AUTO_INCREMENT,
  `ville_nom` varchar(100) NOT NULL,
  PRIMARY KEY (`ville_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `CODE_POSTAL` (
  `cp_id` int(11) NOT NULL AUTO_INCREMENT,
  `cp_code` varchar(10) NOT NULL,
  PRIMARY KEY (`cp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `COMPETENCE` (
  `competence_id` int(11) NOT NULL AUTO_INCREMENT,
  `competence_libelle` varchar(100) NOT NULL,
  PRIMARY KEY (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `PERMISSION` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_nom` varchar(100) NOT NULL,
  `permission_description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `LOCALITE` (
  `localite_id` int(11) NOT NULL AUTO_INCREMENT,
  `ville_id` int(11) NOT NULL,
  `cp_id` int(11) NOT NULL,
  PRIMARY KEY (`localite_id`),
  KEY `ville_id` (`ville_id`),
  KEY `cp_id` (`cp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `UTILISATEUR` (
  `utilisateur_id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_prenom` varchar(50) NOT NULL,
  `utilisateur_nom` varchar(50) NOT NULL,
  `utilisateur_email` varchar(255) NOT NULL,
  `utilisateur_mdp` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`utilisateur_id`),
  UNIQUE KEY `utilisateur_email` (`utilisateur_email`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `PROFIL_SCOLAIRE` (
  `utilisateur_id` int(11) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  PRIMARY KEY (`utilisateur_id`),
  KEY `campus_id` (`campus_id`),
  KEY `promotion_id` (`promotion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `ENTREPRISE` (
  `entreprise_id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_nom` varchar(150) NOT NULL,
  `entreprise_taille` int(11) DEFAULT NULL,
  `entreprise_siteweb` varchar(255) DEFAULT NULL,
  `entreprise_email` varchar(255) NOT NULL,
  `entreprise_telephone` varchar(20) DEFAULT NULL,
  `entreprise_rue` varchar(255) NOT NULL,
  `entreprise_extrait` varchar(255) NOT NULL,
  `entreprise_presentation` text NOT NULL,
  `entreprise_nb_stagiaires` int(11) NOT NULL DEFAULT 0,
  `localite_id` int(11) NOT NULL,
  `secteur_id` int(11) NOT NULL,
  `entreprise_archive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`entreprise_id`),
  KEY `localite_id` (`localite_id`),
  KEY `secteur_id` (`secteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `OFFRE` (
  `offre_id` int(11) NOT NULL AUTO_INCREMENT,
  `offre_titre` varchar(150) NOT NULL,
  `offre_reference` varchar(50) NOT NULL,
  `offre_description` text NOT NULL,
  `offre_missions` text NOT NULL,
  `offre_profil` text NOT NULL,
  `offre_date_debut` date NOT NULL,
  `offre_duree` int(11) NOT NULL,
  `offre_remuneration` decimal(10,2) DEFAULT NULL,
  `offre_date_publication` date NOT NULL,
  `localite_id` int(11) NOT NULL,
  `entreprise_id` int(11) NOT NULL,
  `offre_archive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`offre_id`),
  UNIQUE KEY `offre_reference` (`offre_reference`),
  KEY `localite_id` (`localite_id`),
  KEY `entreprise_id` (`entreprise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `CANDIDATURE` (
  `candidature_id` int(11) NOT NULL AUTO_INCREMENT,
  `candidature_date` date NOT NULL,
  `candidature_lettre_motivation` text DEFAULT NULL,
  `candidature_cv` varchar(255) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `offre_id` int(11) NOT NULL,
  PRIMARY KEY (`candidature_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `offre_id` (`offre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `EVALUATION` (
  `evaluation_id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluation_note` int(11) NOT NULL CHECK (`evaluation_note` >= 0 and `evaluation_note` <= 5),
  `utilisateur_id` int(11) NOT NULL,
  `entreprise_id` int(11) NOT NULL,
  PRIMARY KEY (`evaluation_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `entreprise_id` (`entreprise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `OFFRE_COMPETENCE` (
  `offre_id` int(11) NOT NULL,
  `competence_id` int(11) NOT NULL,
  PRIMARY KEY (`offre_id`,`competence_id`),
  KEY `competence_id` (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `ROLE_PERMISSION` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `WISHLIST` (
  `utilisateur_id` int(11) NOT NULL,
  `offre_id` int(11) NOT NULL,
  `wishlist_date_ajout` datetime NOT NULL,
  PRIMARY KEY (`utilisateur_id`,`offre_id`),
  KEY `offre_id` (`offre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------
-- CONTRAINTES (CLÉS ÉTRANGÈRES)
-- --------------------------------------------------------

ALTER TABLE `LOCALITE`
  ADD CONSTRAINT `fk_loc_ville` FOREIGN KEY (`ville_id`) REFERENCES `VILLE` (`ville_id`),
  ADD CONSTRAINT `fk_loc_cp` FOREIGN KEY (`cp_id`) REFERENCES `CODE_POSTAL` (`cp_id`);

ALTER TABLE `UTILISATEUR`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `ROLE` (`role_id`);

ALTER TABLE `PROFIL_SCOLAIRE`
  ADD CONSTRAINT `fk_prof_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `UTILISATEUR` (`utilisateur_id`),
  ADD CONSTRAINT `fk_prof_campus` FOREIGN KEY (`campus_id`) REFERENCES `CAMPUS` (`campus_id`),
  ADD CONSTRAINT `fk_prof_promo` FOREIGN KEY (`promotion_id`) REFERENCES `PROMOTION` (`promotion_id`);

ALTER TABLE `ENTREPRISE`
  ADD CONSTRAINT `fk_ent_loc` FOREIGN KEY (`localite_id`) REFERENCES `LOCALITE` (`localite_id`),
  ADD CONSTRAINT `fk_ent_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `SECTEUR` (`secteur_id`);

ALTER TABLE `OFFRE`
  ADD CONSTRAINT `fk_off_loc` FOREIGN KEY (`localite_id`) REFERENCES `LOCALITE` (`localite_id`),
  ADD CONSTRAINT `fk_off_ent` FOREIGN KEY (`entreprise_id`) REFERENCES `ENTREPRISE` (`entreprise_id`);

ALTER TABLE `CANDIDATURE`
  ADD CONSTRAINT `fk_cand_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `UTILISATEUR` (`utilisateur_id`),
  ADD CONSTRAINT `fk_cand_off` FOREIGN KEY (`offre_id`) REFERENCES `OFFRE` (`offre_id`);

ALTER TABLE `EVALUATION`
  ADD CONSTRAINT `fk_eval_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `UTILISATEUR` (`utilisateur_id`),
  ADD CONSTRAINT `fk_eval_ent` FOREIGN KEY (`entreprise_id`) REFERENCES `ENTREPRISE` (`entreprise_id`);

ALTER TABLE `OFFRE_COMPETENCE`
  ADD CONSTRAINT `fk_oc_off` FOREIGN KEY (`offre_id`) REFERENCES `OFFRE` (`offre_id`),
  ADD CONSTRAINT `fk_oc_comp` FOREIGN KEY (`competence_id`) REFERENCES `COMPETENCE` (`competence_id`);

ALTER TABLE `ROLE_PERMISSION`
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `ROLE` (`role_id`),
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `PERMISSION` (`permission_id`);

ALTER TABLE `WISHLIST`
  ADD CONSTRAINT `fk_wish_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `UTILISATEUR` (`utilisateur_id`),
  ADD CONSTRAINT `fk_wish_off` FOREIGN KEY (`offre_id`) REFERENCES `OFFRE` (`offre_id`);

COMMIT;