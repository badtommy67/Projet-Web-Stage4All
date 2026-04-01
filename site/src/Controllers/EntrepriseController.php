<?php
namespace App\Controllers;

use App\Models\EntrepriseModel;
use App\Utilities\Pagination;

class EntrepriseController extends Controller {

    public function redirection($uri) {
        // gestion des redirections et appelle des methodes pour les cas particuliers
        match($uri) {
            'entreprises' => $this->liste(),
            'detail-entreprise' => $this->detail(),
            'creer-entreprise' => $this->creer(),
            'modifier-entreprise' => $this->modifier(),
            'supprimer-entreprise' => $this->supprimer(),
            'gestion-entreprises' => $this->gestion(),
            default => $this->twig->render($uri . '.twig.html')
        };
    }

    public function liste() {
        // affiche la liste des entreprises avec filtres et pagination

        // recuperation des elements GET
        $recherche_nom = '';
        if (isset($_GET['nom_entreprise'])) {
            $recherche_nom = trim($_GET['nom_entreprise']); // trim enleve les espaces inutiles
        }

        $recherche_ville = '';
        if (isset($_GET['ville_entreprise'])) {
            $recherche_ville = trim($_GET['ville_entreprise']);
        }

        $recherche_secteur = '';
        if (isset($_GET['secteur_entreprise'])) {
            $recherche_secteur = trim($_GET['secteur_entreprise']);
        }

        $entrepriseModel = new EntrepriseModel($this->pdo);
        $evaluationModel = new \App\Models\EvaluationModel($this->pdo);

        // on donne les elements necessaires a la requete
        $toutes_les_entreprises = $entrepriseModel->rechercherEntreprises($recherche_nom, $recherche_ville, $recherche_secteur);
        $liste_secteurs = $entrepriseModel->getTousLesSecteurs();
        $pagination = new Pagination($toutes_les_entreprises, 9);
        $entreprises_de_la_page = $pagination->itemsPage();
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        $nombre_total_entreprises = count($toutes_les_entreprises);
        $page_debut = max(1, $page_courante - 2);
        $page_fin = min($nombre_total_pages, $page_courante + 2);


        foreach ($entreprises_de_la_page as &$entreprise) {
            $stats = $evaluationModel->obtenirMoyenneEntreprise($entreprise['entreprise_id']);
            $entreprise['moyenne_evaluation'] = $stats['note_moyenne'];
            $entreprise['nombre_avis'] = $stats['nombre_avis'];
        }

        $secteur = '';
        // on verifie si l'utilisateur a mis un secteur (soit different de celui par defaut)
        if ($recherche_secteur !== 'Filtrer par domaine') {
            $secteur = $recherche_secteur;
        }
        

        $parametres_url = http_build_query(array_filter([
            'nom_entreprise' => $recherche_nom,
            'ville_entreprise' => $recherche_ville,
            'secteur_entreprise' => $secteur
        ]));
        // array_filter supprime toutes les valeurs vides : '', null, false - le nombre de champ peut se reduire
        // http_build_query transforme un tableau en url
        // ex : 
        /* [
            'ville_entreprise' => 'Paris',
            'uri' => 'entreprises'
        ]
        devient ville_entreprise=Paris&uri=entreprises
        */

        echo $this->twig->render('entreprises.twig.html', [
            'liste_entreprises' => $entreprises_de_la_page,
            'liste_secteurs' => $liste_secteurs,
            'nombre_total_entreprises' => $nombre_total_entreprises,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante' => $page_courante,
            'page_debut' => $page_debut,
            'page_fin' => $page_fin,
            'recherche_nom' => $recherche_nom,
            'recherche_ville' => $recherche_ville,
            'recherche_secteur' => $recherche_secteur,
            'parametres_url' => $parametres_url,
        ]);
    }

