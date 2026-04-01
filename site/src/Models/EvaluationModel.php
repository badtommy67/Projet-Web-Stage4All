<?php
namespace App\Models;

class EvaluationModel {
    private $objet_pdo;

    public function __construct(\PDO $pdo) {
        $this->objet_pdo = $pdo;
    }

    /* recupere la note qu'un utilisateur specifique a donnee à une entreprise precise.*/
    public function obtenirNoteUtilisateur($identifiant_utilisateur, $identifiant_entreprise) {
        $requete_sql = "
            SELECT evaluation_note 
            FROM EVALUATION 
            WHERE utilisateur_id = :utilisateur_id 
              AND entreprise_id = :entreprise_id
        ";
        
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([
            ':utilisateur_id' => $identifiant_utilisateur, 
            ':entreprise_id'  => $identifiant_entreprise
        ]);
        
        // retourne la note (0-5) ou false si aucune note n'est trouvee
        return $req->fetchColumn();
    }

    /* enregistre la note : met à jour si elle existe, sinon l'insere.*/
    public function enregistrerNote($identifiant_utilisateur, $identifiant_entreprise, $note_donnee) {
        $note_actuelle_utilisateur = $this->obtenirNoteUtilisateur($identifiant_utilisateur, $identifiant_entreprise);

        if ($note_actuelle_utilisateur !== false) {
            // si la note existe deja, on effectue une mise a jour (UPDATE)
            $requete_sql = "
                UPDATE EVALUATION 
                SET evaluation_note = :note 
                WHERE utilisateur_id = :utilisateur_id 
                  AND entreprise_id = :entreprise_id
            ";
        } else {
            // si aucune note n'existe, on effectue une insertion (INSERT)
            $requete_sql = "
                INSERT INTO EVALUATION (evaluation_note, utilisateur_id, entreprise_id) 
                VALUES (:note, :utilisateur_id, :entreprise_id)
            ";
        }

        $req = $this->objet_pdo->prepare($requete_sql);
        return $req->execute([
            ':note'=> (int)$note_donnee,
            ':utilisateur_id' => (int)$identifiant_utilisateur,
            ':entreprise_id'=> (int)$identifiant_entreprise
        ]);
    }


    public function obtenirMoyenneEntreprise($identifiant_entreprise) {
        $requete_sql = "
            SELECT 
                AVG(evaluation_note) AS note_moyenne, 
                COUNT(*) AS nombre_avis 
            FROM EVALUATION 
            WHERE entreprise_id = :entreprise_id
        ";
        
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':entreprise_id' => $identifiant_entreprise]);
        
        return $req->fetch(\PDO::FETCH_ASSOC);
    }
}