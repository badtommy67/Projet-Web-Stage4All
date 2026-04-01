<?php
namespace App\Models;

class UtilisateurModel {
    private $objet_pdo;

    public function __construct(\PDO $pdo) {
        $this->objet_pdo = $pdo;
    }

    public function getUtilisateurParEmail($adresse_email) {
        $requete_sql = "
            SELECT
                utilisateur.utilisateur_id,
                utilisateur.utilisateur_prenom,
                utilisateur.utilisateur_nom,
                utilisateur.utilisateur_email,
                utilisateur.utilisateur_mdp,
                utilisateur.role_id,
                role.role_nom,
                profil.campus_id,
                profil.promotion_id
            FROM UTILISATEUR AS utilisateur
            LEFT JOIN ROLE AS role ON role.role_id = utilisateur.role_id 
            LEFT JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id
            WHERE utilisateur.utilisateur_email = :email
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':email' => $adresse_email]);

        return $req->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUtilisateurParId($identifiant_utilisateur) {
        $requete_sql = "
            SELECT
                utilisateur.utilisateur_id,
                utilisateur.utilisateur_prenom,
                utilisateur.utilisateur_nom,
                utilisateur.utilisateur_email,
                utilisateur.role_id,
                role.role_nom,
                profil.campus_id,
                profil.promotion_id,
                campus.campus_nom,
                promotion.promotion_nom
            FROM UTILISATEUR AS utilisateur
            LEFT JOIN ROLE AS role ON role.role_id = utilisateur.role_id 
            LEFT JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id
            LEFT JOIN CAMPUS AS campus ON campus.campus_id  = profil.campus_id
            LEFT JOIN PROMOTION AS promotion ON promotion.promotion_id = profil.promotion_id
            WHERE utilisateur.utilisateur_id = :id
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':id' => $identifiant_utilisateur]);

        return $req->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPermissionsParRoleId($identifiant_role) {
        $requete_sql = "
            SELECT permission.permission_nom
            FROM PERMISSION AS permission
            JOIN ROLE_PERMISSION AS rp ON rp.permission_id = permission.permission_id
            WHERE rp.role_id = :role_id
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':role_id' => $identifiant_role]);
        return $req->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getTousLesUtilisateurs() {
        $requete_sql = "
            SELECT
                utilisateur.utilisateur_id,
                utilisateur.utilisateur_prenom,
                utilisateur.utilisateur_nom,
                utilisateur.utilisateur_email,
                role.role_nom
            FROM UTILISATEUR AS utilisateur
            JOIN ROLE AS role ON role.role_id = utilisateur.role_id
            ORDER BY utilisateur.utilisateur_id DESC
        ";
        $resultat_utilisateurs = $this->objet_pdo->query($requete_sql);

        return $resultat_utilisateurs->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTousLesCampus() {
        $resultat_campus = $this->objet_pdo->query("SELECT campus_id, campus_nom FROM CAMPUS ORDER BY campus_nom ASC");
        return $resultat_campus->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getToutesLesPromotions() {
        $resultat_promotions = $this->objet_pdo->query("SELECT promotion_id, promotion_nom FROM PROMOTION ORDER BY promotion_nom ASC");
        return $resultat_promotions->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTousLesRoles() {
        $resultat_roles = $this->objet_pdo->query("SELECT role_id, role_nom FROM ROLE ORDER BY role_id ASC");
        return $resultat_roles->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function creerUtilisateur($donnees_utilisateur, $identifiant_role) {
        $requete_sql = "
            INSERT INTO UTILISATEUR (utilisateur_prenom, utilisateur_nom, utilisateur_email, utilisateur_mdp, role_id)
            VALUES (:prenom, :nom, :email, :mdp, :role_id)
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([
            ':prenom' => $donnees_utilisateur['prenom'],
            ':nom' => $donnees_utilisateur['nom'],
            ':email' => $donnees_utilisateur['email'],
            ':mdp' => password_hash($donnees_utilisateur['password'], PASSWORD_DEFAULT),
            ':role_id' => $identifiant_role,
        ]);

        /*
        $data['password']
        C’est le mot de passe que l’utilisateur a saisi dans un formulaire.
        Exemple : "MonMotDePasse123".
        password_hash()
        C’est une fonction PHP qui transforme un mot de passe en une chaine securisee (hachee).
        Elle utilise un algorithme de hachage securise (comme bcrypt).
        Cela signifie que le mot de passe n’est jamais stocke en clair dans la base de donnees.
        PASSWORD_DEFAULT
        C’est le parametre qui dit à PHP d’utiliser l’algorithme de hachage recommande par défaut.
        Actuellement, c’est bcrypt, mais ça peut changer dans les futures versions de PHP pour rester securise.
        ':mdp' => ...
        ':mdp' est un parametre nomme pour PDO.
        On l’associe à la valeur hachee pour ensuite inserer dans la base de donnees de manière securisee.
        */

        $identifiant_utilisateur_cree = $this->objet_pdo->lastInsertId();

        if (!empty($donnees_utilisateur['campus_id']) && !empty($donnees_utilisateur['promotion_id'])) {
            $requete_profil_scolaire = $this->objet_pdo->prepare("
                INSERT INTO PROFIL_SCOLAIRE (utilisateur_id, campus_id, promotion_id)
                VALUES (:uid, :cid, :pid)
            ");
            $requete_profil_scolaire->execute([
                ':uid' => $identifiant_utilisateur_cree,
                ':cid' => $donnees_utilisateur['campus_id'],
                ':pid' => $donnees_utilisateur['promotion_id'],
            ]);
        }
    }

    public function modifierUtilisateur($identifiant_utilisateur, $donnees_utilisateur) {
        $requete_sql = "
            UPDATE UTILISATEUR SET
                utilisateur_prenom = :prenom,
                utilisateur_nom = :nom,
                utilisateur_email = :email,
                role_id = :role_id
            WHERE utilisateur_id = :id
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([
            ':prenom'=> $donnees_utilisateur['prenom'],
            ':nom' => $donnees_utilisateur['nom'],
            ':email'=> $donnees_utilisateur['email'],
            ':role_id' => $donnees_utilisateur['role_id'],
            ':id'=> $identifiant_utilisateur,
        ]);

        if (!empty($donnees_utilisateur['password'])) {
            $requete_mot_de_passe = $this->objet_pdo->prepare("UPDATE UTILISATEUR SET utilisateur_mdp = :mdp WHERE utilisateur_id = :id");
            $requete_mot_de_passe->execute([':mdp' => password_hash($donnees_utilisateur['password'], PASSWORD_DEFAULT), ':id' => $identifiant_utilisateur]);
        }

        $requete_verif_profil = $this->objet_pdo->prepare("SELECT utilisateur_id FROM PROFIL_SCOLAIRE WHERE utilisateur_id = :id");
        $requete_verif_profil->execute([':id' => $identifiant_utilisateur]);

        if ($requete_verif_profil->fetch()) {
            $requete_maj_profil = $this->objet_pdo->prepare("
                UPDATE PROFIL_SCOLAIRE SET campus_id = :cid, promotion_id = :pid WHERE utilisateur_id = :id
            ");
            $requete_maj_profil->execute([':cid' => $donnees_utilisateur['campus_id'], ':pid' => $donnees_utilisateur['promotion_id'], ':id' => $identifiant_utilisateur]);
        } else {
            $requete_creation_profil = $this->objet_pdo->prepare("
                INSERT INTO PROFIL_SCOLAIRE (utilisateur_id, campus_id, promotion_id) VALUES (:id, :cid, :pid)
            ");
            $requete_creation_profil->execute([':id' => $identifiant_utilisateur, ':cid' => $donnees_utilisateur['campus_id'], ':pid' => $donnees_utilisateur['promotion_id']]);
        }
    }

    public function supprimerUtilisateur($identifiant_utilisateur) {
        try {
            $this->objet_pdo->beginTransaction();
            $this->objet_pdo->prepare("DELETE FROM WISHLIST WHERE utilisateur_id = :id")->execute([':id' => $identifiant_utilisateur]);
            $this->objet_pdo->prepare("DELETE FROM CANDIDATURE WHERE utilisateur_id = :id")->execute([':id' => $identifiant_utilisateur]);
           $this->objet_pdo->prepare("DELETE FROM EVALUATION WHERE utilisateur_id = :id")->execute([':id' => $identifiant_utilisateur]);
            $this->objet_pdo->prepare("DELETE FROM PROFIL_SCOLAIRE WHERE utilisateur_id = :id")->execute([':id' => $identifiant_utilisateur]);
            $this->objet_pdo->prepare("DELETE FROM UTILISATEUR WHERE utilisateur_id = :id")->execute([':id' => $identifiant_utilisateur]);
            $this->objet_pdo->commit();
            return true;
        } catch (\PDOException $exception_pdo) {
            $this->objet_pdo->rollBack();
            return false;
        }
    }

    public function getCandidaturesParUtilisateurId($identifiant_utilisateur) {
        $requete_sql = "
            SELECT
                candidature.candidature_id,
                candidature.candidature_date,
                offre.offre_id,
                offre.offre_titre,
                entreprise.entreprise_nom
            FROM CANDIDATURE AS candidature
            JOIN OFFRE AS offre ON offre.offre_id = candidature.offre_id
            JOIN ENTREPRISE AS entreprise ON entreprise.entreprise_id = offre.entreprise_id
            WHERE candidature.utilisateur_id = :id
            ORDER BY candidature.candidature_id DESC
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':id' => $identifiant_utilisateur]);

        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

   public function getWishlistParUtilisateurId($identifiant_utilisateur) {
    $requete_sql = "
        SELECT
            offre.offre_id,
            offre.offre_titre,
            offre.offre_duree,
            offre.offre_reference,
            ville.ville_nom,
            entreprise.entreprise_nom,
            wishlist.wishlist_date_ajout
        FROM WISHLIST AS wishlist
        JOIN OFFRE AS offre ON offre.offre_id = wishlist.offre_id
        JOIN ENTREPRISE AS entreprise ON entreprise.entreprise_id = offre.entreprise_id
        JOIN LOCALITE AS localite ON localite.localite_id = offre.localite_id
        JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
        WHERE wishlist.utilisateur_id = :id
        ORDER BY wishlist.wishlist_date_ajout DESC
    ";
    $req = $this->objet_pdo->prepare($requete_sql);
    $req->execute([':id' => $identifiant_utilisateur]);

    return $req->fetchAll(\PDO::FETCH_ASSOC);
}

    public function getEtudiantsDuPromo($identifiant_campus, $identifiant_promotion) {
        $requete_sql = "
            SELECT
                utilisateur.utilisateur_id,
                utilisateur.utilisateur_prenom,
                utilisateur.utilisateur_nom,
                promotion.promotion_nom
            FROM UTILISATEUR AS utilisateur
            JOIN ROLE AS role ON role.role_id = utilisateur.role_id
            JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id
            LEFT JOIN PROMOTION  AS promotion ON promotion.promotion_id  = profil.promotion_id
            WHERE role.role_nom = 'Etudiant'
            AND profil.campus_id = :campus_id
            AND profil.promotion_id = :promotion_id
            ORDER BY utilisateur.utilisateur_nom ASC
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([
            ':campus_id'    => $identifiant_campus,
            ':promotion_id' => $identifiant_promotion,
        ]);
        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCandidaturesDuCampus($identifiant_campus, $identifiant_promotion) {
        $requete_sql = "
            SELECT
                candidature.candidature_date,
                utilisateur.utilisateur_prenom,
                utilisateur.utilisateur_nom,
                offre.offre_titre
            FROM CANDIDATURE AS candidature
            JOIN UTILISATEUR AS utilisateur ON utilisateur.utilisateur_id = candidature.utilisateur_id
            JOIN ROLE AS role ON role.role_id = utilisateur.role_id
            JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id
            JOIN OFFRE AS offre ON offre.offre_id = candidature.offre_id
            WHERE profil.campus_id = :campus_id
            AND profil.promotion_id = :promotion_id
            AND role.role_nom = 'Etudiant'
            ORDER BY candidature.candidature_id DESC
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':campus_id' => $identifiant_campus, ':promotion_id' => $identifiant_promotion]);

        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function getCandidaturesDuCampusDetail($identifiant_campus, $identifiant_promotion, $recherche_nom = '', $recherche_date = '', $recherche_entreprise = '', $recherche_offre = '') {
        $conditions_sql = "WHERE profil.campus_id = :campus_id AND profil.promotion_id = :promotion_id AND role.role_nom = 'Etudiant'";
        $parametres_requete = [':campus_id' => $identifiant_campus, ':promotion_id' => $identifiant_promotion];

        // Filtre nom/prénom
        if ($recherche_nom !== '') {
            $conditions_sql .= " AND (utilisateur.utilisateur_nom LIKE :nom OR utilisateur.utilisateur_prenom LIKE :prenom)";
            $parametres_requete[':nom'] = '%' . $recherche_nom . '%';
            $parametres_requete[':prenom'] = '%' . $recherche_nom . '%';
        }

        // Filtre date
        if ($recherche_date !== '') {
            $conditions_sql .= " AND DATE(candidature.candidature_date) = :date_candidature";
            $parametres_requete[':date_candidature'] = $recherche_date;
        }

        // Filtre entreprise (AJOUT)
        if ($recherche_entreprise !== '') {
            $conditions_sql .= " AND entreprise.entreprise_nom LIKE :entreprise";
            $parametres_requete[':entreprise'] = '%' . $recherche_entreprise . '%';
        }

        // Filtre offre (AJOUT)
        if ($recherche_offre !== '') {
            $conditions_sql .= " AND offre.offre_titre LIKE :offre";
            $parametres_requete[':offre'] = '%' . $recherche_offre . '%';
        }

        $requete_sql = "
            SELECT
                candidature.candidature_id,
                candidature.candidature_date,
                candidature.candidature_cv,
                candidature.candidature_lettre_motivation,
                candidature.offre_id,
                utilisateur.utilisateur_prenom,
                utilisateur.utilisateur_nom,
                offre.offre_titre,
                entreprise.entreprise_nom,
                ville.ville_nom,
                promotion.promotion_nom
            FROM CANDIDATURE AS candidature
            JOIN UTILISATEUR AS utilisateur ON utilisateur.utilisateur_id = candidature.utilisateur_id
            JOIN ROLE AS role ON role.role_id = utilisateur.role_id
            JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id
            JOIN PROMOTION AS promotion ON promotion.promotion_id = profil.promotion_id
            JOIN OFFRE AS offre ON offre.offre_id = candidature.offre_id
            JOIN ENTREPRISE AS entreprise ON entreprise.entreprise_id = offre.entreprise_id
            JOIN LOCALITE AS localite ON localite.localite_id = offre.localite_id
            JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
            $conditions_sql
            ORDER BY candidature.candidature_id DESC
        ";

        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute($parametres_requete);

        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Méthode pour l'auto-complétion des entreprises
    public function getEntreprisesDesCandidaturesCampus($identifiant_campus) {
        $requete_sql = "
            SELECT DISTINCT entreprise.entreprise_nom 
            FROM CANDIDATURE AS candidature 
            JOIN UTILISATEUR AS utilisateur ON utilisateur.utilisateur_id = candidature.utilisateur_id 
            JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id 
            JOIN OFFRE AS offre ON offre.offre_id = candidature.offre_id 
            JOIN ENTREPRISE AS entreprise ON entreprise.entreprise_id = offre.entreprise_id 
            WHERE profil.campus_id = :campus_id 
            ORDER BY entreprise.entreprise_nom ASC
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':campus_id' => $identifiant_campus]);
        return $req->fetchAll(\PDO::FETCH_COLUMN); // On récupère juste une liste simple de noms
    }

    // Méthode pour l'auto-complétion des offres
    public function getOffresDesCandidaturesCampus($identifiant_campus) {
        $requete_sql = "
            SELECT DISTINCT offre.offre_titre 
            FROM CANDIDATURE AS candidature 
            JOIN UTILISATEUR AS utilisateur ON utilisateur.utilisateur_id = candidature.utilisateur_id 
            JOIN PROFIL_SCOLAIRE AS profil ON profil.utilisateur_id = utilisateur.utilisateur_id 
            JOIN OFFRE AS offre ON offre.offre_id = candidature.offre_id 
            WHERE profil.campus_id = :campus_id 
            ORDER BY offre.offre_titre ASC
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':campus_id' => $identifiant_campus]);
        return $req->fetchAll(\PDO::FETCH_COLUMN); // Liste simple des titres d'offres
    }
}