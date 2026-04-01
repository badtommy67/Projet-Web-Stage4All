<?php
namespace App\Controllers;

use App\Models\OffreModel;
use App\Models\WishlistModel;
use App\Utilities\Pagination;

class OffreController extends Controller {

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

    public function redirection($adresse_url) {
        match($adresse_url) {
            'offres' => $this->liste(),
            'detail-offre'=> $this->detail(),
            'creer-offre' => $this->creer(),
            'modifier-offre' => $this->modifier(),
            'supprimer-offre'=> $this->supprimer(),
            'gestion-offres'=> $this->gestion(),
            'postuler' => $this->twig->render('postuler.twig.html'),
            default => $this->twig->render($adresse_url . '.twig.html')
        };
    }

    public function liste() {

        $recherche_mots_cles = '';
        if (isset($_GET['mots_cles'])) {
            $recherche_mots_cles = trim($_GET['mots_cles']);
        }

        $recherche_ville = '';
        if (isset($_GET['ville_offre'])) {
            $recherche_ville = trim($_GET['ville_offre']);
        }

        $recherche_duree = '';
        if (isset($_GET['duree_offre'])) {
            $recherche_duree = trim($_GET['duree_offre']);
        }

        $recherche_secteur = '';
        if (isset($_GET['secteur_offre'])) {
            $recherche_secteur = trim($_GET['secteur_offre']);
        }

        $recherche_tri = 'decroissant'; // valeur par défaut
        if (isset($_GET['tri_offre'])) {
            $recherche_tri = trim($_GET['tri_offre']);
        }

        $offreModel = new OffreModel($this->pdo);

        $toutes_les_offres = $offreModel->rechercherOffres($recherche_mots_cles, $recherche_ville, $recherche_duree, $recherche_secteur, $recherche_tri);
        $liste_secteurs = $offreModel->getTousLesSecteurs();

        $pagination = new Pagination($toutes_les_offres, 9);
        $offres_de_la_page = $pagination->itemsPage();
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        $nombre_total_offres = count($toutes_les_offres);

        $page_debut_affichage = max(1, $page_courante - 2);
        $page_fin_affichage = min($nombre_total_pages, $page_courante + 2);

        $parametres_adresse_url = http_build_query(array_filter([
            'mots_cles' => $recherche_mots_cles,
            'ville_offre' => $recherche_ville,
            'duree_offre' => $recherche_duree,
            'secteur_offre' => $recherche_secteur,
            'tri_offre' => $recherche_tri
        ]));

        $identifiants_favoris = [];
        if (isset($_SESSION['utilisateur']['id'])) {
            $wishlistModel = new WishlistModel($this->pdo);
            $identifiants_favoris = $wishlistModel->getWishlistIdsParEtudiant($_SESSION['utilisateur']['id']);
        }

        echo $this->twig->render('offres.twig.html', [
            'liste_offres'=> $offres_de_la_page,
            'liste_secteurs' => $liste_secteurs,
            'nombre_total_offres' => $nombre_total_offres,
            'nombre_total_pages'=> $nombre_total_pages,
            'page_courante' => $page_courante,
            'page_debut'=> $page_debut_affichage,
            'page_fin'=> $page_fin_affichage,
            'recherche_mots_cles' => $recherche_mots_cles,
            'recherche_ville' => $recherche_ville,
            'recherche_duree'=> $recherche_duree,
            'recherche_secteur' => $recherche_secteur,
            'recherche_tri' => $recherche_tri,
            'parametres_url'=> $parametres_adresse_url,
            'favoris_ids' => $identifiants_favoris,
        ]);
    }

    public function detail() {

        $identifiant_offre = 0; // valeur par défaut
        if (isset($_GET['id'])) {
            $identifiant_offre = (int) $_GET['id'];
        }

        $offreModel = new OffreModel($this->pdo);
        $offre = $offreModel->getOffreParId($identifiant_offre);

        if (!$offre) {
            echo $this->twig->render('404.twig.html');
            return;
        }

        $liste_competences = $offreModel->getCompetencesParOffreId($identifiant_offre);
        $nombre_candidatures = $offreModel->getNombreCandidatures($identifiant_offre);

        $identifiants_favoris = [];
        if (isset($_SESSION['utilisateur']['id'])) {
            $wishlistModel = new WishlistModel($this->pdo);
            $identifiants_favoris = $wishlistModel->getWishlistIdsParEtudiant($_SESSION['utilisateur']['id']);
        }

        echo $this->twig->render('detail-offre.twig.html', [
            'offre' => $offre,
            'liste_competences' => $liste_competences,
            'nb_candidatures' => $nombre_candidatures,
            'favoris_ids' => $identifiants_favoris,
        ]);
    }