    public function detail() {
        $entreprise_id = 0;

        if (isset($_GET['id'])) {
            $entreprise_id = (int) $_GET['id'];
        }

        $entrepriseModel = new EntrepriseModel($this->pdo);
        $evaluationModel = new \App\Models\EvaluationModel($this->pdo);

        $entreprise = $entrepriseModel->getEntrepriseParId($entreprise_id);

        // si l'entreprise n'existe pas, on affiche la page 404
        if (!$entreprise) {
            echo $this->twig->render('404.twig.html');
            return; 
        }

        $stats = $evaluationModel->obtenirMoyenneEntreprise($entreprise_id);
        $entreprise['moyenne_evaluation'] = $stats['note_moyenne'];
        $entreprise['nombre_avis'] = $stats['nombre_avis'];

        // on va chercher les offres associees a l'entreprise via l'id (cle) commun
        $liste_offres = $entrepriseModel->getOffresParEntrepriseId($entreprise_id);

        // construction de l'adresse pour la carte de localisation
        $adresse_complete = $entreprise['entreprise_rue'] . ', ' . $entreprise['cp_code'] . ' ' . $entreprise['ville_nom'];

        // encodage en url
        $map_url = "https://www.google.com/maps?q=" . urlencode($adresse_complete) . "&output=embed";

        echo $this->twig->render('detail-entreprise.twig.html', [
            'entreprise' => $entreprise,
            'liste_offres' => $liste_offres,
            'map_url' => $map_url
        ]);
    }

    public function creer() {
        $permissions = [];
        
        // on verifie si l'utilisateur est connecte, si oui, on recupere ses permissions
        if (isset($_SESSION['utilisateur']['permissions'])) {
            $permissions = $_SESSION['utilisateur']['permissions'];
        }

        // on verifie si l'utilisateur est connecte et qu'il a bien la permission de creer une entreprise
        if (!isset($_SESSION['utilisateur']) || !in_array('SFx3 - Créer une entreprise', $permissions)) {
            header('Location: /connexion');
            exit;
        }

        $entrepriseModel = new EntrepriseModel($this->pdo);
        $liste_secteurs  = $entrepriseModel->getTousLesSecteurs();

        // on verifie si le formulaire est envoye
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entrepriseModel->creerEntreprise($_POST); // on recupere tous les champs du formulaire
            $_SESSION['flash'] = 'Entreprise créée avec succès !'; // message de confirmation du bon envoi du formulaire
            header('Location: /gestion-entreprises'); // redirection apres creation de l'entreprise
            exit;
        }

