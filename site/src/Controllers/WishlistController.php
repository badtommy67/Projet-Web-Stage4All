<?php
namespace App\Controllers;

use App\Models\WishlistModel;
use App\Utilities\Pagination;
use Twig\Environment;
use PDO;

class WishlistController {
    private WishlistModel $wishlistModel;
    private Environment $twig;

    public function __construct(Environment $twig, PDO $pdo) {
        $this->twig = $twig;
        $this->wishlistModel = new WishlistModel($pdo);
    }

    private function verifierPermissions(array $permissionsRequises) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['utilisateur'])) {
            header('Location: /connexion');
            exit;
        }

        $permissionsUtilisateur = [];
        if (isset($_SESSION['utilisateur']['permissions'])) {
            $permissionsUtilisateur = $_SESSION['utilisateur']['permissions'];
        }

        foreach ($permissionsRequises as $permission) {
            if (in_array($permission, $permissionsUtilisateur)) {
                return true;
            }
        }

        header('Location: /profil');
        exit;
    }

    public function redirection(string $adresse_url) {
        switch ($adresse_url) {
            case 'mes-favoris': 
                $this->afficherWishlist();
                break;
            case 'wishlist-ajouter':
                $this->ajouter();
                break;
            case 'wishlist-retirer':
                $this->retirer();
                break;
            default:
                header('Location: /404');
                exit;
        }
    }

    public function afficherWishlist() {
        $this->verifierPermissions(['SFx23 - Afficher les offres ajoutées à la wish-list']);

        $identifiant_utilisateur = (int) $_SESSION['utilisateur']['id'];
        
        $toutes_les_offres = $this->wishlistModel->getWishlistParEtudiant($identifiant_utilisateur);
        $identifiants_favoris = $this->wishlistModel->getWishlistIdsParEtudiant($identifiant_utilisateur);

        $pagination = new Pagination($toutes_les_offres, 9);
        $offres_de_la_page = $pagination->itemsPage();
        
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        
        $page_debut_affichage = max(1, $page_courante - 2);
        $page_fin_affichage = min($nombre_total_pages, $page_courante + 2);

        $parametres_adresse_url = http_build_query(['uri' => 'mes-favoris']);

        echo $this->twig->render('mes-favoris.twig.html', [
            'liste_offres' => $offres_de_la_page,
            'favoris_ids' => $identifiants_favoris,
            'session' => $_SESSION,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante'=> $page_courante,
            'page_debut'=> $page_debut_affichage,
            'page_fin'=> $page_fin_affichage,
            'parametres_url' => $parametres_adresse_url,
        ]);
    }

    public function ajouter() {
        $this->verifierPermissions(['SFx24 - Ajouter une offre à la wish-list']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offre_id'])) {
            $identifiant_offre = (int) $_POST['offre_id'];
            $identifiant_utilisateur = (int) $_SESSION['utilisateur']['id'];

            $this->wishlistModel->ajouter($identifiant_utilisateur, $identifiant_offre);
        }
        
        $provenance = '/profil';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $provenance = $_SERVER['HTTP_REFERER'];
        }
        header('Location: ' . $provenance);
        exit;
    }

    public function retirer() {
        $this->verifierPermissions(['SFx25 - Retirer une offre à la wish-list']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offre_id'])) {
            $identifiant_offre = (int) $_POST['offre_id'];
            $identifiant_utilisateur = (int) $_SESSION['utilisateur']['id'];

            $this->wishlistModel->retirer($identifiant_utilisateur, $identifiant_offre);
        }

        $provenance = $_SERVER['HTTP_REFERER'] ?? '/profil';
        header('Location: ' . $provenance);
        exit;
    }
}