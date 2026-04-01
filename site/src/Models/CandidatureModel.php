<?php
namespace App\Models;

class CandidatureModel {
    private $objet_pdo;

    public function __construct(\PDO $pdo) {
        $this->objet_pdo = $pdo;
    }

    public function creerCandidature($donnees_candidature) {
        $requete_sql = "
            INSERT INTO CANDIDATURE (
                candidature_date, 
                candidature_lettre_motivation, 
                candidature_cv, 
                utilisateur_id, 
                offre_id
            ) 
            VALUES (
                NOW(), 
                :candidature_lettre_motivation, 
                :candidature_cv, 
                :utilisateur_id, 
                :offre_id
            )
        ";
        
        $declaration_preparee = $this->objet_pdo->prepare($requete_sql);
        
        return $declaration_preparee->execute([
            ':candidature_lettre_motivation' => $donnees_candidature['lettre_motivation'],
            ':candidature_cv' => $donnees_candidature['cv_nom'],
            ':utilisateur_id' => (int)$donnees_candidature['id_utilisateur'],
            ':offre_id'=> (int)$donnees_candidature['id_offre']
        ]);
    }

    public function obtenirCandidaturesParUtilisateur($identifiant_utilisateur) {
        $requete_sql = "
            SELECT 
                CANDIDATURE.candidature_id,
                CANDIDATURE.candidature_date,
                CANDIDATURE.candidature_lettre_motivation,
                CANDIDATURE.candidature_cv,
                OFFRE.offre_titre,
                OFFRE.offre_id,
                ENTREPRISE.entreprise_nom,
                VILLE.ville_nom
            FROM CANDIDATURE
            INNER JOIN OFFRE ON CANDIDATURE.offre_id = OFFRE.offre_id
            INNER JOIN ENTREPRISE ON OFFRE.entreprise_id = ENTREPRISE.entreprise_id
            INNER JOIN LOCALITE ON OFFRE.localite_id = LOCALITE.localite_id
            INNER JOIN VILLE ON LOCALITE.ville_id = VILLE.ville_id
            WHERE CANDIDATURE.utilisateur_id = :identifiant_utilisateur
            ORDER BY CANDIDATURE.candidature_id DESC
        ";
        
        $declaration_preparee = $this->objet_pdo->prepare($requete_sql);
        $declaration_preparee->execute([':identifiant_utilisateur' => $identifiant_utilisateur]);
        
        return $declaration_preparee->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenirCandidatureParId($candidature_id) {
        $requete_sql = "
            SELECT 
                CANDIDATURE.candidature_lettre_motivation,
                CANDIDATURE.candidature_cv,
                CANDIDATURE.utilisateur_id,
                PROFIL_SCOLAIRE.campus_id,
                PROFIL_SCOLAIRE.promotion_id,
                OFFRE.offre_titre,
                ENTREPRISE.entreprise_nom,
                UTILISATEUR.utilisateur_nom,
                UTILISATEUR.utilisateur_prenom
            FROM CANDIDATURE
            INNER JOIN OFFRE ON CANDIDATURE.offre_id = OFFRE.offre_id
            INNER JOIN ENTREPRISE ON OFFRE.entreprise_id = ENTREPRISE.entreprise_id
            INNER JOIN UTILISATEUR ON CANDIDATURE.utilisateur_id = UTILISATEUR.utilisateur_id
            INNER JOIN PROFIL_SCOLAIRE ON UTILISATEUR.utilisateur_id = PROFIL_SCOLAIRE.utilisateur_id
            WHERE CANDIDATURE.candidature_id = :candidature_id
        ";
        
        $declaration_preparee = $this->objet_pdo->prepare($requete_sql);
        $declaration_preparee->execute([':candidature_id' => $candidature_id]);
        
        return $declaration_preparee->fetch(\PDO::FETCH_ASSOC);
    }
}