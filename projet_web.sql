-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3307
-- Généré le : lun. 02 juin 2025 à 06:42
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projet_web`
--

-- --------------------------------------------------------

--
-- Structure de la table `adm`
--

CREATE TABLE `adm` (
  `ID_ADM` int(11) NOT NULL,
  `NOM` varchar(50) DEFAULT NULL,
  `PRENOM` varchar(50) DEFAULT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  `PASSWRD` varchar(255) DEFAULT NULL,
  `AVATAR` varchar(255) DEFAULT NULL,
  `IMG` varchar(255) DEFAULT NULL,
  `poste` varchar(80) DEFAULT NULL,
  `GOOGLE_ID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `adm`
--

INSERT INTO `adm` (`ID_ADM`, `NOM`, `PRENOM`, `EMAIL`, `PASSWRD`, `AVATAR`, `IMG`, `poste`, `GOOGLE_ID`) VALUES
(25051802, 'Mouna', 'Soukaina', 'soukaina.mouna@uit.ac.ma', '$2y$10$hFb9HQK7X/.aI9qHVE8P7ePvQMs4X57fiLxhCYJuqp9GdyaNDFQS.', 'S', NULL, 'Administrateur', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `modules`
--

CREATE TABLE `modules` (
  `id_module` int(11) NOT NULL,
  `nom` varchar(120) DEFAULT NULL,
  `ID_PROF` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `modules`
--

INSERT INTO `modules` (`id_module`, `nom`, `ID_PROF`) VALUES
(0, 'tech_web', 123456);

-- --------------------------------------------------------

--
-- Structure de la table `prof`
--

CREATE TABLE `prof` (
  `ID_PROF` int(11) NOT NULL,
  `NOM` varchar(50) DEFAULT NULL,
  `PRENOM` varchar(50) DEFAULT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  `DEPARTEMENT` varchar(100) DEFAULT NULL,
  `PASSWRD` varchar(255) DEFAULT NULL,
  `AVATAR` varchar(255) DEFAULT NULL,
  `IMG` varchar(255) DEFAULT NULL,
  `MOD_ENS` varchar(120) DEFAULT NULL,
  `POSTE` varchar(150) DEFAULT NULL,
  `GOOGLE_ID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `prof`
--

INSERT INTO `prof` (`ID_PROF`, `NOM`, `PRENOM`, `EMAIL`, `DEPARTEMENT`, `PASSWRD`, `AVATAR`, `IMG`, `MOD_ENS`, `POSTE`, `GOOGLE_ID`) VALUES
(12389, 'Mouna', 'soukaina', 'soukaina.mouna@uit.ac.ma', 'Département de génie électrique, des réseaux et des systèmes des télécommunications', '$2y$10$1R24gA9jO/eBFO0Q4l.bZuA.9TQPR0xqArlLM8t8ijfYjVkQ55SaW', 'P', NULL, NULL, 'PROFESSEUR', NULL),
(123456, 'Oumaira', 'Ilham', 'ilham.oumaira@uit.ac.ma', 'Informatique', '$2y$10$iZLCO2iwtpZHggZP32NAxugFy6FqtliZcLwKdLs4TRlK8hEopbCJG', 'I', 'uploads/img_683d268d217549.18496016.jpg', NULL, '', NULL),
(129872, 'Saad', 'Aouatif', 'saad.aouatif@uit.ac.ma', '', '$2y$10$iBNLwNoswjPpU.mYw8rpaewF1pCsEqZxfSSsLwasQV3/tB4juS4Vq', 'A', NULL, NULL, NULL, NULL),
(435272, 'El Bouayadi', 'Rachid', 'rachid.elbouayadi@uit.ac.ma', 'Département de génie électrique, des réseaux et des systèmes des télécommunications', '$2y$10$ecRd0WsEFdyy5zjC/wFq0Ozk9YUD5Oik.TKbisJghVm2/gP3OB3Ve', 'R', NULL, NULL, NULL, NULL),
(729922, 'Kissi', 'Chaimaa', 'chaimaa.kissi@uit.ac.ma', 'Département de génie électrique, des réseaux et des systèmes des télécommunications', '$2y$10$qNCnZXSB47JBMFR1X5iX1uElYvA1oN0a5JlvqbWK2CjsRwjh.FnYa', 'C', NULL, NULL, NULL, NULL),
(786252, 'Boujiha', 'Tarik', 'tarik.boujiha@uit.ac.ma', 'Département de génie électrique, des réseaux et des systèmes des télécommunications', '$2y$10$JaQm01XWWB26YAwRUYYuy.SfpnF0e.C5xX6F.yB063TmsCs.YoneS', 'T', NULL, NULL, NULL, NULL),
(789123, 'Bannari', 'Rachid', 'rachid.bannari@uit.ac.ma', 'Département d\'informatique, de logistique et de mathématiques', '$2y$10$/QhtMNIl1vs2z8zhM6ak.uj0GqCrG76Q2gXqpZ7zY8FHuBvPmewZ6', 'R', NULL, NULL, '', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `project`
--

CREATE TABLE `project` (
  `ID_PROJECT` int(11) NOT NULL,
  `NOM` varchar(100) DEFAULT NULL,
  `DESCRIPTION` text DEFAULT NULL,
  `TYPE` varchar(50) DEFAULT NULL,
  `CATEGORIE` varchar(100) DEFAULT NULL,
  `FICHIER` varchar(255) DEFAULT NULL,
  `FILE1` varchar(255) DEFAULT NULL,
  `FILE2` varchar(255) DEFAULT NULL,
  `FILE3` varchar(255) DEFAULT NULL,
  `AIMER` int(11) DEFAULT NULL,
  `CLAP` int(11) DEFAULT NULL,
  `SUPPORT` int(11) DEFAULT NULL,
  `NOTE` decimal(4,2) DEFAULT NULL,
  `STATUT_STU` varchar(50) DEFAULT NULL,
  `COMENTS` text DEFAULT NULL,
  `IMG` varchar(255) DEFAULT NULL,
  `ID_PROF` int(11) DEFAULT NULL,
  `APOGEE` int(11) DEFAULT NULL,
  `DATE_DEP` date DEFAULT NULL,
  `SEMESTRE` varchar(100) DEFAULT NULL,
  `TECHNOLOGIE` varchar(500) DEFAULT NULL,
  `etat` varchar(120) DEFAULT NULL,
  `STATUT` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `project`
--

INSERT INTO `project` (`ID_PROJECT`, `NOM`, `DESCRIPTION`, `TYPE`, `CATEGORIE`, `FICHIER`, `FILE1`, `FILE2`, `FILE3`, `AIMER`, `CLAP`, `SUPPORT`, `NOTE`, `STATUT_STU`, `COMENTS`, `IMG`, `ID_PROF`, `APOGEE`, `DATE_DEP`, `SEMESTRE`, `TECHNOLOGIE`, `etat`, `STATUT`) VALUES
(75, 'Optimisation des flux logistiques dans un entrepôt', 'Analyser les flux de marchandises et proposer un réaménagement pour réduire les temps de traitement de 20%.', 'stage pfa', 'optimisation', '683d16d83c16f_documents de travailEXAM2022-2023.zip', '683d16d83d501_chapitre-i-generalites-sur-les-lignes-de-transmissions-s6-3l-telecom.pdf', '683d16d84287a_résumé php.docx', NULL, NULL, NULL, NULL, NULL, 'validé', NULL, '683d16d8586af_ai.jpg', 435272, 22015155, '2025-06-02', 's8', 'python', NULL, NULL),
(76, 'Amélioration de la productivité d\'une ligne d\'assemblage', 'Diagnostic des goulots d\'étranglement et proposition de solutions correctives.\r\n', 'stage d\'observation', 'iot', '683d17a36f018_documents de travailEXAM2022-2023.zip', '683d17a36f726_Critical Thinking Skills for Students Education Presentation in White Yellow and Blue Flat Graphic Style.pptx', '683d17a36fcd4_sources_presentation.docx', NULL, NULL, NULL, NULL, NULL, 'en attente', NULL, '683d17a370263_animation.jpg', 729922, 22015155, '2025-06-02', 's6', 'packet tracer', NULL, NULL),
(77, 'Conception d\'un système de gestion des déchets industriels', 'Modélisation d\'un circuit de tri et valorisation des déchets.', 'module', 'ia', '683d18416f407_documents de travailEXAM2022-2023.zip', '683d18416fe33_chapitre-i-generalites-sur-les-lignes-de-transmissions-s6-3l-telecom.pdf', '683d18417035d_résumé php.docx', NULL, NULL, NULL, NULL, NULL, 'validé', NULL, '683d1841706ea_C2-2.png', 786252, 22015155, '2025-06-02', 's4', 'React,autocat,excel', NULL, NULL),
(78, 'Automatisation d\'un processus de contrôle qualité', 'Développement d\'un système de détection automatique des défauts.\r\n', 'stage pfe', 'web', '683d1cb6d28ae_documents de travailEXAM2022-2023.zip', '683d1cb6d2f6e_chapitre-i-generalites-sur-les-lignes-de-transmissions-s6-3l-telecom.pdf', '683d1cb6d3480_résumé php.docx', NULL, NULL, NULL, NULL, NULL, 'validé', NULL, '683d1cb6d3818_WhatsApp Image 2025-06-02 à 04.18.05_f2dfc9fd.jpg', 789123, 22014510, '2025-06-02', 's10', 'php,js,html', NULL, NULL),
(79, 'Simulation de flux de production avec Arena', ' Modélisation et optimisation des ressources de production.\r\n', 'module', 'mobile', '683d1d3791a87_documents de travailEXAM2022-2023.zip', '683d1d3791fc6_chapitre-i-generalites-sur-les-lignes-de-transmissions-s6-3l-telecom.pdf', '683d1d3792581_modélisation.py', NULL, NULL, NULL, NULL, NULL, 'en attente', NULL, '683d1d3792953_capes-externe-compte-seulement-1-28-candidat-poste-2024.webp', 786252, 22014510, '2025-06-02', 's6', 'html,python', NULL, NULL),
(80, 'Simulation de flux de production avec Arena', 'Modélisation et optimisation des ressources de production.\r\n', 'stage pfa', 'mobile', '683d1e2a5b4fc_Examen RO.zip', '683d1e2a5bbae_modélisation.py', '683d1e2a5c082_résumé php.docx', NULL, NULL, NULL, NULL, 14.00, 'en attente', 'good job', '683d1e2a5c443_design.jpg', 123456, 22014510, '2025-06-02', 's7', 'Capteurs de mouvement, Analyse RULA', 'V', 'EVALUE'),
(81, 'Étude ergonomique d\'un poste de travail', 'Évaluation des risques et propositions d\'améliorations.\r\n', 'module', 'iot', '683d1f084c495_documents de travailEXAM2022-2023.zip', '683d1f084cbf0_TP-4 (2).pdf', '683d1f084d04f_Rapport_ENSA_P_                                    _                                        1                                    _                                _2025-06-01.pdf', NULL, NULL, NULL, NULL, NULL, 'en attente', NULL, '683d1f084d417_C4-1.png', 123456, 22014526, '2025-06-02', 's8', 'React, Node.js, MongoDB', 'R', NULL),
(82, 'Système de reconnaissance faciale pour la présence en amphi', 'Solution automatisée pour l\'émargement électronique.\r\n', 'module', 'ia', '683d1f66dff30_Examen RO.zip', '683d1f66e1ff6_application.html', '683d1f66e2552_chapitre-i-generalites-sur-les-lignes-de-transmissions-s6-3l-telecom.pdf', '683d1f66e971e_modélisation.py', NULL, NULL, NULL, NULL, 'en attente', NULL, '683d1f9067e1d_capes-externe-compte-seulement-1-28-candidat-poste-2024.webp', 123456, 22014526, '2025-06-02', 's3', 'Python, OpenCV, TensorFlow', NULL, NULL),
(83, 'Application mobile de suivi énergétique', 'Visualisation en temps réel de la consommation énergétique.', 'stage pfe', 'iot', '683d208ef3093_documents de travailEXAM2022-2023.zip', '683d208ef361a_chapitre-i-generalites-sur-les-lignes-de-transmissions-s6-3l-telecom.pdf', '683d208ef3a73_modélisation.py', NULL, NULL, NULL, NULL, NULL, 'validé', NULL, '683d208ef3e1f_ai.jpg', 789123, 22014526, '2025-06-02', 's10', ' IoT/Mobile', NULL, NULL),
(84, 'Outil d\'analyse de sentiment sur les réseaux sociaux', 'Classification automatique des opinions sur un produit.\r\n', 'stage pfe', 'ia', '683d2128971de_Examen RO.zip', '683d21289780b_TP-4 (2).pdf', '683d212897d34_Critical Thinking Skills for Students Education Presentation in White Yellow and Blue Flat Graphic Style.pptx', '683d2128981cd_Frame_Critical_Remarks_Soukaina_Mouna.docx', NULL, NULL, NULL, NULL, 'en attente', NULL, '683d21289851c_development.jpg', 729922, 24016071, '2025-06-02', 's9', 'Python, NLTK, Transformers', NULL, NULL),
(85, 'Jeu éducatif en réalité augmentée', 'Application ludo-éducative pour enfants.\r\n', 'stage d\'observation', 'web', '683d218f77655_documents de travailEXAM2022-2023.zip', '683d218f77d1f_application.html', '683d218f78169_modélisation.py', NULL, NULL, NULL, NULL, NULL, 'validé', NULL, '683d218f784d8_WhatsApp-Image-2025-04-02-at-16.29.55-480x290.jpeg', 789123, 24016071, '2025-06-02', 's7', 'Unity, ARCore, C#', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `student`
--

CREATE TABLE `student` (
  `APOGEE` int(11) NOT NULL,
  `NOM` varchar(50) DEFAULT NULL,
  `PRENOM` varchar(50) DEFAULT NULL,
  `EMAIL_INST` varchar(100) DEFAULT NULL,
  `PASSWRD` varchar(255) DEFAULT NULL,
  `FILIERE` varchar(50) DEFAULT NULL,
  `NIV` varchar(20) DEFAULT NULL,
  `AVATAR` varchar(255) DEFAULT NULL,
  `IMG` varchar(255) DEFAULT NULL,
  `GOOGLE_ID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `student`
--

INSERT INTO `student` (`APOGEE`, `NOM`, `PRENOM`, `EMAIL_INST`, `PASSWRD`, `FILIERE`, `NIV`, `AVATAR`, `IMG`, `GOOGLE_ID`) VALUES
(22014510, 'Harcharras', 'Marwa', 'marwa.harcharras@uit.ac.ma', '$2y$10$uc713cyp4j9mrr9zi67REeFSq4lANNnLq3a7TyPpdQNjqXddCx8Gq', 'Génie Industriel', 'CI1', 'M', 'uploads/profiles/profile_22014510.webp', NULL),
(22014526, 'Rhayour', 'Hind', 'hind.rhayour@uit.ac.ma', '$2y$10$GWanwbhrHD9mP0on37wIveFchEYG2PUp.df.ErHJZShxhkRyyI6PW', 'Génie Informatique', 'CI1', 'H', NULL, NULL),
(22015155, 'Mouna', 'Soukaina', 'soukaina.mouna@uit.ac.ma', '$2y$10$FzUSV2KTTMBI9DiEuIgC6eRGc1E2soRcVV8bcAciSETDuql9I5u1m', 'Génie Informatique', 'CI2', 'S', NULL, NULL),
(24016071, 'Bouhou', 'Fatima', 'fatima.bouhou@uit.ac.ma', '$2y$10$QEhwmSoJHv7GEfyAoLPgcO4O582oWm64FkWnvjv5.M5272nr2tMFO', 'Génie Informatique', 'CI1', 'F', NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `adm`
--
ALTER TABLE `adm`
  ADD PRIMARY KEY (`ID_ADM`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- Index pour la table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id_module`),
  ADD KEY `id_prof` (`ID_PROF`);

--
-- Index pour la table `prof`
--
ALTER TABLE `prof`
  ADD PRIMARY KEY (`ID_PROF`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- Index pour la table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`ID_PROJECT`),
  ADD KEY `ID_PROF` (`ID_PROF`),
  ADD KEY `APOGEE` (`APOGEE`);

--
-- Index pour la table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`APOGEE`),
  ADD UNIQUE KEY `EMAIL_INST` (`EMAIL_INST`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `adm`
--
ALTER TABLE `adm`
  MODIFY `ID_ADM` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25051803;

--
-- AUTO_INCREMENT pour la table `prof`
--
ALTER TABLE `prof`
  MODIFY `ID_PROF` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1234568;

--
-- AUTO_INCREMENT pour la table `project`
--
ALTER TABLE `project`
  MODIFY `ID_PROJECT` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`ID_PROF`) REFERENCES `prof` (`ID_PROF`);

--
-- Contraintes pour la table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `project_ibfk_1` FOREIGN KEY (`ID_PROF`) REFERENCES `prof` (`ID_PROF`),
  ADD CONSTRAINT `project_ibfk_2` FOREIGN KEY (`APOGEE`) REFERENCES `student` (`APOGEE`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
