<?php

namespace App\Controllers;

use App\Models\StatistiqueModel;

class StatistiqueController {
    private $twig;
    private $pdo;
    private $statistiqueModel;

    public function __construct($twig, $pdo) {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->statistiqueModel = new StatistiqueModel($pdo); 
    }

    public function redirection($adresse_url) {
        if ($adresse_url === 'statistiques') {
            $this->afficherStatistiques();
        } else {
            echo $this->twig->render('404.twig.html');
        }
    }

    private function afficherStatistiques() {

        $nombre_total_offres = $this->statistiqueModel->getTotalOffres();
        $nombre_total_candidatures = $this->statistiqueModel->getTotalCandidatures();

        if ($nombre_total_offres > 0) {
            $moyenne_candidatures = round($nombre_total_candidatures / $nombre_total_offres, 1); // round arrondi un nombre a l'entier le plus proche
        } else {
            $moyenne_candidatures = 0;
        }

        $repartition_brute_duree = $this->statistiqueModel->getRepartitionDuree();
        $pourcentage_duree_courte = 0;
        $pourcentage_duree_moyenne = 0;
        $pourcentage_duree_longue = 0;

        if ($nombre_total_offres > 0 && $repartition_brute_duree) {
            $pourcentage_duree_courte = round(($repartition_brute_duree['duree_courte'] / $nombre_total_offres) * 100);
            $pourcentage_duree_moyenne = round(($repartition_brute_duree['duree_moyenne'] / $nombre_total_offres) * 100);
            $pourcentage_duree_longue = round(($repartition_brute_duree['duree_longue'] / $nombre_total_offres) * 100);
        }

        $liste_meilleures_offres_souhaitees = $this->statistiqueModel->getTopWishlist();

        echo $this->twig->render('statistiques.twig.html', [
            'total_offres' => $nombre_total_offres,
            'moyenne_candidatures' => $moyenne_candidatures,
            'duree_courte' => $pourcentage_duree_courte,
            'duree_moyenne' => $pourcentage_duree_moyenne,
            'duree_longue' => $pourcentage_duree_longue,
            'top_wishlist' => $liste_meilleures_offres_souhaitees
        ]);
    }
}