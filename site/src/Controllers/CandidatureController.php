<?php
namespace App\Controllers;

use App\Models\CandidatureModel;
use App\Models\OffreModel;

class CandidatureController extends Controller {

    private function verifierPermissions(array $permissionsRequises) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['utilisateur'])) {
            header('Location: /connexion');
            exit;
        }

        $permissionsUtilisateur = $_SESSION['utilisateur']['permissions'] ?? [];

        foreach ($permissionsRequises as $permission) {
            if (in_array($permission, $permissionsUtilisateur)) {
                return true;
            }
        }

        header('Location: /profil');
        exit;
    }

    public function redirection($adresse_url) {
        echo match($adresse_url) {
            'postuler' => $this->postuler(),
            'mes-candidatures' => $this->mesCandidatures(),
            'detail-candidature' => $this->voirCVetLM(),
            'voir-cv' => $this->afficherCv((int)($_GET['id'] ?? 0)),
            default => $this->twig->render('404.twig.html')
        };
    }

    public function postuler() {
        $this->verifierPermissions(['SFx20 - Postuler à une offre']);

        $identifiant_offre = 0;
        if (isset($_POST['id_offre'])) {
            $identifiant_offre = (int) $_POST['id_offre'];
        } elseif (isset($_GET['id'])) {
            $identifiant_offre = (int) $_GET['id'];
        }
        
        if (!isset($_SESSION['utilisateur'])) {
            header('Location: /connexion');
            exit;
        }

        $offreModel = new OffreModel($this->pdo);
        $offre = $offreModel->getOffreParId($identifiant_offre);

        if (!$offre) {
            echo $this->twig->render('404.twig.html');
            exit;
        }

        $identifiant_entreprise = $offre['entreprise_id'];
        $reference_offre = $offre['offre_reference'] ?? 'sans-ref';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifiant_utilisateur = $_SESSION['utilisateur']['id'];
            $nom_fichier_cv = "";

            if (isset($_FILES['cv']) && $_FILES['cv']['error'] === 0) {
                $fichier_telecharge = $_FILES['cv'];
                $extension_fichier = strtolower(pathinfo($fichier_telecharge['name'], PATHINFO_EXTENSION)); // PATHINFO_EXTENSION permet de recuperer l’extension d’un fichier

                if (!in_array($extension_fichier, ['pdf', 'doc', 'docx']) || $fichier_telecharge['size'] > 5 * 1024 * 1024) {
                    $_SESSION['flash'] = "Fichier invalide (PDF/DOC max 5Mo).";
                    header('Location: /postuler/' . $identifiant_offre);
                    exit;
                }

                $caracteres_recherches = [' ', "'", 'à', 'á', 'â', 'ã', 'ä', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
                $caracteres_remplacements = ['_', '_', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];

                $nom_utilisateur_brut = 'nom';
                if (isset($_POST['nom'])) {
                    $nom_utilisateur_brut = $_POST['nom'];
                }

                $prenom_utilisateur_brut = 'prenom';
                if (isset($_POST['prenom'])) {
                    $prenom_utilisateur_brut = $_POST['prenom'];
                }

                /*
                strtolower() transforme une chaine en minuscules afin d’uniformiser l’ecriture
                str_replace() remplace certains caracteres indesirables (definis dans $caracteres_recherches)
                par d’autres caractères plus adaptes (dans $caracteres_remplacements), ce qui permet d’obtenir un nom et un prenom “propres” pour un usage comme un nom de fichier ou un identifiant.
                La variable $reference_propre applique le meme principe : elle met la reference en minuscules et remplace les espaces, slash / et antislash \ par des tirets - pour eviter des problemes dans les chemins de fichiers.
                Enfin, date('d-m-Y_H-i') genere un horodatage au format jour-mois-année_heure-minute, utile pour rendre un nom de fichier unique.
                */
                $nom_nettoye = str_replace($caracteres_recherches, $caracteres_remplacements, strtolower($nom_utilisateur_brut));
                $prenom_nettoye = str_replace($caracteres_recherches, $caracteres_remplacements, strtolower($prenom_utilisateur_brut));
                $reference_propre = str_replace([' ', '/', '\\'], '-', strtolower($reference_offre));
                $horodatage = date('d-m-Y_H-i');
                
                // on concatene tout ensemble
                $nom_fichier_cv = "cv_" . $nom_nettoye . "_" . $prenom_nettoye . "_" . $reference_propre . "_" . $horodatage . "." . $extension_fichier;

                $chemin_dossier_cv = dirname(__DIR__, 2) . '/uploads/cv/';
                if (!is_dir($chemin_dossier_cv)) {
                    mkdir($chemin_dossier_cv, 0777, true);
                }

                if (!move_uploaded_file($fichier_telecharge['tmp_name'], $chemin_dossier_cv . $nom_fichier_cv)) {
                    $_SESSION['flash'] = "Erreur lors de l'enregistrement du fichier.";
                    header('Location: /postuler/' . $identifiant_offre);
                    exit;
                }
            }

            $candidatureModel = new CandidatureModel($this->pdo);
            $lettre_motivation = '';
            if (isset($_POST['lettre_motivation'])) {
                $lettre_motivation = $_POST['lettre_motivation'];
            }

            $succes_creation = $candidatureModel->creerCandidature([
                'id_offre' => $identifiant_offre,
                'id_utilisateur' => $identifiant_utilisateur,
                'lettre_motivation' => $lettre_motivation,
                'cv_nom' => $nom_fichier_cv
            ]);

            if ($succes_creation) {
                $_SESSION['flash'] = "Votre candidature a été envoyée avec succès !";
                header('Location: /mes-candidatures');
                exit;
            }
        }

        echo $this->twig->render('postuler.twig.html', [
            'id_offre' => $identifiant_offre,
            'offre_titre' => $offre['offre_titre'],
        ]);
    }

    public function mesCandidatures() {
        $this->verifierPermissions(["SFx21 - Afficher la liste des offres pour lesquelles l'étudiant a postulé"]);

        $identifiant_utilisateur = $_SESSION['utilisateur']['id'];
        $candidatureModel = new CandidatureModel($this->pdo);

        $page_courante = 1;

        if (isset($_GET['page'])) {
            $page_courante = (int) $_GET['page'];
        }
        if ($page_courante < 1) $page_courante = 1;
        $candidatures_par_page = 10;

        $toutes_les_candidatures = $candidatureModel->obtenirCandidaturesParUtilisateur($identifiant_utilisateur);
        $nombre_total_candidatures = count($toutes_les_candidatures);
        $nombre_total_pages = max(1, ceil($nombre_total_candidatures / $candidatures_par_page)); // la fonction ceil() en PHP sert a arrondir un nombre a l’entier superieur
        $decalage_pagination = ($page_courante - 1) * $candidatures_par_page;

        $liste_a_afficher = array_slice($toutes_les_candidatures, $decalage_pagination, $candidatures_par_page);

        echo $this->twig->render('mes-candidatures.twig.html', [
            'liste_candidatures' => $liste_a_afficher,
            'page_courante'=> $page_courante,
            'nombre_total_pages' => $nombre_total_pages,
            'page_debut' => 1,
            'page_fin' => $nombre_total_pages,
            'parametres_url' => 'uri=mes-candidatures'
        ]);
    }

    public function voirCVetLM() {
        $this->verifierPermissions(["SFx22 - Afficher la liste des offres auxquelles les élèves du pilote ont postulé", "SFx21 - Afficher la liste des offres pour lesquelles l'étudiant a postulé"]);

        $id_candidature = 0;

        if (isset($_GET['id'])) {
            $id_candidature = (int) $_GET['id'];
        }

        if ($id_candidature === 0) {
            echo $this->twig->render('404.twig.html');
            return;
        }

        $candidatureModel = new CandidatureModel($this->pdo);
        $candidature = $candidatureModel->obtenirCandidatureParId($id_candidature);

        if (!$candidature) {
            echo $this->twig->render('404.twig.html');
            return;
        }
        $nom_fichier_cv = $candidature['candidature_cv'];
        $chemin_dossier_cv = dirname(__DIR__, 2) . '/uploads/cv/';
        $chemin_complet = $chemin_dossier_cv . $nom_fichier_cv;

        $fichier_existe = !empty($nom_fichier_cv) && file_exists($chemin_complet);


        $permissions = $_SESSION['utilisateur']['permissions'] ?? [];
        
        if (in_array("SFx21 - Afficher la liste des offres pour lesquelles l'étudiant a postulé", $permissions)) {
            if ($candidature['utilisateur_id'] !== $_SESSION['utilisateur']['id']) {
                header('Location: /mes-candidatures');
                exit;
            }
        } 
        elseif (in_array("SFx22 - Afficher la liste des offres auxquelles les élèves du pilote ont postulé", $permissions)) {
            if ($candidature['campus_id'] !== $_SESSION['utilisateur']['campus_id'] || $candidature['promotion_id'] !== $_SESSION['utilisateur']['promotion_id']) {
                header('Location: /suivi-eleves');
                exit;
            }
        }

        echo $this->twig->render('detail-candidature.twig.html', [
            'candidature_id' => $id_candidature,
            'lettre_motivation' => $candidature['candidature_lettre_motivation'],
            'offre_titre' => $candidature['offre_titre'],
            'offre_id'=> $candidature['offre_id'],
            'entreprise_nom'=> $candidature['entreprise_nom'],
            'entreprise_id' => $candidature['entreprise_id'],
            'cv_nom' => $candidature['candidature_cv'],
            'etudiant_nom' => $candidature['utilisateur_nom'], 
            'etudiant_prenom'=> $candidature['utilisateur_prenom'],
            'extension_cv' => strtolower(pathinfo($candidature['candidature_cv'], PATHINFO_EXTENSION)),
            'fichier_existe' => $fichier_existe,
        ]);
    }


    public function afficherCv($id_candidature) {
        $this->verifierPermissions(["SFx22 - Afficher la liste des offres auxquelles les élèves du pilote ont postulé", "SFx21 - Afficher la liste des offres pour lesquelles l'étudiant a postulé"]);

        if ($id_candidature <= 0) {
            header('Location: /404');
            exit;
        }

        $candidatureModel = new CandidatureModel($this->pdo);
        $candidature = $candidatureModel->obtenirCandidatureParId($id_candidature);

        if (!$candidature || empty($candidature['candidature_cv'])) {
            header('Location: /404');
            exit;
        }

        $permissions = $_SESSION['utilisateur']['permissions'] ?? [];
        $id_utilisateur_session = $_SESSION['utilisateur']['id'];

        if (in_array("SFx21 - Afficher la liste des offres pour lesquelles l'étudiant a postulé", $permissions)) {
            if ($candidature['utilisateur_id'] !== $id_utilisateur_session) {
                header('Location: /mes-candidatures');
                exit;
            }
        } 
        elseif (in_array("SFx22 - Afficher la liste des offres auxquelles les élèves du pilote ont postulé", $permissions)) {
            if ($candidature['campus_id'] !== $_SESSION['utilisateur']['campus_id'] || 
                $candidature['promotion_id'] !== $_SESSION['utilisateur']['promotion_id']) {
                header('Location: /suivi-eleves');
                exit;
            }
        }

        $nom_fichier_bdd = $candidature['candidature_cv'];
        $chemin_dossier_cv = dirname(__DIR__, 2) . '/uploads/cv/';
        $chemin_complet = $chemin_dossier_cv . $nom_fichier_bdd;

        if (file_exists($chemin_complet) && is_file($chemin_complet)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $chemin_complet);
            finfo_close($finfo);

            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . $nom_fichier_bdd . '"');
            header('Content-Length: ' . filesize($chemin_complet));
            
            readfile($chemin_complet);
            exit;
        } else {
            header('Location: /404');
            exit;
        }
    }
}