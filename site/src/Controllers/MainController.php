<?php
namespace App\Controllers;

use App\Models\OffreModel;
use App\Models\WishlistModel;

class MainController {
    private $twig;
    private $pdo;

    public function __construct($twig, $pdo = null) {
        $this->twig = $twig;
        $this->pdo  = $pdo;
    }


    // méthode pour afficher la page d'accueil
    /*public function home() {
        echo $this->twig->render('home.twig.html');
    }
    
    public function offres() {
        echo $this->twig->render('offres.twig.html');
        echo "La vue des offres arrive bientôt !";
    }

    public function entreprises() {
        echo $this->twig->render('entreprises.twig.html');
        echo "La vue des entreprises arrive bientôt !";
    }*/

    public function redirection($adresse_url) {
        if ($adresse_url === 'home') {
            $this->home();
        } else {
            echo $this->twig->render($adresse_url . '.twig.html');
        }
    }

    private function home() {
        $offreModel = new OffreModel($this->pdo);

        // on recupere les 3 offres les plus recentes
        $dernieres_offres_publiees = $offreModel->getDernieresOffres(3);

        $identifiants_favoris = [];
        if (isset($_SESSION['utilisateur']['id'])) {
            $wishlistModel = new WishlistModel($this->pdo);
            $identifiants_favoris = $wishlistModel->getWishlistIdsParEtudiant($_SESSION['utilisateur']['id']);
        }

        // on affiche la page en lui passant les offres
        echo $this->twig->render('home.twig.html', [
            'dernieres_offres' => $dernieres_offres_publiees,
            'favoris_ids' => $identifiants_favoris
        ]);
    }

}