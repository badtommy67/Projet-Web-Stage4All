<?php
namespace App\Models;

use PDO;

class WishlistModel {
    private PDO $objet_pdo;

    public function __construct(PDO $pdo) {
        $this->objet_pdo = $pdo;
    }
    

    //SFx 23 Récupérer toutes les offres de la wishlist d'un étudiant
    public function getWishlistParEtudiant(int $identifiant_utilisateur): array {
        $requete_sql = "SELECT w.wishlist_date_ajout, o.*, e.entreprise_nom, v.ville_nom
                FROM WISHLIST w
                JOIN OFFRE o ON w.offre_id = o.offre_id
                JOIN ENTREPRISE e ON o.entreprise_id = e.entreprise_id
                JOIN LOCALITE l ON o.localite_id = l.localite_id
                JOIN VILLE v ON l.ville_id = v.ville_id
                WHERE w.utilisateur_id = :user_id
                ORDER BY w.wishlist_date_ajout DESC";
                
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute(['user_id' => $identifiant_utilisateur]);
        
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWishlistIdsParEtudiant(int $identifiant_utilisateur): array {
        $requete_sql = "SELECT offre_id FROM WISHLIST WHERE utilisateur_id = :user_id";
        
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute(['user_id' => $identifiant_utilisateur]);
        return $req->fetchAll(PDO::FETCH_COLUMN); 
    }

    //SFx 24 Ajouter une offre à la wishlist
    public function ajouter(int $identifiant_utilisateur, int $identifiant_offre): bool {
        $requete_sql = "INSERT IGNORE INTO WISHLIST (utilisateur_id, offre_id, wishlist_date_ajout) 
                VALUES (:user_id, :offre_id, NOW())";
                
        $req = $this->objet_pdo->prepare($requete_sql);
        return $req->execute([
            'user_id' => $identifiant_utilisateur,
            'offre_id' => $identifiant_offre
        ]);
    }

    //SFx 25  Retirer une offre de la wishlist
    public function retirer(int $identifiant_utilisateur, int $identifiant_offre): bool {
        $requete_sql = "DELETE FROM WISHLIST 
                WHERE utilisateur_id = :user_id AND offre_id = :offre_id";
                
        $req = $this->objet_pdo->prepare($requete_sql);
        return $req->execute([
            'user_id' => $identifiant_utilisateur,
            'offre_id' => $identifiant_offre
        ]);
    }
}