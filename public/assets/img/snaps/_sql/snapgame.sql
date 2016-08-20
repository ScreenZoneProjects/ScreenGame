-- phpMyAdmin SQL Dump
-- version 4.6.3
-- https://www.phpmyadmin.net/
--
-- Client :  localhost
-- Généré le :  Mer 10 Août 2016 à 03:45
-- Version du serveur :  5.7.14
-- Version de PHP :  5.5.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `snapgame`
--

-- --------------------------------------------------------

--
-- Structure de la table `snap_games`
--

CREATE TABLE `snap_games` (
  `game_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT 'Unknown',
  `filename` varchar(256) NOT NULL DEFAULT 'unknown.png'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `snap_games`
--

INSERT INTO `snap_games` (`game_id`, `name`, `filename`) VALUES
(2, 'Sonic the Hedgehog', 'sonic_the_hedgehog.png'),
(3, 'Street of Rage 2', 'streets_of_rage_2.png'),
(4, 'Final Fight 3', 'final_fight_3.png');

-- --------------------------------------------------------

--
-- Structure de la table `snap_scores`
--

CREATE TABLE `snap_scores` (
  `score_id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT 'Anonymous',
  `score` int(11) NOT NULL DEFAULT '0',
  `session` varchar(64) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `snap_games`
--
ALTER TABLE `snap_games`
  ADD PRIMARY KEY (`game_id`);

--
-- Index pour la table `snap_scores`
--
ALTER TABLE `snap_scores`
  ADD PRIMARY KEY (`score_id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `snap_games`
--
ALTER TABLE `snap_games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
--
-- AUTO_INCREMENT pour la table `snap_scores`
--
ALTER TABLE `snap_scores`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;