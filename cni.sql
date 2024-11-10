-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 17 oct. 2024 à 22:25
-- Version du serveur : 10.4.28-MariaDB
-- Version de PHP : 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `cni`
--

-- --------------------------------------------------------

--
-- Structure de la table `cartesidentite`
--

CREATE TABLE `cartesidentite` (
  `CarteID` int(11) NOT NULL,
  `DemandeID` int(11) DEFAULT NULL,
  `NumeroCarteIdentite` varchar(50) NOT NULL,
  `DateEmission` date DEFAULT NULL,
  `DateExpiration` date DEFAULT NULL,
  `CodeQR` varchar(255) DEFAULT NULL,
  `Statut` enum('Active','Expiree','Perdue','Annulee') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `certificatsnationalite`
--

CREATE TABLE `certificatsnationalite` (
  `CertificatID` int(11) NOT NULL,
  `DemandeID` int(11) DEFAULT NULL,
  `NumeroCertificat` varchar(50) NOT NULL,
  `DateEmission` date DEFAULT NULL,
  `CheminPDF` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes`
--

CREATE TABLE `demandes` (
  `DemandeID` int(11) NOT NULL,
  `UtilisateurID` int(11) DEFAULT NULL,
  `TypeDemande` enum('CNI','CertificatNationalite','NATIONALITE') NOT NULL,
  `SousTypeDemande` enum('premiere','renouvellement','perte','naturalisation') DEFAULT NULL,
  `Statut` enum('Soumise','EnCours','Approuvee','Rejetee','Terminee','Annulee') NOT NULL,
  `DateSoumission` timestamp NOT NULL DEFAULT current_timestamp(),
  `DateAchevement` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demandes`
--

INSERT INTO `demandes` (`DemandeID`, `UtilisateurID`, `TypeDemande`, `SousTypeDemande`, `Statut`, `DateSoumission`, `DateAchevement`) VALUES
(1, 3, 'CNI', 'premiere', 'EnCours', '2024-10-03 14:07:19', '2024-10-11 18:07:34'),
(2, 3, 'NATIONALITE', NULL, 'Approuvee', '2024-10-04 21:41:04', '2024-10-05 13:20:37');

-- --------------------------------------------------------

--
-- Structure de la table `demande_cni_details`
--

CREATE TABLE `demande_cni_details` (
  `DetailID` int(11) NOT NULL,
  `DemandeID` int(11) NOT NULL,
  `TypeDemande` enum('premiere','renouvellement','perte','naturalisation') DEFAULT NULL,
  `Nom` varchar(50) NOT NULL,
  `Prenom` varchar(50) NOT NULL,
  `DateNaissance` date NOT NULL,
  `LieuNaissance` varchar(100) NOT NULL,
  `Adresse` text NOT NULL,
  `Sexe` enum('M','F') NOT NULL,
  `Taille` int(11) NOT NULL,
  `Profession` varchar(100) NOT NULL,
  `NumeroCNIPrecedente` varchar(50) DEFAULT NULL,
  `DatePerteVol` date DEFAULT NULL,
  `NumeroDecretNaturalisation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demande_cni_details`
--

INSERT INTO `demande_cni_details` (`DetailID`, `DemandeID`, `TypeDemande`, `Nom`, `Prenom`, `DateNaissance`, `LieuNaissance`, `Adresse`, `Sexe`, `Taille`, `Profession`, `NumeroCNIPrecedente`, `DatePerteVol`, `NumeroDecretNaturalisation`) VALUES
(1, 1, NULL, 'Tambo simo', 'Hedric', '2002-06-04', 'Bameka', 'Mimboman', 'M', 182, 'Etudiant', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `demande_nationalite_details`
--

CREATE TABLE `demande_nationalite_details` (
  `DetailID` int(11) NOT NULL,
  `DemandeID` int(11) NOT NULL,
  `Nom` varchar(50) NOT NULL,
  `Prenom` varchar(50) NOT NULL,
  `DateNaissance` date NOT NULL,
  `LieuNaissance` varchar(100) NOT NULL,
  `NomPere` varchar(100) NOT NULL,
  `NomMere` varchar(100) NOT NULL,
  `Adresse` text NOT NULL,
  `Telephone` varchar(20) NOT NULL,
  `Motif` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demande_nationalite_details`
--

INSERT INTO `demande_nationalite_details` (`DetailID`, `DemandeID`, `Nom`, `Prenom`, `DateNaissance`, `LieuNaissance`, `NomPere`, `NomMere`, `Adresse`, `Telephone`, `Motif`) VALUES
(1, 2, 'Tambo Simo', 'Hedric', '2002-06-04', 'Bameka', 'Kamga Simplice', 'Edjimbi Nadine', 'Mimboman', '656774288', 'Pour une demande de CNI');

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

CREATE TABLE `documents` (
  `DocumentID` int(11) NOT NULL,
  `DemandeID` int(11) DEFAULT NULL,
  `TypeDocument` enum('Photo','CertificatNationalite','ActeNaissance','AncienneCNI','ActeMariage','JustificatifProfession','DecretNaturalisation','CasierJudiciaire','DeclarationPerte','acteNaissance') NOT NULL,
  `CheminFichier` varchar(255) NOT NULL,
  `DateTelechargement` timestamp NOT NULL DEFAULT current_timestamp(),
  `StatutValidation` enum('EnAttente','Approuve','Rejete') DEFAULT 'EnAttente',
  `Utilisateurid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`DocumentID`, `DemandeID`, `TypeDocument`, `CheminFichier`, `DateTelechargement`, `StatutValidation`, `Utilisateurid`) VALUES
(1, 1, 'Photo', '../../uploads/documents/66fea5171d010_Ariel.jpg', '2024-10-03 14:07:19', 'EnAttente', 3),
(2, 1, 'ActeNaissance', '../../uploads/documents/66fea5172743e_Capture d’écran 2024-07-18 114353.png', '2024-10-03 14:07:19', 'EnAttente', 3),
(3, 1, 'CertificatNationalite', '../../uploads/documents/66fea51727654_Capture d’écran 2024-07-18 115619.png', '2024-10-03 14:07:19', 'EnAttente', 3),
(4, 1, 'JustificatifProfession', '../../uploads/documents/66fea51729924_49b629a7724a307a86d7679a7a2d8990.jpg', '2024-10-03 14:07:19', 'EnAttente', 3),
(5, 2, 'ActeNaissance', '../../uploads/documents_nationalite/670060f076a83_DemandeCNI.pdf', '2024-10-04 21:41:04', 'EnAttente', 3);

-- --------------------------------------------------------

--
-- Structure de la table `historique_demandes`
--

CREATE TABLE `historique_demandes` (
  `HistoriqueID` int(11) NOT NULL,
  `DemandeID` int(11) NOT NULL,
  `AncienStatut` enum('Soumise','EnCours','Approuvee','Rejetee','Terminee') DEFAULT NULL,
  `NouveauStatut` enum('Soumise','EnCours','Approuvee','Rejetee','Terminee','Annulee') NOT NULL,
  `DateModification` timestamp NOT NULL DEFAULT current_timestamp(),
  `Commentaire` text DEFAULT NULL,
  `ModifiePar` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_demandes`
--

INSERT INTO `historique_demandes` (`HistoriqueID`, `DemandeID`, `AncienStatut`, `NouveauStatut`, `DateModification`, `Commentaire`, `ModifiePar`) VALUES
(1, 2, 'Soumise', 'Annulee', '2024-10-05 11:03:39', 'Demande annulée par l\'utilisateur', 3),
(2, 1, 'Soumise', 'Annulee', '2024-10-05 11:05:20', 'Demande annulée par l\'utilisateur', 3),
(3, 2, 'Soumise', 'Annulee', '2024-10-05 11:16:43', 'Demande annulée par l\'utilisateur', 3),
(4, 1, 'Soumise', 'Annulee', '2024-10-05 11:20:27', 'Demande annulée par l\'utilisateur', 3),
(5, 2, 'Soumise', 'Annulee', '2024-10-05 11:25:13', 'Demande annulée par l\'utilisateur', 3),
(6, 1, 'Soumise', 'Annulee', '2024-10-05 11:38:03', 'Demande annulée par l\'utilisateur', 3),
(7, 2, 'Soumise', 'Annulee', '2024-10-05 11:44:42', 'Demande annulée par l\'utilisateur', 3),
(8, 1, 'Soumise', 'Annulee', '2024-10-05 11:57:28', 'Demande annulée par l\'utilisateur', 3),
(9, 2, 'Soumise', 'Annulee', '2024-10-05 12:24:20', 'Demande annulée par l\'utilisateur', 3),
(10, 1, 'Soumise', 'Annulee', '2024-10-05 12:24:40', 'Demande annulée par l\'utilisateur', 3),
(11, 2, 'Soumise', 'Annulee', '2024-10-05 12:25:52', 'Demande annulée par l\'utilisateur', 3),
(12, 1, 'Soumise', 'Annulee', '2024-10-05 12:28:38', 'Demande annulée par l\'utilisateur', 3),
(13, 2, 'Soumise', 'Annulee', '2024-10-05 12:30:11', 'Demande annulée par l\'utilisateur', 3),
(14, 1, 'Soumise', 'Annulee', '2024-10-05 12:33:25', 'Demande annulée par l\'utilisateur', 3),
(15, 2, 'Soumise', 'Annulee', '2024-10-05 13:03:40', 'Demande annulée par l\'utilisateur', 3),
(16, 2, 'Soumise', 'Annulee', '2024-10-05 13:05:32', 'Demande annulée par l\'utilisateur', 3),
(17, 1, 'Soumise', 'Annulee', '2024-10-05 13:10:39', 'Demande annulée par l\'utilisateur', 3),
(18, 2, 'Soumise', 'Annulee', '2024-10-05 13:20:37', 'Demande annulée par l\'utilisateur', 3),
(19, 1, 'Soumise', 'Annulee', '2024-10-05 13:24:39', 'Demande annulée par l\'utilisateur', 3),
(20, 1, 'Soumise', 'EnCours', '2024-10-11 18:07:34', '', 7),
(21, 2, 'EnCours', 'Approuvee', '2024-10-13 23:27:21', 'Demande approuvée par le président', NULL),
(22, 2, 'EnCours', 'Approuvee', '2024-10-13 23:29:37', 'Demande approuvée par le président', NULL),
(23, 2, 'EnCours', 'Approuvee', '2024-10-13 23:35:30', 'Demande approuvée par le président', NULL),
(24, 2, 'EnCours', 'Approuvee', '2024-10-13 23:36:03', 'Demande approuvée par le président', NULL),
(25, 2, 'EnCours', 'Approuvee', '2024-10-13 23:37:15', 'Demande approuvée par le président', NULL),
(26, 2, 'EnCours', 'Approuvee', '2024-10-13 23:41:12', 'Demande approuvée par le président', NULL),
(27, 2, 'EnCours', 'Rejetee', '2024-10-13 23:46:03', 'non conforme', NULL),
(28, 2, 'EnCours', 'Rejetee', '2024-10-13 23:46:52', 'non conforme', NULL),
(29, 2, 'EnCours', 'Approuvee', '2024-10-13 23:47:23', 'Demande approuvée par le président', NULL),
(30, 2, 'EnCours', 'Approuvee', '2024-10-13 23:48:00', 'Demande approuvée par le président', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `journalactivites`
--

CREATE TABLE `journalactivites` (
  `JournalID` int(11) NOT NULL,
  `UtilisateurID` int(11) DEFAULT NULL,
  `TypeActivite` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `AdresseIP` varchar(45) DEFAULT NULL,
  `DateHeure` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lieuxretrait`
--

CREATE TABLE `lieuxretrait` (
  `LieuID` int(11) NOT NULL,
  `NomLieu` varchar(100) NOT NULL,
  `Adresse` text DEFAULT NULL,
  `NumeroContact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL,
  `UtilisateurID` int(11) DEFAULT NULL,
  `DemandeID` int(11) DEFAULT NULL,
  `Contenu` text NOT NULL,
  `TypeNotification` varchar(50) DEFAULT NULL,
  `EstLue` tinyint(1) DEFAULT 0,
  `DateCreation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`NotificationID`, `UtilisateurID`, `DemandeID`, `Contenu`, `TypeNotification`, `EstLue`, `DateCreation`) VALUES
(1, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:03:39'),
(2, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:05:20'),
(3, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:16:43'),
(4, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:20:27'),
(5, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:25:13'),
(6, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:38:03'),
(7, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:44:42'),
(8, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 11:57:28'),
(9, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 12:24:20'),
(10, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 12:24:40'),
(11, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 12:25:52'),
(12, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 12:28:38'),
(13, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 12:30:11'),
(14, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 12:33:25'),
(15, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 13:03:40'),
(16, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 13:05:32'),
(17, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 13:10:39'),
(18, 3, NULL, 'Votre demande N°2 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 13:20:37'),
(19, 3, NULL, 'Votre demande N°1 a été annulée avec succès.', 'Annulation', 0, '2024-10-05 13:24:39'),
(20, 3, 1, 'Votre demande de CNI a été approuvée et est en cours de traitement.', 'Approbation', 0, '2024-10-11 18:07:34');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `PaiementID` int(11) NOT NULL,
  `DemandeID` int(11) DEFAULT NULL,
  `Montant` decimal(10,2) NOT NULL,
  `DatePaiement` timestamp NOT NULL DEFAULT current_timestamp(),
  `StatutPaiement` enum('EnAttente','Complete','Echoue') NOT NULL,
  `ReferenceTransaction` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reclamations`
--

CREATE TABLE `reclamations` (
  `ReclamationID` int(11) NOT NULL,
  `UtilisateurID` int(11) DEFAULT NULL,
  `DemandeID` int(11) DEFAULT NULL,
  `TypeReclamation` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Statut` enum('Ouverte','EnCours','Fermee') DEFAULT 'Ouverte',
  `DateCreation` timestamp NOT NULL DEFAULT current_timestamp(),
  `DateMiseAJour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendezvous`
--

CREATE TABLE `rendezvous` (
  `RendezVousID` int(11) NOT NULL,
  `DemandeID` int(11) DEFAULT NULL,
  `DateRendezVous` datetime DEFAULT NULL,
  `Lieu` varchar(100) DEFAULT NULL,
  `Statut` enum('Planifie','Termine','Annule') DEFAULT 'Planifie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `role`
--

INSERT INTO `role` (`id`, `role`) VALUES
(1, 'Administrateur'),
(2, 'Citoyen'),
(3, 'Officier'),
(4, 'President');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `UtilisateurID` int(11) NOT NULL,
  `Codeutilisateur` varchar(50) NOT NULL,
  `MotDePasse` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `NumeroTelephone` varchar(20) DEFAULT NULL,
  `Prenom` varchar(50) DEFAULT NULL,
  `Nom` varchar(50) DEFAULT NULL,
  `DateNaissance` date DEFAULT NULL,
  `Adresse` text DEFAULT NULL,
  `RoleId` tinyint(4) NOT NULL,
  `DateCreation` timestamp NOT NULL DEFAULT current_timestamp(),
  `DateMiseAJour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `PhotoUtilisateur` varchar(255) DEFAULT NULL,
  `Genre` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`UtilisateurID`, `Codeutilisateur`, `MotDePasse`, `Email`, `NumeroTelephone`, `Prenom`, `Nom`, `DateNaissance`, `Adresse`, `RoleId`, `DateCreation`, `DateMiseAJour`, `PhotoUtilisateur`, `Genre`, `IsActive`) VALUES
(2, 'Admin', '$2y$10$WmmK7dcL0pBovgmtHOTiAeSx7etZggFUiEWTiidwr2ppF73oZRzJW', 'simohedric2023@gmail.com', '656774288', 'Hedric', 'Simo', '2024-09-12', 'mimboman-chapelle', 3, '2024-09-12 06:12:08', '2024-09-21 19:16:44', NULL, 'Homme', 0),
(3, 'LT32237', '$2y$10$oJJcOwgG5WK/pA8QRWnHUem.sOxs6dmSNAKiIWF3AyZqFbGpdajSu', 'suprahedric2000@gmail.com', '656774288', 'Ariel', 'Meka', '2002-06-04', 'yaoundé', 2, '2024-09-12 21:36:34', '2024-10-05 20:13:53', 'uploads/profile_pictures/66fed5ae95ccd.jpg', 'M', 0),
(4, 'CEadmin', '$2y$10$zu0JPd7usEzePNS5JuMV2O/cajQU9OlWC7xqdJsl9Sc/IjsT2RcNm', 'admin@gmail.com', '656774288', 'Hedric', 'Simo', '2002-06-04', 'Yaoundé', 1, '2024-09-13 04:07:24', '2024-09-21 19:17:24', NULL, 'Homme', 0),
(5, 'OU237', '$2y$10$SMb8eGd13cMOJvLKp96biOJiTTlMi2MUmVAYk3EQUmfXcOJdoLTme', 'simohedric2002@gmail.com', '656774288', 'Hedric', 'Tambo Simo', '2002-06-04', 'mimboman-chapelle', 2, '2024-09-13 07:05:03', '2024-09-21 19:17:42', NULL, '2', NULL),
(6, 'AR237', '$2y$10$nM8QJl5xgIPbGGN5tTLarevXIctRwDUT75D2CJdHFiDNPitoYtaVC', 'Ariel2006@gmail.com', '656774288', 'Pascal', 'Meka', '2006-04-16', 'Nfou', 4, '2024-09-13 13:46:49', '2024-09-21 19:18:00', NULL, 'Femme', NULL),
(7, 'OF237', '$2y$10$iykXkv1kI6N.N7bgcFBuk.JCsXLvfsEPrsn5sFWQGRDjjz6GmrYKW', 'officier@gmail.com', '656774288', 'hedric', 'simo', NULL, NULL, 3, '2024-09-13 13:52:30', '2024-09-21 19:18:16', NULL, 'Homme', NULL),
(8, 'CNI', '$2y$10$f9VX1UUanfPv4ND6yQ3DHOJTJn6RCohKIXq4b005m0hsw37h83vrW', 'hedricariel2024@gmail.com', '656774288', 'Hedric', 'Tambo Simo', '2002-06-04', 'Yaoundé', 2, '2024-09-21 23:41:07', '2024-09-21 23:41:07', '../uploads/profile_pictures/66ef5993e27fb.png', 'Homme', NULL),
(10, 'CNI002238', '$2y$10$AGRXQ2Mx3ThnEJt9AJLesuwim52nE/R6FKYzeob1wXpra294mSeuy', 'Ariel2002@gmail.com', '692042589', 'Ariel', 'Meka Keigne', '2006-04-16', 'Mfou', 2, '2024-09-22 20:00:45', '2024-10-13 19:44:14', '../uploads/profile_pictures/66f0776d02f5b.jpg', 'Femme', 0),
(11, 'CNI002239', '$2y$10$JITcBL3UvSWt/7k83Q1aDeBUOfeYRmWJUnrgsv39Lcd2LaW7OiUbe', 'simoariel@gmail.com', '656774288', 'Ariel', 'Simo', '2006-04-16', 'Yaounde', 2, '2024-09-25 11:44:08', '2024-10-13 19:39:23', '../uploads/profile_pictures/66f3f788736b5.jpg', 'Femme', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cartesidentite`
--
ALTER TABLE `cartesidentite`
  ADD PRIMARY KEY (`CarteID`),
  ADD UNIQUE KEY `NumeroCarteIdentite` (`NumeroCarteIdentite`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `certificatsnationalite`
--
ALTER TABLE `certificatsnationalite`
  ADD PRIMARY KEY (`CertificatID`),
  ADD UNIQUE KEY `NumeroCertificat` (`NumeroCertificat`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `demandes`
--
ALTER TABLE `demandes`
  ADD PRIMARY KEY (`DemandeID`),
  ADD KEY `UtilisateurID` (`UtilisateurID`);

--
-- Index pour la table `demande_cni_details`
--
ALTER TABLE `demande_cni_details`
  ADD PRIMARY KEY (`DetailID`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `demande_nationalite_details`
--
ALTER TABLE `demande_nationalite_details`
  ADD PRIMARY KEY (`DetailID`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`DocumentID`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `historique_demandes`
--
ALTER TABLE `historique_demandes`
  ADD PRIMARY KEY (`HistoriqueID`),
  ADD KEY `DemandeID` (`DemandeID`),
  ADD KEY `ModifiePar` (`ModifiePar`);

--
-- Index pour la table `journalactivites`
--
ALTER TABLE `journalactivites`
  ADD PRIMARY KEY (`JournalID`),
  ADD KEY `UtilisateurID` (`UtilisateurID`);

--
-- Index pour la table `lieuxretrait`
--
ALTER TABLE `lieuxretrait`
  ADD PRIMARY KEY (`LieuID`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `UtilisateurID` (`UtilisateurID`),
  ADD KEY `idx_notifications_demande` (`DemandeID`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`PaiementID`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `reclamations`
--
ALTER TABLE `reclamations`
  ADD PRIMARY KEY (`ReclamationID`),
  ADD KEY `UtilisateurID` (`UtilisateurID`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `rendezvous`
--
ALTER TABLE `rendezvous`
  ADD PRIMARY KEY (`RendezVousID`),
  ADD KEY `DemandeID` (`DemandeID`);

--
-- Index pour la table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`UtilisateurID`),
  ADD UNIQUE KEY `NomUtilisateur` (`Codeutilisateur`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cartesidentite`
--
ALTER TABLE `cartesidentite`
  MODIFY `CarteID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `certificatsnationalite`
--
ALTER TABLE `certificatsnationalite`
  MODIFY `CertificatID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demandes`
--
ALTER TABLE `demandes`
  MODIFY `DemandeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `demande_cni_details`
--
ALTER TABLE `demande_cni_details`
  MODIFY `DetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `demande_nationalite_details`
--
ALTER TABLE `demande_nationalite_details`
  MODIFY `DetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `documents`
--
ALTER TABLE `documents`
  MODIFY `DocumentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `historique_demandes`
--
ALTER TABLE `historique_demandes`
  MODIFY `HistoriqueID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `journalactivites`
--
ALTER TABLE `journalactivites`
  MODIFY `JournalID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lieuxretrait`
--
ALTER TABLE `lieuxretrait`
  MODIFY `LieuID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `PaiementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reclamations`
--
ALTER TABLE `reclamations`
  MODIFY `ReclamationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rendezvous`
--
ALTER TABLE `rendezvous`
  MODIFY `RendezVousID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `UtilisateurID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `cartesidentite`
--
ALTER TABLE `cartesidentite`
  ADD CONSTRAINT `cartesidentite_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`);

--
-- Contraintes pour la table `certificatsnationalite`
--
ALTER TABLE `certificatsnationalite`
  ADD CONSTRAINT `certificatsnationalite_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`);

--
-- Contraintes pour la table `demandes`
--
ALTER TABLE `demandes`
  ADD CONSTRAINT `demandes_ibfk_1` FOREIGN KEY (`UtilisateurID`) REFERENCES `utilisateurs` (`UtilisateurID`);

--
-- Contraintes pour la table `demande_cni_details`
--
ALTER TABLE `demande_cni_details`
  ADD CONSTRAINT `demande_cni_details_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demande_nationalite_details`
--
ALTER TABLE `demande_nationalite_details`
  ADD CONSTRAINT `demande_nationalite_details_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`);

--
-- Contraintes pour la table `historique_demandes`
--
ALTER TABLE `historique_demandes`
  ADD CONSTRAINT `historique_demandes_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `historique_demandes_ibfk_2` FOREIGN KEY (`ModifiePar`) REFERENCES `utilisateurs` (`UtilisateurID`);

--
-- Contraintes pour la table `journalactivites`
--
ALTER TABLE `journalactivites`
  ADD CONSTRAINT `journalactivites_ibfk_1` FOREIGN KEY (`UtilisateurID`) REFERENCES `utilisateurs` (`UtilisateurID`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`UtilisateurID`) REFERENCES `utilisateurs` (`UtilisateurID`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`);

--
-- Contraintes pour la table `reclamations`
--
ALTER TABLE `reclamations`
  ADD CONSTRAINT `reclamations_ibfk_1` FOREIGN KEY (`UtilisateurID`) REFERENCES `utilisateurs` (`UtilisateurID`),
  ADD CONSTRAINT `reclamations_ibfk_2` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`);

--
-- Contraintes pour la table `rendezvous`
--
ALTER TABLE `rendezvous`
  ADD CONSTRAINT `rendezvous_ibfk_1` FOREIGN KEY (`DemandeID`) REFERENCES `demandes` (`DemandeID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
