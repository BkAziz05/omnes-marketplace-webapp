-- ============================================================
-- Omnes MarketPlace - Base de données MySQL
-- Basé sur le MCD fourni
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `omnes_marketplace` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `omnes_marketplace`;

-- ============================================================
-- Table : administrateur
-- ============================================================
CREATE TABLE `administrateur` (
  `idAdmin` INT(11) NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idAdmin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : vendeur
-- ============================================================
CREATE TABLE `vendeur` (
  `idVendeur` INT(11) NOT NULL AUTO_INCREMENT,
  `pseudo` VARCHAR(100) NOT NULL UNIQUE,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `photo_profil` VARCHAR(255) DEFAULT NULL,
  `image_fond` VARCHAR(255) DEFAULT NULL,
  `statut_compte` ENUM('actif','suspendu','supprime') DEFAULT 'actif',
  `idAdmin` INT(11) NOT NULL COMMENT 'Vendeur géré par cet admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idVendeur`),
  FOREIGN KEY (`idAdmin`) REFERENCES `administrateur`(`idAdmin`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : acheteur
-- ============================================================
CREATE TABLE `acheteur` (
  `idAcheteur` INT(11) NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `mdp` VARCHAR(255) NOT NULL,
  `adresse` TEXT DEFAULT NULL,
  `NumTelephone` VARCHAR(20) DEFAULT NULL,
  `clause_acceptee` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idAcheteur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : categorie
-- ============================================================
CREATE TABLE `categorie` (
  `idCategorie` INT(11) NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(150) NOT NULL,
  `type_marchandise` ENUM('rare','haute_gamme','regulier') NOT NULL,
  PRIMARY KEY (`idCategorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : article
-- ============================================================
CREATE TABLE `article` (
  `idArticle` INT(11) NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(200) NOT NULL,
  `description_qualite` TEXT DEFAULT NULL,
  `description_defaut` TEXT DEFAULT NULL,
  `prix_base` DECIMAL(10,2) NOT NULL,
  `mode_vente` ENUM('immediat','negotiation','enchere') NOT NULL,
  `status` ENUM('disponible','vendu','en_cours','supprime') DEFAULT 'disponible',
  `date_publication` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `date_debut_enchere` DATETIME DEFAULT NULL,
  `date_fin_enchere` DATETIME DEFAULT NULL,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `idVendeur` INT(11) NOT NULL,
  `idCategorie` INT(11) NOT NULL,
  PRIMARY KEY (`idArticle`),
  FOREIGN KEY (`idVendeur`) REFERENCES `vendeur`(`idVendeur`) ON DELETE CASCADE,
  FOREIGN KEY (`idCategorie`) REFERENCES `categorie`(`idCategorie`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : photo_article
-- ============================================================
CREATE TABLE `photo_article` (
  `idPhoto` INT(11) NOT NULL AUTO_INCREMENT,
  `url_photo` VARCHAR(255) NOT NULL,
  `ordre` INT(11) DEFAULT 1,
  `idArticle` INT(11) NOT NULL,
  PRIMARY KEY (`idPhoto`),
  FOREIGN KEY (`idArticle`) REFERENCES `article`(`idArticle`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : panier
-- ============================================================
CREATE TABLE `panier` (
  `idPanier` INT(11) NOT NULL AUTO_INCREMENT,
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `statut` ENUM('ouvert','valide','abandonne') DEFAULT 'ouvert',
  `sous_total` DECIMAL(10,2) DEFAULT 0.00,
  `idAcheteur` INT(11) NOT NULL,
  PRIMARY KEY (`idPanier`),
  FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur`(`idAcheteur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : ligne_panier
-- ============================================================
CREATE TABLE `ligne_panier` (
  `idLigne` INT(11) NOT NULL AUTO_INCREMENT,
  `quantite` INT(11) DEFAULT 1,
  `prix_snapshot` DECIMAL(10,2) NOT NULL COMMENT 'Prix au moment de l ajout',
  `mode_acquisition` ENUM('immediat','negotiation','enchere') NOT NULL,
  `idPanier` INT(11) NOT NULL,
  `idArticle` INT(11) NOT NULL,
  PRIMARY KEY (`idLigne`),
  FOREIGN KEY (`idPanier`) REFERENCES `panier`(`idPanier`) ON DELETE CASCADE,
  FOREIGN KEY (`idArticle`) REFERENCES `article`(`idArticle`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : commande
-- ============================================================
CREATE TABLE `commande` (
  `idCommande` INT(11) NOT NULL AUTO_INCREMENT,
  `date_commande` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `montant_total` DECIMAL(10,2) NOT NULL,
  `mode_validation` ENUM('immediat','negotiation','enchere') NOT NULL,
  `status_commande` ENUM('en_attente','validee','expediee','livree','annulee') DEFAULT 'en_attente',
  `adresse_livraison` TEXT NOT NULL,
  `idAcheteur` INT(11) NOT NULL,
  `idPanier` INT(11) NOT NULL,
  PRIMARY KEY (`idCommande`),
  FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur`(`idAcheteur`) ON DELETE RESTRICT,
  FOREIGN KEY (`idPanier`) REFERENCES `panier`(`idPanier`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : paiement
-- ============================================================
CREATE TABLE `paiement` (
  `idPaiement` INT(11) NOT NULL AUTO_INCREMENT,
  `numero_masque` VARCHAR(20) NOT NULL COMMENT 'Ex: **** **** **** 1234',
  `nom_carte` VARCHAR(150) NOT NULL,
  `expiration` VARCHAR(7) NOT NULL COMMENT 'Format MM/YYYY',
  `statut_paiement` ENUM('en_attente','approuve','refuse','rembourse') DEFAULT 'en_attente',
  `type_paiement` ENUM('visa','mastercard','amex','paypal') NOT NULL,
  `idCommande` INT(11) NOT NULL,
  `idAcheteur` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idPaiement`),
  FOREIGN KEY (`idCommande`) REFERENCES `commande`(`idCommande`) ON DELETE RESTRICT,
  FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur`(`idAcheteur`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : negociation
-- ============================================================
CREATE TABLE `negociation` (
  `id_negociation` INT(11) NOT NULL AUTO_INCREMENT,
  `statut` ENUM('en_cours','acceptee','refusee','expiree') DEFAULT 'en_cours',
  `date_debut` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `nb_tours` INT(11) DEFAULT 0 COMMENT 'Nombre de propositions échangées (max 5)',
  `prix_final` DECIMAL(10,2) DEFAULT NULL,
  `idArticle` INT(11) NOT NULL,
  `idAcheteur` INT(11) NOT NULL,
  `idVendeur` INT(11) NOT NULL,
  PRIMARY KEY (`id_negociation`),
  FOREIGN KEY (`idArticle`) REFERENCES `article`(`idArticle`) ON DELETE RESTRICT,
  FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur`(`idAcheteur`) ON DELETE RESTRICT,
  FOREIGN KEY (`idVendeur`) REFERENCES `vendeur`(`idVendeur`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : proposition (historique des négociations)
-- ============================================================
CREATE TABLE `proposition` (
  `idProposition` INT(11) NOT NULL AUTO_INCREMENT,
  `montant` DECIMAL(10,2) NOT NULL,
  `date_proposition` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `emetteur` ENUM('acheteur','vendeur') NOT NULL,
  `statut` ENUM('en_attente','acceptee','refusee','contre_offre') DEFAULT 'en_attente',
  `id_negociation` INT(11) NOT NULL,
  PRIMARY KEY (`idProposition`),
  FOREIGN KEY (`id_negociation`) REFERENCES `negociation`(`id_negociation`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : offre_enchere
-- ============================================================
CREATE TABLE `offre_enchere` (
  `idOffre` INT(11) NOT NULL AUTO_INCREMENT,
  `montant_max` DECIMAL(10,2) NOT NULL COMMENT 'Montant maximum que l acheteur accepte',
  `montant_courant` DECIMAL(10,2) NOT NULL COMMENT 'Offre effective placée par le système',
  `date_offre` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `statut` ENUM('active','gagnante','perdante','annulee') DEFAULT 'active',
  `idArticle` INT(11) NOT NULL,
  `idAcheteur` INT(11) NOT NULL,
  PRIMARY KEY (`idOffre`),
  FOREIGN KEY (`idArticle`) REFERENCES `article`(`idArticle`) ON DELETE RESTRICT,
  FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur`(`idAcheteur`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : alerte
-- ============================================================
CREATE TABLE `alerte` (
  `idAlerte` INT(11) NOT NULL AUTO_INCREMENT,
  `mots_cles` VARCHAR(255) DEFAULT NULL,
  `prix_max` DECIMAL(10,2) DEFAULT NULL,
  `mode_vente_souhaite` ENUM('immediat','negotiation','enchere','tous') DEFAULT 'tous',
  `active` TINYINT(1) DEFAULT 1,
  `idAcheteur` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idAlerte`),
  FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur`(`idAcheteur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table : notification
-- ============================================================
CREATE TABLE `notification` (
  `idNotification` INT(11) NOT NULL AUTO_INCREMENT,
  `message` TEXT NOT NULL,
  `type` ENUM('alerte','negociation','enchere','commande','systeme') NOT NULL,
  `lue` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `idAcheteur` INT(11) DEFAULT NULL,
  `idVendeur` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idNotification`),
  FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur`(`idAcheteur`) ON DELETE CASCADE,
  FOREIGN KEY (`idVendeur`) REFERENCES `vendeur`(`idVendeur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DONNÉES DE TEST
-- ============================================================

-- Administrateur (mot de passe : Admin1234!)
INSERT INTO `administrateur` (`nom`, `prenom`, `email`, `mot_de_passe`) VALUES
('Dupont', 'Jean', 'admin@omnes-marketplace.fr', '$2y$12$IDgsH7MOQzQjzxmmhs95T.5ogKuEJetJ0es6Bb9EAb8/K4uOAuk7y');

-- Vendeurs (mot de passe : Vendeur1234!)
INSERT INTO `vendeur` (`pseudo`, `nom`, `prenom`, `email`, `mot_de_passe`, `statut_compte`, `idAdmin`) VALUES
('MarcAntiques', 'Martin', 'Marc', 'marc.martin@email.fr', '$2y$12$4uNztn9KPPmMDjEEjpV2AenU/n0dtIxIrja2JpZdVBgMwIX68TvhG', 'actif', 1),
('SophieLux', 'Leclerc', 'Sophie', 'sophie.leclerc@email.fr', '$2y$12$4uNztn9KPPmMDjEEjpV2AenU/n0dtIxIrja2JpZdVBgMwIX68TvhG', 'actif', 1),
('ThomasShop', 'Bernard', 'Thomas', 'thomas.bernard@email.fr', '$2y$12$4uNztn9KPPmMDjEEjpV2AenU/n0dtIxIrja2JpZdVBgMwIX68TvhG', 'actif', 1);

-- Acheteurs (mot de passe : Acheteur1234!)
INSERT INTO `acheteur` (`nom`, `prenom`, `email`, `mdp`, `adresse`, `NumTelephone`, `clause_acceptee`) VALUES
('Dubois', 'Alice', 'alice.dubois@email.fr', '$2y$12$1eMx5b3ScnQDoaneYKa0UOR5rJ7zBW7yqejqjE91xHTETkJySxCvC', '12 Rue de la Paix, 75001 Paris', '0612345678', 1),
('Moreau', 'Pierre', 'pierre.moreau@email.fr', '$2y$12$1eMx5b3ScnQDoaneYKa0UOR5rJ7zBW7yqejqjE91xHTETkJySxCvC', '5 Avenue des Fleurs, 69001 Lyon', '0698765432', 1),
('Lambert', 'Camille', 'camille.lambert@email.fr', '$2y$12$1eMx5b3ScnQDoaneYKa0UOR5rJ7zBW7yqejqjE91xHTETkJySxCvC', '8 Boulevard Haussmann, 75008 Paris', '0645678901', 0);

-- Catégories
INSERT INTO `categorie` (`libelle`, `type_marchandise`) VALUES
('Bijoux & Montres', 'rare'),
('Art & Collections', 'rare'),
('Vêtements Luxe', 'haute_gamme'),
('Électronique', 'haute_gamme'),
('Livres & Papeterie', 'regulier'),
('Accessoires Mode', 'regulier'),
('Matériel Scolaire', 'regulier'),
('Mobilier Antique', 'rare');

-- Articles
INSERT INTO `article` (`nom`, `description_qualite`, `description_defaut`, `prix_base`, `mode_vente`, `status`, `date_debut_enchere`, `date_fin_enchere`, `idVendeur`, `idCategorie`) VALUES
('Bague Cartier 18 carats or jaune 4.8g', 'Magnifique bague en or jaune 18 carats, poinçon visible, authentique', NULL, 1500.00, 'enchere', 'disponible', '2026-03-10 09:00:00', '2026-03-17 17:00:00', 1, 1),
('Montre Rolex Submariner', 'Montre de luxe en excellent état, boîte et papiers fournis', 'Légères traces d usage sur le bracelet', 8000.00, 'negotiation', 'disponible', NULL, NULL, 2, 1),
('Veste Hermès Vintage', 'Veste en tweed Hermès vintage, coupe parfaite', 'Légère décoloration sur la doublure', 450.00, 'negotiation', 'disponible', NULL, NULL, 2, 3),
('MacBook Pro 14 M3', 'MacBook Pro 14 pouces, puce M3, 16Go RAM, 512Go SSD', NULL, 1800.00, 'immediat', 'disponible', NULL, NULL, 3, 4),
('iPhone 15 Pro Max 256Go', 'iPhone 15 Pro Max couleur titane naturel, comme neuf', NULL, 950.00, 'immediat', 'disponible', NULL, NULL, 3, 4),
('Commode Louis XV Acajou', 'Commode d époque Louis XV en acajou massif, circa 1750', 'Petits manques de placage sur le côté gauche', 3200.00, 'enchere', 'disponible', '2026-03-12 10:00:00', '2026-03-19 18:00:00', 1, 8),
('Pack Livres HTML5 CSS3 JS', 'Lot de 4 livres de programmation web en très bon état', NULL, 45.00, 'immediat', 'disponible', NULL, NULL, 3, 5),
('Sac Chanel Classique Caviar', 'Sac Chanel classique en cuir caviar noir, quincaillerie dorée', 'Légère usure sur les coins', 4500.00, 'negotiation', 'disponible', NULL, NULL, 2, 3);

-- Photos des articles
INSERT INTO `photo_article` (`url_photo`, `ordre`, `idArticle`) VALUES
('uploads/articles/bague_cartier_1.jpg', 1, 1),
('uploads/articles/bague_cartier_2.jpg', 2, 1),
('uploads/articles/rolex_submariner_1.jpg', 1, 2),
('uploads/articles/veste_hermes_1.jpg', 1, 3),
('uploads/articles/macbook_pro_1.jpg', 1, 4),
('uploads/articles/iphone15_1.jpg', 1, 5),
('uploads/articles/commode_lxv_1.jpg', 1, 6),
('uploads/articles/livres_web_1.jpg', 1, 7),
('uploads/articles/sac_chanel_1.jpg', 1, 8);

-- Panier pour acheteur 1
INSERT INTO `panier` (`statut`, `sous_total`, `idAcheteur`) VALUES
('ouvert', 950.00, 1);

-- Ligne panier
INSERT INTO `ligne_panier` (`quantite`, `prix_snapshot`, `mode_acquisition`, `idPanier`, `idArticle`) VALUES
(1, 950.00, 'immediat', 1, 5);

-- Alertes
INSERT INTO `alerte` (`mots_cles`, `prix_max`, `mode_vente_souhaite`, `active`, `idAcheteur`) VALUES
('bijou or montre', 2000.00, 'enchere', 1, 1),
('macbook apple', 2500.00, 'immediat', 1, 2),
('sac luxe chanel', 5000.00, 'negotiation', 1, 3);

-- Offre enchère
INSERT INTO `offre_enchere` (`montant_max`, `montant_courant`, `statut`, `idArticle`, `idAcheteur`) VALUES
(2000.00, 1501.00, 'active', 1, 1),
(5000.00, 3201.00, 'active', 6, 2);

-- Négociation
INSERT INTO `negociation` (`statut`, `nb_tours`, `idArticle`, `idAcheteur`, `idVendeur`) VALUES
('en_cours', 1, 2, 1, 2);

INSERT INTO `proposition` (`montant`, `emetteur`, `statut`, `id_negociation`) VALUES
(6500.00, 'acheteur', 'en_attente', 1);

-- ============================================================
-- INDEX supplémentaires pour les performances
-- ============================================================
CREATE INDEX idx_article_mode_vente ON article(mode_vente);
CREATE INDEX idx_article_status ON article(status);
CREATE INDEX idx_article_vendeur ON article(idVendeur);
CREATE INDEX idx_negociation_article ON negociation(idArticle);
CREATE INDEX idx_offre_article ON offre_enchere(idArticle);
CREATE INDEX idx_notification_acheteur ON notification(idAcheteur);
