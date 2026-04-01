<?php
namespace App\Controllers;

use App\Models\EvaluationModel;
use App\Models\EntrepriseModel;

class EvaluationController extends Controller {

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
        match($adresse_url) {
            'evaluer-entreprise' => $this->afficherFormulaireEvaluation(),
            'traiter-notation'   => $this->traiterNotationEntreprise(),
            default => $this->twig->render($adresse_url . '.twig.html')
        };
    }

    public function afficherFormulaireEvaluation() {
        $this->verifierPermissions(['SFx5 - Evaluer une entreprise']);

        $identifiant_utilisateur = null;
        if (isset($_SESSION['utilisateur']['id'])) {
            $identifiant_utilisateur = $_SESSION['utilisateur']['id'];
        }

        $identifiant_entreprise = 0;
        if (isset($_GET['id'])) {
            $identifiant_entreprise = (int) $_GET['id'];
        }

        $entrepriseModel = new EntrepriseModel($this->pdo);
        $evaluationModel = new EvaluationModel($this->pdo);

        $entreprise = $entrepriseModel->getEntrepriseParId($identifiant_entreprise);
        
        if (!$entreprise) {
            echo $this->twig->render('404.twig.html');
            return;
        }
        
        $note_actuelle_utilisateur = $evaluationModel->obtenirNoteUtilisateur($identifiant_utilisateur, $identifiant_entreprise);

        echo $this->twig->render('evaluer-entreprise.twig.html', [
            'entreprise' => $entreprise,
            'note_existante' => $note_actuelle_utilisateur
        ]);
    }

   public function traiterNotationEntreprise() {
    $this->verifierPermissions(['SFx5 - Evaluer une entreprise']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['utilisateur']['id'])) {
        $evaluationModel = new \App\Models\EvaluationModel($this->pdo);
        
        $identifiant_entreprise = (int)$_POST['entreprise_id'];
        $note_donnee = (int)$_POST['note'];
        $identifiant_utilisateur = (int)$_SESSION['utilisateur']['id'];

        $succes_enregistrement = $evaluationModel->enregistrerNote($identifiant_utilisateur, $identifiant_entreprise, $note_donnee);

        if ($succes_enregistrement) {
            $_SESSION['flash'] = "Votre note a été enregistrée avec succès !";
        } else {
            $_SESSION['flash'] = "Erreur technique : la base de données a refusé l'enregistrement.";
        }

        header('Location: /detail-entreprise&id=' . $identifiant_entreprise);
        exit;
    }
}
}