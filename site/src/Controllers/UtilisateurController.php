<?php
namespace App\Controllers;

use App\Models\UtilisateurModel;
use App\Models\EntrepriseModel;
use App\Models\OffreModel;

class UtilisateurController extends Controller {

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
            'connexion'  => $this->connexion(),
            'profil' => $this->profil(),
            'gestion-utilisateurs' => $this->gestion(),
            'deconnexion' => $this->deconnexion(),
            'creer-utilisateur' => $this->creer(),
            'modifier-utilisateur' => $this->modifier(),
            'supprimer-utilisateur'=> $this->supprimer(),
            'mes-candidatures' => $this->mesCandidatures(),
            'mes-favoris' => $this->mesFavoris(),
            'suivi-eleves' => $this->suiviEleves(),
            default => $this->afficher($adresse_url)
        };
    }

    private function afficher($adresse_url) {
        echo $this->twig->render($adresse_url . '.twig.html');
    }

    public function connexion() {
        // on demarre une session si on a en pas deja creer une
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['utilisateur'])) {
            header('Location: /profil');
            exit;
        }


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adresse_email = '';
            if (isset($_POST['email'])) {
                $adresse_email = trim($_POST['email']);
            }

            $mot_de_passe = '';
            if (isset($_POST['password'])) {
                $mot_de_passe = trim($_POST['password']);
            }

            // on verifie que les deux champs ont bien ete remplis
            if ($adresse_email === '' || $mot_de_passe === '') {
                $message_erreur = 'Veuillez remplir tous les champs.';
            } else {
                $utilisateurModel = new UtilisateurModel($this->pdo);
                $utilisateur = $utilisateurModel->getUtilisateurParEmail($adresse_email);

                // on fait une verification du mot de passe. on prend le mot de passe saisie, on prend celui hashé (qui est dans la bdd) et on hashe le mot de passe initialement en clair avec password_verify, s'ils correspondent on valide, sinon erreur
                if (!$utilisateur || !password_verify($mot_de_passe, $utilisateur['utilisateur_mdp'])) {
                    $message_erreur = 'Email ou mot de passe incorrect.';
                } else {
                    $liste_permissions = $utilisateurModel->getPermissionsParRoleId($utilisateur['role_id']);

                    $_SESSION['utilisateur'] = [
                        'id' => $utilisateur['utilisateur_id'],
                        'prenom'=> $utilisateur['utilisateur_prenom'],
                        'nom'=> $utilisateur['utilisateur_nom'],
                        'email'=> $utilisateur['utilisateur_email'],
                        'role_id' => $utilisateur['role_id'],
                        'campus_id' => $utilisateur['campus_id'],
                        'promotion_id' => $utilisateur['promotion_id'],
                        'permissions' => $liste_permissions,
                    ];

                    header('Location: /profil');
                    exit;
                }
            }
        }

        echo $this->twig->render('connexion.twig.html', [
            'flash' => $message_erreur ?? '',
        ]);
    }

    public function deconnexion() {
        // cela peut paraitre absurde, mais si aucune session n'est demarree, on en creer une car on ne peut pas en supprimer une si elle n'existe pas
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy(); // destruction de la session
        echo $this->twig->render('deconnexion.twig.html');
    }

    public function profil() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['utilisateur'])) {
            header('Location: /connexion');
           exit;
        }

        $identifiant_utilisateur = $_SESSION['utilisateur']['id'];
        $liste_permissions = [];
        if (isset($_SESSION['utilisateur'])) {
            if (isset($_SESSION['utilisateur']['permissions'])) {
                $liste_permissions = $_SESSION['utilisateur']['permissions'];
            }
        }
        // une fois qu'on a recuperer les permissions de l'utilisateur on va pouvoir autoriser ou non certaines actions

        $utilisateurModel = new UtilisateurModel($this->pdo);
        $utilisateur = $utilisateurModel->getUtilisateurParId($identifiant_utilisateur);

        $liste_utilisateurs= [];
        $liste_entreprises = [];
        $liste_offres = [];
        $liste_candidatures = [];
        $liste_souhaits= [];
        $liste_etudiants = [];
        $liste_candidatures_campus = [];

        $entrepriseModel = new EntrepriseModel($this->pdo);
        $offreModel = new OffreModel($this->pdo);

        // ici on va avoir chacune des methodes a executer en fonction des permissions de l'utilisateur, ca va etre utile pour produire l'affichage du profil

        if (in_array('SFx2 - Rechercher et afficher une entreprise', $liste_permissions)) {
            $liste_entreprises = $entrepriseModel->rechercherEntreprises('', '', '', 'decroissant');
        }

        if (in_array('SFx7 - Rechercher et afficher une offre', $liste_permissions)) {
            $liste_offres = $offreModel->rechercherOffres('', '', '', '', 'decroissant');
        }

        if (in_array('SFx12 - Rechercher et afficher un compte Pilote', $liste_permissions) || 
            in_array('SFx16 - Rechercher et afficher un compte Etudiant', $liste_permissions)) {
            $liste_utilisateurs = $utilisateurModel->getTousLesUtilisateurs();
            
        }

        if (in_array('SFx22 - Afficher la liste des offres auxquelles les élèves du pilote ont postulé', $liste_permissions)) {
            $identifiant_campus = $utilisateur['campus_id'];
            $identifiant_promotion = $utilisateur['promotion_id'];
            $liste_etudiants = $utilisateurModel->getEtudiantsDuPromo($identifiant_campus, $identifiant_promotion);
            $liste_candidatures_campus = $utilisateurModel->getCandidaturesDuCampus($identifiant_campus, $identifiant_promotion);
        }

        if (in_array("SFx21 - Afficher la liste des offres pour lesquelles l'étudiant a postulé", $liste_permissions)) {
            $liste_candidatures = $utilisateurModel->getCandidaturesParUtilisateurId($identifiant_utilisateur);
        }

        if (in_array('SFx23 - Afficher les offres ajoutées à la wish-list', $liste_permissions)) {
            $liste_souhaits = $utilisateurModel->getWishlistParUtilisateurId($identifiant_utilisateur);
        }

        echo $this->twig->render('profil.twig.html', [
            'utilisateur' => $utilisateur,
            'permissions' => $liste_permissions,
            'liste_utilisateurs' => $liste_utilisateurs,
            'liste_entreprises' => $liste_entreprises,
            'liste_offres' => $liste_offres,
            'liste_candidatures'=> $liste_candidatures,
            'liste_wishlist' => $liste_souhaits,
            'liste_etudiants' => $liste_etudiants,
            'liste_candidatures_campus' => $liste_candidatures_campus,
        ]);
    }

    public function creer() {
        $this->verifierPermissions(['SFx17 - Créer un compte Etudiant', 'SFx13 - Créer un compte Pilote']);

        $utilisateurModel = new UtilisateurModel($this->pdo);
        $liste_campus = $utilisateurModel->getTousLesCampus();
        $liste_promotions = $utilisateurModel->getToutesLesPromotions();
        $liste_roles = $utilisateurModel->getTousLesRoles();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mot_de_passe = '';
            $confirmation_mot_de_passe  = '';

            if (isset($_POST['password'])) {
                $mot_de_passe = $_POST['password'];
            }

            if (isset($_POST['confirm_password'])) {
                $confirmation_mot_de_passe = $_POST['confirm_password'];
            }

            if ($mot_de_passe !== $confirmation_mot_de_passe) {
                $message_erreur = 'Les mots de passe ne correspondent pas.';
            } else {
                $utilisateurModel->creerUtilisateur($_POST, (int)$_POST['role_id']);
                $_SESSION['flash'] = 'Utilisateur créé avec succès !';
                header('Location: /gestion-utilisateurs');
                exit;
            }
        }

        echo $this->twig->render('creer-utilisateur.twig.html', [
            'liste_campus' => $liste_campus,
            'liste_promotions'=> $liste_promotions,
            'liste_roles' => $liste_roles,
            'flash' => $message_erreur ?? '',
        ]);
    }

    public function modifier() {
        $this->verifierPermissions(['SFx18 - Modifier un compte Etudiant', 'SFx14 - Modifier un compte Pilote']);

        $identifiant_utilisateur = 0;
        if (isset($_GET['id'])) {
            $identifiant_utilisateur = (int) $_GET['id'];
        }

        $utilisateurModel = new UtilisateurModel($this->pdo);

        $utilisateur = $utilisateurModel->getUtilisateurParId($identifiant_utilisateur);
        if (!$utilisateur) {
            header('Location: /gestion-utilisateurs');
            exit;
        }

        $mes_permissions = $_SESSION['utilisateur']['permissions'] ?? [];
        if (!in_array('SFx14 - Modifier un compte Pilote', $mes_permissions)) {
            if ($utilisateur['role_nom'] !== 'Etudiant' || $utilisateur['campus_id'] !== $_SESSION['utilisateur']['campus_id']) {
                header('Location: /gestion-utilisateurs');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mot_de_passe = '';
            $confirmation_mot_de_passe  = '';

            if (isset($_POST['password'])) {
                $mot_de_passe = $_POST['password'];
            }
            if (isset($_POST['confirm_password'])) {
                $confirmation_mot_de_passe = $_POST['confirm_password'];
            }

            if ($mot_de_passe !== '' && $mot_de_passe !== $confirmation_mot_de_passe) {
                $message_erreur = 'Les mots de passe ne correspondent pas.';
            } else {
                $utilisateurModel->modifierUtilisateur($identifiant_utilisateur, $_POST);
                $_SESSION['flash'] = 'Utilisateur modifié avec succès !';
                header('Location: /gestion-utilisateurs');
                exit;
            }
        }

        $liste_campus = $utilisateurModel->getTousLesCampus();
        $liste_promotions = $utilisateurModel->getToutesLesPromotions();
        $liste_roles = $utilisateurModel->getTousLesRoles();

        echo $this->twig->render('modifier-utilisateur.twig.html', [
            'utilisateur' => $utilisateur,
            'liste_campus'=> $liste_campus,
            'liste_promotions'=> $liste_promotions,
            'liste_roles'=> $liste_roles,
            'flash'=> $message_erreur ?? '',
        ]);
    }

    public function supprimer() {
        $this->verifierPermissions(['SFx19 - Supprimer un compte Etudiant', 'SFx15 - Supprimer un compte Pilote']);

        $identifiant_utilisateur = 0;
        if (isset($_GET['id'])) {
            $identifiant_utilisateur = (int) $_GET['id'];
        }

        $utilisateurModel = new UtilisateurModel($this->pdo);
        $utilisateur = $utilisateurModel->getUtilisateurParId($identifiant_utilisateur);

        if (!$utilisateur) {
            header('Location: /gestion-utilisateurs');
            exit;
        }

        $mes_permissions = $_SESSION['utilisateur']['permissions'] ?? [];
        if (!in_array('SFx15 - Supprimer un compte Pilote', $mes_permissions)) {
            if ($utilisateur['role_nom'] !== 'Etudiant' || $utilisateur['campus_id'] !== $_SESSION['utilisateur']['campus_id']) {
                header('Location: /gestion-utilisateurs');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $utilisateurModel->supprimerUtilisateur($identifiant_utilisateur);
            $_SESSION['flash'] = 'Utilisateur supprimé avec succès.';
            header('Location: /gestion-utilisateurs');
            exit;
        }

        echo $this->twig->render('supprimer-utilisateur.twig.html', [
            'utilisateur' => $utilisateur,
        ]);
    }

    public function gestion() {
        $this->verifierPermissions(['SFx12 - Rechercher et afficher un compte Pilote', 'SFx16 - Rechercher et afficher un compte Etudiant']);

        $permissions = $_SESSION['utilisateur']['permissions'] ?? [];

       $recherche_nom = '';
        $recherche_campus = '';
        $recherche_role = '';

        if (isset($_GET['nom'])) {
            $recherche_nom = trim($_GET['nom']);
        }

        if (isset($_GET['campus'])) {
            $recherche_campus = trim($_GET['campus']);
        }

        if (isset($_GET['role'])) {
            $recherche_role = trim($_GET['role']);
        }

        $page_courante = 1;
        if (isset($_GET['page'])) {
            $page_courante = max(1, (int) $_GET['page']);
        }
        $utilisateurs_par_page = 10;
        $decalage_pagination = ($page_courante - 1) * $utilisateurs_par_page;

        $conditions_sql = [];
        $parametres_requete = [];

        /*
        On vérifie si l’utilisateur a rempli le champ de recherche “nom” ($recherche_nom).
        Si oui :
        On ajoute une condition SQL qui cherche dans le nom, le prénom et l’email.
        % autour de la valeur permet de faire une recherche partielle, par exemple Jean trouvera Jean, Jean-Pierre, Jeanne, etc.
        Les trois paramètres :nom, :prenom, :email sont liés à $parametres_requete pour préparer la requête sécurisée.
        */

        if ($recherche_nom !== '') {
            $conditions_sql[] = "(utilisateur.utilisateur_nom LIKE :nom OR utilisateur.utilisateur_prenom LIKE :prenom OR utilisateur.utilisateur_email LIKE :email)";
            $parametres_requete[':nom'] = '%' . $recherche_nom . '%';
            $parametres_requete[':prenom'] = '%' . $recherche_nom . '%';
            $parametres_requete[':email'] = '%' . $recherche_nom . '%';
        }

        if ($recherche_campus !== '') {
            $conditions_sql[] = "campus.campus_nom LIKE :campus";
            $parametres_requete[':campus'] = '%' . $recherche_campus . '%';
        }

        if ($recherche_role !== '') {
            $conditions_sql[] = "role.role_nom = :role";
            $parametres_requete[':role'] = $recherche_role;
        }

        if (!in_array('SFx12 - Rechercher et afficher un compte Pilote', $permissions)) {
    
        $conditions_sql[] = "role.role_nom = 'Etudiant'";

        $conditions_sql[] = "profil.promotion_id = :promotion_pilote";
        $conditions_sql[] = "profil.campus_id = :campus_pilote";

        $parametres_requete[':promotion_pilote'] = $_SESSION['utilisateur']['promotion_id'];
        $parametres_requete[':campus_pilote'] = $_SESSION['utilisateur']['campus_id'];
    }


        /*
        $clause_where_sql = '' → valeur par défaut si pas de conditions.
        $clause_where_sql = 'WHERE ' → début de la clause SQL.
        Boucle sur $conditions_sql pour ajouter chaque condition une par une.
        La variable $est_premiere_condition permet d’éviter de mettre AND devant la première condition.
        */

        $clause_where_sql = '';
        if (count($conditions_sql) > 0) {
            $clause_where_sql = 'WHERE ';
            $est_premiere_condition = true;
            foreach ($conditions_sql as $condition) {
                if (!$est_premiere_condition) {
                    $clause_where_sql .= ' AND ';
                }
                $clause_where_sql .= $condition;
                $est_premiere_condition = false;
            }
        }

        $requete_comptage = "
            SELECT COUNT(*)
            FROM UTILISATEUR AS utilisateur
            JOIN ROLE AS role ON role.role_id = utilisateur.role_id
            LEFT JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id
            LEFT JOIN CAMPUS AS campus ON campus.campus_id = profil.campus_id
            LEFT JOIN PROMOTION AS promotion  ON promotion.promotion_id = profil.promotion_id
            $clause_where_sql
        ";
        $statement_comptage = $this->pdo->prepare($requete_comptage);
        $statement_comptage->execute($parametres_requete);
        $nombre_total_utilisateurs = (int) $statement_comptage->fetchColumn();

        $nombre_total_pages = max(1, (int) ceil($nombre_total_utilisateurs / $utilisateurs_par_page));
        $page_courante = min($page_courante, $nombre_total_pages);

        $requete_utilisateurs = "
            SELECT
                utilisateur.utilisateur_id,
                utilisateur.utilisateur_nom,
                utilisateur.utilisateur_prenom,
                utilisateur.utilisateur_email,
                role.role_nom,
                campus.campus_nom,
                promotion.promotion_nom
            FROM UTILISATEUR AS utilisateur
            JOIN ROLE AS role ON role.role_id = utilisateur.role_id
            LEFT JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id
            LEFT JOIN CAMPUS AS campus ON campus.campus_id = profil.campus_id
            LEFT JOIN PROMOTION AS promotion ON promotion.promotion_id = profil.promotion_id
            $clause_where_sql
            ORDER BY utilisateur.utilisateur_nom ASC, utilisateur.utilisateur_prenom ASC
            LIMIT  :limite
            OFFSET :offset
        ";
        $statement_utilisateurs = $this->pdo->prepare($requete_utilisateurs);
        foreach ($parametres_requete as $cle => $valeur) {
            $statement_utilisateurs->bindValue($cle, $valeur);
        }
        $statement_utilisateurs->bindValue(':limite', $utilisateurs_par_page, \PDO::PARAM_INT);
        $statement_utilisateurs->bindValue(':offset', $decalage_pagination, \PDO::PARAM_INT);
        $statement_utilisateurs->execute();
        $liste_utilisateurs = $statement_utilisateurs->fetchAll(\PDO::FETCH_ASSOC);

        $page_debut_affichage = max(1, $page_courante - 2);
        $page_fin_affichage = min($nombre_total_pages, $page_courante + 2);

        $parametres_adresse_url = http_build_query(array_filter([
            'nom' => $recherche_nom,
            'campus' => $recherche_campus,
            'role' => $recherche_role,
        ]));

        echo $this->twig->render('gestion-utilisateurs.twig.html', [
            'liste_utilisateurs' => $liste_utilisateurs,
            'nombre_total_utilisateurs' => $nombre_total_utilisateurs,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante' => $page_courante,
            'page_debut' => $page_debut_affichage,
            'page_fin' => $page_fin_affichage,
            'recherche_nom' => $recherche_nom,
            'recherche_campus' => $recherche_campus,
            'recherche_role' => $recherche_role,
            'parametres_url' => $parametres_adresse_url,
        ]);
    }

    public function mesCandidatures() {
        $this->verifierPermissions(["SFx21 - Afficher la liste des offres pour lesquelles l'étudiant a postulé"]);

        $identifiant_utilisateur  = $_SESSION['utilisateur']['id'];
        $utilisateurModel = new UtilisateurModel($this->pdo);
        $toutes_les_candidatures = $utilisateurModel->getCandidaturesParUtilisateurId($identifiant_utilisateur);

        $pagination = new \App\Utilities\Pagination($toutes_les_candidatures, 10);
        $liste_candidatures = $pagination->itemsPage();
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        $page_debut_affichage = max(1, $page_courante - 2);
        $page_fin_affichage = min($nombre_total_pages, $page_courante + 2);
        $parametres_adresse_url = http_build_query(['uri' => 'mes-candidatures']);

        echo $this->twig->render('mes-candidatures.twig.html', [
            'liste_candidatures' => $liste_candidatures,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante' => $page_courante,
            'page_debut'=> $page_debut_affichage,
            'page_fin' => $page_fin_affichage,
            'parametres_url'=> $parametres_adresse_url,
        ]);
    }

    public function mesFavoris() {
        $this->verifierPermissions(['SFx23 - Afficher les offres ajoutées à la wish-list']);

        $identifiant_utilisateur = $_SESSION['utilisateur']['id'];
        $utilisateurModel = new UtilisateurModel($this->pdo);
        $tous_les_favoris = $utilisateurModel->getWishlistParUtilisateurId($identifiant_utilisateur);

        $pagination = new \App\Utilities\Pagination($tous_les_favoris, 10);
        $liste_souhaits = $pagination->itemsPage();
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        $page_debut_affichage = max(1, $page_courante - 2);
        $page_fin_affichage = min($nombre_total_pages, $page_courante + 2);
        $parametres_adresse_url = http_build_query(['uri' => 'mes-favoris']);

        echo $this->twig->render('mes-favoris.twig.html', [
            'liste_wishlist' => $liste_souhaits,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante' => $page_courante,
            'page_debut' => $page_debut_affichage,
            'page_fin' => $page_fin_affichage,
            'parametres_url'=> $parametres_adresse_url,
        ]);
    }

    public function suiviEleves() {
        $this->verifierPermissions(['SFx22 - Afficher la liste des offres auxquelles les élèves du pilote ont postulé']);

        $identifiant_campus    = $_SESSION['utilisateur']['campus_id'];
        $identifiant_promotion = $_SESSION['utilisateur']['promotion_id'];
        $utilisateurModel = new UtilisateurModel($this->pdo);

        // recuperation de tous les filtres depuis l'URL
        $recherche_nom = '';
        if (isset($_GET['nom'])) {
            $recherche_nom = trim($_GET['nom']);
        }

        $recherche_date = '';
        if (isset($_GET['date'])) {
            $recherche_date = trim($_GET['date']);
        }

        $recherche_entreprise = '';
        if (isset($_GET['entreprise'])) {
            $recherche_entreprise = trim($_GET['entreprise']);
        }

        $recherche_offre = '';
        if (isset($_GET['offre'])) {
            $recherche_offre = trim($_GET['offre']);
        }

        // appel au modele pour recuperer les donnees filtrees
        $toutes_les_candidatures_detaillees = $utilisateurModel->getCandidaturesDuCampusDetail(
            $identifiant_campus,
            $identifiant_promotion,
            $recherche_nom, 
            $recherche_date, 
            $recherche_entreprise, 
            $recherche_offre
        );


        // recuperation des listes pour l'auto-completion des formulaires
        $liste_entreprises_candidatures = $utilisateurModel->getEntreprisesDesCandidaturesCampus($identifiant_campus);
        $liste_offres_candidatures = $utilisateurModel->getOffresDesCandidaturesCampus($identifiant_campus);

        // pagination
        $pagination = new \App\Utilities\Pagination($toutes_les_candidatures_detaillees, 10);
        $liste_candidatures = $pagination->itemsPage();
        $page_courante = $pagination->getCurrentPage();
        $nombre_total_pages = $pagination->getTotalPages();
        $page_debut_affichage = max(1, $page_courante - 2);
        $page_fin_affichage = min($nombre_total_pages, $page_courante + 2);
        
        // integration des filtres dans l'URL de pagination
        $parametres_adresse_url = http_build_query(array_filter([
            'nom' => $recherche_nom,
            'date' => $recherche_date,
            'entreprise' => $recherche_entreprise,
            'offre' => $recherche_offre
        ]));

        echo $this->twig->render('suivi-eleves.twig.html', [
            'liste_candidatures' => $liste_candidatures,
            'nombre_total_pages' => $nombre_total_pages,
            'page_courante' => $page_courante,
            'page_debut' => $page_debut_affichage,
            'page_fin' => $page_fin_affichage,
            'parametres_url' => $parametres_adresse_url,
            
            // on envoie les valeurs actuelles pour pre-remplir les champs
            'recherche_nom' => $recherche_nom,
            'recherche_date' => $recherche_date,
            'recherche_entreprise' => $recherche_entreprise,
            'recherche_offre' => $recherche_offre,
            'datalist_entreprises' => $liste_entreprises_candidatures,
            'datalist_offres' => $liste_offres_candidatures
        ]);
    }
}