    public function creer() {
        // verifie si l'utilisateur est connecte
        // verifie si la cle 'permissions' existe
        $this->verifierPermissions(['SFx8 - Créer une offre']);

        $offreModel = new OffreModel($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $prefixe = 'REF-';
            $annee_mois_courant = date('Ym'); // on recupere au format AAAAMM
            $nombre_offres_existantes = $this->pdo->query("SELECT COUNT(*) FROM OFFRE WHERE offre_archive = 0")->fetchColumn();
            $numero_nouvelle_offre = $nombre_offres_existantes + 1;
            $numero_formate = (string)$numero_nouvelle_offre;
            while (strlen($numero_formate) < 3) {
                $numero_formate = '0' . $numero_formate;
                // on veut obligatoirement 3 chiffres, donc on ajoute des zeros devant au besoin
            }

            $reference_generee = $prefixe . $annee_mois_courant . '-' . $numero_formate;
            $_POST['reference'] = $reference_generee;
            $offreModel->creerOffre($_POST);
            $_SESSION['flash'] = 'Offre créée avec succès !';
            header('Location: /gestion-offres');
            exit;
        }

        echo $this->twig->render('creer-offre.twig.html', [
            'entreprises' => $offreModel->getToutesLesEntreprises(),
            'competences' => $offreModel->getToutesLesCompetences(),
        ]);
    }

    public function gestion() {
        $this->verifierPermissions(['SFx8 - Créer une offre']);

        $recherche_nom_offre = '';
        if (isset($_GET['titre_offre'])) {
            $recherche_nom_offre = trim($_GET['titre_offre']);
        }

        $recherche_ville_offre = '';
        if (isset($_GET['ville_offre'])) {
            $recherche_ville_offre = trim($_GET['ville_offre']);
        }

        $recherche_tri_offre = '';
        if (isset($_GET['tri_offre'])) {
            $recherche_tri_offre = trim($_GET['tri_offre']);
        }

        $offreModel = new OffreModel($this->pdo);
        
        $toutes_les_offres_trouvees = $offreModel->rechercherOffres($recherche_nom_offre, $recherche_ville_offre, '', '', $recherche_tri_offre);

        $pagination = new Pagination($toutes_les_offres_trouvees, 10);
        $offres_de_la_page = $pagination->itemsPage();
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        $page_debut_affichage = max(1, $page_courante - 2);
        $page_fin_affichage = min($nombre_total_pages, $page_courante + 2);

        $parametres_adresse_url = http_build_query(array_filter([
            'titre_offre' => $recherche_nom_offre, 
            'ville_offre' => $recherche_ville_offre,
            'tri_offre' => $recherche_tri_offre
        ]));

        echo $this->twig->render('gestion-offres.twig.html', [
            'liste_offres' => $offres_de_la_page,
            'recherche_nom' => $recherche_nom_offre,
            'recherche_ville' => $recherche_ville_offre,
            'recherche_tri' => $recherche_tri_offre,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante'=> $page_courante,
            'page_debut' => $page_debut_affichage,
            'page_fin'=> $page_fin_affichage,
            'parametres_url' => $parametres_adresse_url,
        ]);
    }

    public function modifier() {
        $this->verifierPermissions(['SFx9 - Modifier une offre']);

        $offreModel = new OffreModel($this->pdo);
        $identifiant_offre = 0;
        if (isset($_GET['id'])) {
            $identifiant_offre = (int) $_GET['id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $offreModel->modifierOffre($identifiant_offre, $_POST);
            $_SESSION['flash'] = 'Offre modifiée avec succès !';
            header('Location: /gestion-offres');
            exit;
        }

        $offre = $offreModel->getOffreParId($identifiant_offre);
        if (!$offre) {
            header('Location: /gestion-offres');
            exit;
        }

        $competences_offre = $offreModel->getCompetencesParOffreId($identifiant_offre);

        echo $this->twig->render('modifier-offre.twig.html', [
            'offre' => $offre,
            'entreprises' => $offreModel->getToutesLesEntreprises(),
            'competences' => $offreModel->getToutesLesCompetences(),
            'competences_offre' => $competences_offre,
        ]);
    }

    public function supprimer() {
        $this->verifierPermissions(['SFx10 - Supprimer une offre']);

        $offreModel = new OffreModel($this->pdo);
        $identifiant_offre = 0;
        if (isset($_GET['id'])) {
            $identifiant_offre = (int) $_GET['id'];
        }

        if ($identifiant_offre === 0) {
            header('Location: /gestion-offres');
            exit;
        }

        $offre = $offreModel->getOffreParId($identifiant_offre);
        if (!$offre) {
            header('Location: /gestion-offres');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // comme pour les entreprises, ici on verifie si elle a des candidatures
            $possede_des_candidatures = $offreModel->aDesCandidatures($identifiant_offre);

            // on l'archive si elle a des candidatures et on la supprime sinon
            if ($possede_des_candidatures) {
                $offreModel->archiverOffre($identifiant_offre);
                $_SESSION['flash'] = 'Offre archivée car elle possède des candidatures.';
            } else {
                $offreModel->supprimerOffre($identifiant_offre);
                $_SESSION['flash'] = 'Offre supprimée avec succès.';
            }

            header('Location: /gestion-offres');
            exit;
        }

        $nombre_candidatures = $offreModel->getNombreCandidatures($identifiant_offre);

        echo $this->twig->render('supprimer-offre.twig.html', [
            'offre' => $offre,
            'nb_candidatures' => $nombre_candidatures,
        ]);
    }
}