        echo $this->twig->render('creer-entreprise.twig.html', [
            'secteurs' => $liste_secteurs,
        ]);
    }

    public function gestion() {
        // on recupere les permissions de l'utilisateur connecte. s'il n'existe pas -> tableau vide
        $permissions = [];

        if (isset($_SESSION['utilisateur']['permissions'])) {
            $permissions = $_SESSION['utilisateur']['permissions'];
        }


        // ici on verifie a la fois si l'utilisateur est connecte et ses permissions. on interdit alors l'acces si l'utilisateur est non connecte ou n'a pas les permissions requises
        if (!isset($_SESSION['utilisateur']) || !in_array('SFx3 - Créer une entreprise', $permissions)) {
            header('Location: /connexion'); // redirection vers la page de connexion
            exit;
        }

        $recherche_nom = '';
        if (isset($_GET['nom_entreprise'])) {
            $recherche_nom = trim($_GET['nom_entreprise']);
        }

        $recherche_ville = '';
        if (isset($_GET['ville_entreprise'])) {
            $recherche_ville = trim($_GET['ville_entreprise']);
        }

        $tri = '';
        if (isset($_GET['tri'])) {
            $tri = $_GET['tri'];
        }

        $entrepriseModel = new EntrepriseModel($this->pdo);
        $toutes_les_entreprises = $entrepriseModel->rechercherEntreprises($recherche_nom, $recherche_ville, '', $tri);

        $pagination = new Pagination($toutes_les_entreprises, 10);
        $entreprises_de_la_page = $pagination->itemsPage();
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        $page_debut = max(1, $page_courante - 2);
        $page_fin = min($nombre_total_pages, $page_courante + 2);

        $parametres_url = http_build_query(array_filter([
            'nom_entreprise' => $recherche_nom,
            'ville_entreprise' => $recherche_ville,
            'tri' => $tri
        ]));

        echo $this->twig->render('gestion-entreprises.twig.html', [
            'liste_entreprises' => $entreprises_de_la_page,
            'recherche_nom' => $recherche_nom,
            'recherche_ville'=> $recherche_ville,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante'=> $page_courante,
            'page_debut' => $page_debut,
            'page_fin' => $page_fin,
            'parametres_url' => $parametres_url,
            'tri'=> $tri
        ]);
    }

    public function modifier() {
        $permissions = [];

        if (isset($_SESSION['utilisateur'])) {
            if (isset($_SESSION['utilisateur']['permissions'])) {
                $permissions = $_SESSION['utilisateur']['permissions'];
            }
        }

        if (!isset($_SESSION['utilisateur']) || !in_array('SFx4 - Modifier une entreprise', $permissions)) {
            header('Location: /connexion');
            exit;
        }

        $entrepriseModel = new EntrepriseModel($this->pdo);

        $id = 0;
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }

        /*
        $_SERVER['REQUEST_METHOD'] === 'POST' detecte si le formulaire est envoye
        modifierEntreprise($id, $_POST) met a jour la bdd
        $_SESSION['flash'] message de confirmation
        header(...) redirige vers la liste des entreprises apres modification
        */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entrepriseModel->modifierEntreprise($id, $_POST);
            $_SESSION['flash'] = 'Entreprise modifiée avec succès !';
            header('Location: /gestion-entreprises');
            exit;
        }

        // on recupere les donnees existantes de l’entreprise pour afficher dans le formulaire (on pre-rempli les champs)
        $entreprise = $entrepriseModel->getEntreprisePourModification($id);
        
        // on verifie que l'entreprise existe bien, sinon redirection vers la page de gestion des entreprises
        if (!$entreprise) {
            header('Location: /gestion-entreprises');
            exit;
        }

        echo $this->twig->render('modifier-entreprise.twig.html', [
            'entreprise' => $entreprise,
            'secteurs' => $entrepriseModel->getTousLesSecteurs(),
        ]);
    }

    public function supprimer() {
        if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['permissions'])) {
            $permissions = $_SESSION['utilisateur']['permissions'];
        } else {
            $permissions = [];
        }

        if (!isset($_SESSION['utilisateur']) || !in_array('SFx6 - Supprimer une entreprise', $permissions)) {
            header('Location: /connexion');
            exit;
        }

        $entrepriseModel = new EntrepriseModel($this->pdo);
        $id = 0; // valeur par defaut

        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }

        if ($id === 0) {
            header('Location: /gestion-entreprises'); // aucun id valide n'est fourni on redirige vers la liste
            exit;
        }

        $entreprise = $entrepriseModel->getEntrepriseParId($id);
        if (!$entreprise) {
            header('Location: /gestion-entreprises'); // on verifie si l'entreprise existe bien, evite les erreurs
            exit;
        }

        // quand l'utilisateur clique sur supprimer une offre
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // on verifie si l'entreprise a des offres ou non.
            // si elle a des offres : archivage
            // si non : suppression
            $a_des_liens = $entrepriseModel->aDesOffresOuCandidatures($id);

            if ($a_des_liens) {
                $entrepriseModel->archiverEntreprise($id);
                $_SESSION['flash'] = 'Entreprise archivée car elle possède des offres ou candidatures.';
            } else {
                $entrepriseModel->supprimerEntreprise($id);
                $_SESSION['flash'] = 'Entreprise supprimée avec succès.';
            }

            header('Location: /gestion-entreprises');
            exit;
        }

        // on recupere le nombre d'offres liees a cette entreprise -> avertissement de l'utilisateur
        $nb_offres = $entrepriseModel->getNombreOffres($id);
        
        // a ce stade on ne supprime pas encore l'entreprise !
        echo $this->twig->render('supprimer-entreprise.twig.html', [
            'entreprise' => $entreprise,
            'nb_offres' => $nb_offres,
            'supprime' => false
        ]);
    }
}