<?php

namespace App\Models;

class StatistiqueModel {
    private $objet_pdo;

    public function __construct(\PDO $pdo) {
        $this->objet_pdo = $pdo;
    }

    // ici nous comptons le nombre total d'offres disponibles (non archivées)
    public function getTotalOffres() {
        $requete_sql = "SELECT COUNT(offre_id) AS total 
                FROM OFFRE 
                WHERE offre_archive = ?";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([0]);
        $resultat = $req->fetch(\PDO::FETCH_ASSOC);
        if (isset($resultat['total'])) {
            return $resultat['total'];
        } else {
            return 0;
        }
    }

    // ici nous avons le nombre total de candidatures faites
    public function getTotalCandidatures() {
        $requete_sql = "SELECT COUNT(candidature_id) AS total 
                FROM CANDIDATURE";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute();
        $resultat = $req->fetch(\PDO::FETCH_ASSOC);
        if (isset($resultat['total'])) {
            return $resultat['total'];
        }
        return 0;
    }

    // ici nous avons la repartition des offres par durée
    public function getRepartitionDuree() {
        // Optimisation : Utilisation de SUM CASE pour ne faire qu'une seule lecture de la table
        $requete_sql = "SELECT
                    SUM(CASE WHEN offre_duree <= 2 THEN 1 ELSE 0 END) AS duree_courte,
                    SUM(CASE WHEN offre_duree > 2 AND offre_duree <= 5 THEN 1 ELSE 0 END) AS duree_moyenne,
                    SUM(CASE WHEN offre_duree > 5 THEN 1 ELSE 0 END) AS duree_longue
                FROM OFFRE 
                WHERE offre_archive = ?";

        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([0]);
        return $req->fetch(\PDO::FETCH_ASSOC);
    }

    // ici nous avons le top 3 des offres dans les wishlists
    public function getTopWishlist() {
        // pour chaque offre, on compte combien de fois elle apparaît dans la table WISHLIST
        $requete_sql = "SELECT OFFRE.offre_titre
                FROM WISHLIST
                JOIN OFFRE ON WISHLIST.offre_id = OFFRE.offre_id
                GROUP BY OFFRE.offre_id, OFFRE.offre_titre
                ORDER BY COUNT(WISHLIST.utilisateur_id) DESC
                LIMIT 3";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute();
        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }
}