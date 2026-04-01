<?php
namespace App\Models;

class EntrepriseModel {
    private $objet_pdo;

    public function __construct(\PDO $pdo) {
        $this->objet_pdo = $pdo;
    }

    public function rechercherEntreprises($nom_recherche, $ville_recherche, $secteur_recherche, $tri_selectionne = '') {
        $requete_sql = "
            SELECT
                entreprise.entreprise_id,
                entreprise.entreprise_nom,
                entreprise.entreprise_extrait,
                ville.ville_nom,
                code_postal.cp_code,
                secteur.secteur_nom,
                COUNT(DISTINCT offre.offre_id) AS nombre_offres,
                entreprise.entreprise_nb_stagiaires
            FROM ENTREPRISE AS entreprise
            JOIN LOCALITE AS localite ON localite.localite_id = entreprise.localite_id
            JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
            JOIN CODE_POSTAL AS code_postal ON code_postal.cp_id = localite.cp_id
            JOIN SECTEUR AS secteur ON secteur.secteur_id = entreprise.secteur_id
            LEFT JOIN OFFRE AS offre ON offre.entreprise_id = entreprise.entreprise_id
            WHERE entreprise.entreprise_archive = 0
        ";

        $parametres_requete = [];
        // on fait de la verification de champs, s'il est non vide, on l'ajoute aux parametres
        if ($nom_recherche !== '') {
            $requete_sql .= " AND entreprise.entreprise_nom LIKE :nom_entreprise";
            $parametres_requete[':nom_entreprise'] = '%' . $nom_recherche . '%'; // ici, les % qui encadrent la valeur permettront de chercher d'autres mots ayant cette portion de lettre.
            // ex : %test% trouvera petest, testpe, petestpe,...
        }

        /*  Si l’utilisateur renseigne une ville, on ajoute une condition SQL pour chercher dans le nom de la ville ou dans le code postal.
        Les parametres sont securises via PDO.
        Si l’utilisateur ne renseigne rien, aucune condition n’est ajoutee pour la ville.
        */

        if ($ville_recherche !== '') {
            $requete_sql .= " AND (ville.ville_nom LIKE :ville_entreprise OR code_postal.cp_code LIKE :ville_cp)";
            $parametres_requete[':ville_entreprise'] = '%' . $ville_recherche . '%';
            $parametres_requete[':ville_cp'] = '%' . $ville_recherche . '%';
        }

        if ($secteur_recherche !== '' && $secteur_recherche !== 'Filtrer par domaine') {
            // on verifie que le champ n'est pas et qu'il n'est pas egal au champ par defaut
            // si ce n'est pas le cas, un filtre est applique
            $requete_sql .= " AND secteur.secteur_nom = :secteur_entreprise";
            $parametres_requete[':secteur_entreprise'] = $secteur_recherche;
        }

        $clause_de_tri = "ORDER BY entreprise.entreprise_nom ASC";
    
        if ($tri_selectionne === 'offres') {
            $clause_de_tri = "ORDER BY nombre_offres DESC, entreprise.entreprise_nom ASC";
        } elseif ($tri_selectionne === 'croissant') {
            $clause_de_tri = "ORDER BY entreprise.entreprise_id ASC";
        } elseif ($tri_selectionne === 'decroissant') {
            $clause_de_tri = "ORDER BY entreprise.entreprise_id DESC";
        }

        $requete_sql .= "
            GROUP BY
                entreprise.entreprise_id,
                entreprise.entreprise_nom,
                entreprise.entreprise_extrait,
                ville.ville_nom,
                code_postal.cp_code,
                secteur.secteur_nom,
                entreprise.entreprise_nb_stagiaires
        " . $clause_de_tri;

        $req = $this->objet_pdo->prepare($requete_sql); // PHP envoie uniquement la structure de la requête SQL au serveur de base de données, sans les données réelles
        $req->execute($parametres_requete); // PHP prend le tableau de valeurs ($parametres_requete) et l'envoie à la base de données.
        
        return $req->fetchAll(\PDO::FETCH_ASSOC); // les donnees de la requete sont dans $req
        // fetchAll recupere toutes les lignes trouvees et les retournes sous forme d'un tableau multidimensionnel - cle/valeur (grace a \PDO::FETCH_ASSOC)
    }

    public function getTousLesSecteurs() {
        $requete_sql = "SELECT secteur_id, secteur_nom FROM SECTEUR ORDER BY secteur_nom ASC";
        $resultat_secteurs = $this->objet_pdo->query($requete_sql);
        
        return $resultat_secteurs->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getEntrepriseParId($identifiant_entreprise) {
        $requete_sql = "
            SELECT entreprise.*, ville.ville_nom, code_postal.cp_code, secteur.secteur_nom
            FROM ENTREPRISE AS entreprise
            JOIN LOCALITE AS localite ON localite.localite_id = entreprise.localite_id
            JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
            JOIN CODE_POSTAL AS code_postal ON code_postal.cp_id = localite.cp_id
            JOIN SECTEUR AS secteur ON secteur.secteur_id = entreprise.secteur_id
            WHERE entreprise.entreprise_id = :entreprise_id
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':entreprise_id' => $identifiant_entreprise]);
        
        return $req->fetch(\PDO::FETCH_ASSOC);
    }

    public function getOffresParEntrepriseId($identifiant_entreprise) {
        $requete_sql = "
            SELECT offre.*, ville.ville_nom, code_postal.cp_code
            FROM OFFRE AS offre
            JOIN LOCALITE AS localite ON localite.localite_id = offre.localite_id
            JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
            JOIN CODE_POSTAL AS code_postal ON code_postal.cp_id = localite.cp_id
            WHERE offre.entreprise_id = :entreprise_id
            AND offre.offre_archive = 0
            ORDER BY offre.offre_date_debut ASC
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':entreprise_id' => $identifiant_entreprise]); // ici on fait le bon choix de la ligne de la table. on va uniquement prendre les offres associees a l'id unique de l'entreprise dont on cherche les offres
        
        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function creerEntreprise($donnees_entreprise) {
        // INSERT IGNORE permet d'essayer d'inserer une valeur. si elle existe deja dans la bdd, on l'ignore (pas d'ajout)
        $requete_insertion_ville = $this->objet_pdo->prepare("INSERT IGNORE INTO VILLE (ville_nom) VALUES (:nom)");
        $requete_insertion_ville->execute([':nom' => $donnees_entreprise['ville_nom']]);
        $identifiant_ville = $this->objet_pdo->lastInsertId(); // cette methode renvoi 0 si aucune ligne n'a ete preparee juste avant (contrainte d'unicite)
        
        if (empty($identifiant_ville)) { // on teste donc si la ligne existe deja ou non. si elle existe, on execute le if
            $requete_verification_ville = $this->objet_pdo->prepare("SELECT ville_id FROM VILLE WHERE ville_nom = :nom");
            $requete_verification_ville->execute([':nom' => $donnees_entreprise['ville_nom']]);
            $identifiant_ville = $requete_verification_ville->fetchColumn(); // prise de la valeur de la premiere colonne (id recherche) -> renvoi un nombre uniquement
        }

        $requete_insertion_code_postal = $this->objet_pdo->prepare("INSERT IGNORE INTO CODE_POSTAL (cp_code) VALUES (:cp)");
        $requete_insertion_code_postal->execute([':cp' => $donnees_entreprise['code_postal']]);
        $identifiant_code_postal = $this->objet_pdo->lastInsertId();
        
        if (empty($identifiant_code_postal)) {
            $requete_verification_code_postal = $this->objet_pdo->prepare("SELECT cp_id FROM CODE_POSTAL WHERE cp_code = :cp");
            $requete_verification_code_postal->execute([':cp' => $donnees_entreprise['code_postal']]);
            $identifiant_code_postal = $requete_verification_code_postal->fetchColumn();
        }
        
        $requete_insertion_localite = $this->objet_pdo->prepare("INSERT IGNORE INTO LOCALITE (ville_id, cp_id) VALUES (:identifiant_ville, :identifiant_code_postal)");
        $requete_insertion_localite->execute([':identifiant_ville' => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
        $identifiant_localite = $this->objet_pdo->lastInsertId();

        if (empty($identifiant_localite)) {
            $requete_verification_localite = $this->objet_pdo->prepare("SELECT localite_id FROM LOCALITE WHERE ville_id = :identifiant_ville AND cp_id = :identifiant_code_postal");
            $requete_verification_localite->execute([':identifiant_ville'  => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
            $identifiant_localite = $requete_verification_localite->fetchColumn();
        }
        
        $requete_sql = "
            INSERT INTO ENTREPRISE (
                entreprise_nom, entreprise_taille, entreprise_siteweb,
                entreprise_email, entreprise_telephone, entreprise_rue,
                entreprise_extrait, entreprise_presentation,
                entreprise_nb_stagiaires, localite_id, secteur_id
            ) VALUES (
                :nom, :taille, :siteweb,
                :email, :telephone, :rue,
                :extrait, :presentation,
                :nb_stagiaires, :localite_id, :secteur_id
            )
        ";

        // ci-dessus on prepare correctement la requete, on va creer nos marqueurs plus bas

        $requete_insertion_entreprise = $this->objet_pdo->prepare($requete_sql);

        $nom_entreprise = $donnees_entreprise['nom'];
        $email_entreprise = $donnees_entreprise['email'];
        $presentation_entreprise = $donnees_entreprise['description'];
        $extrait_presentation = mb_substr($donnees_entreprise['description'], 0, 255);
        $identifiant_secteur = $donnees_entreprise['secteur_id'];

        $taille_entreprise = null; // valeur par défaut si rien n'est rempli
        if (isset($donnees_entreprise['taille'])) {
            $taille_entreprise = $donnees_entreprise['taille'];
        }

        $site_web_entreprise = null;
        if (isset($donnees_entreprise['site_web'])) {
            $site_web_entreprise = $donnees_entreprise['site_web'];
        }

        $telephone_entreprise = null;
        if (isset($donnees_entreprise['telephone'])) {
            $telephone_entreprise = $donnees_entreprise['telephone'];
        }

        $rue_entreprise = ''; 
        if (isset($donnees_entreprise['adresse'])) {
            $rue_entreprise = $donnees_entreprise['adresse'];
        }

        $nombre_stagiaires = 0;
        if (isset($donnees_entreprise['anciens_stagiaires'])) {
            $nombre_stagiaires = $donnees_entreprise['anciens_stagiaires'];
        }

        $parametres_insertion = [
            ':nom' => $nom_entreprise,
            ':taille' => $taille_entreprise,
            ':siteweb' => $site_web_entreprise,
            ':email' => $email_entreprise,
            ':telephone' => $telephone_entreprise,
            ':rue' => $rue_entreprise,
            ':extrait' => $extrait_presentation,
            ':presentation' => $presentation_entreprise,
            ':nb_stagiaires' => $nombre_stagiaires,
            ':localite_id' => $identifiant_localite,
            ':secteur_id' => $identifiant_secteur,
        ];
        // on fait le lien entre les valeurs et nos marqueurs

        $requete_insertion_entreprise->execute($parametres_insertion);
    }

    public function getEntreprisePourModification($identifiant_entreprise) {
        $requete_sql = "
            SELECT 
                e.*, 
                v.ville_nom, 
                cp.cp_code as ville_cp
            FROM ENTREPRISE e
            JOIN LOCALITE l ON e.localite_id = l.localite_id
            JOIN VILLE v ON l.ville_id = v.ville_id
            JOIN CODE_POSTAL cp ON l.cp_id = cp.cp_id
            WHERE e.entreprise_id = :id
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':id' => $identifiant_entreprise]);
        
        return $req->fetch(\PDO::FETCH_ASSOC);
    }

    public function modifierEntreprise($identifiant_entreprise, $donnees_entreprise) {
        $nom_ville = mb_strtoupper(trim($donnees_entreprise['ville_nom']), 'UTF-8'); // mb_strtoupper prend le texte, force la majuscule, trim supprime les espaces inutiles
        $code_postal = trim($donnees_entreprise['code_postal']);

        $requete_verification_ville = $this->objet_pdo->prepare("SELECT ville_id FROM VILLE WHERE ville_nom = :nom");
        $requete_verification_ville->execute([':nom' => $nom_ville]);
        $identifiant_ville = $requete_verification_ville->fetchColumn(); // prise de la valeur de la premiere colonne (id recherche) -> renvoi un nombre uniquement

        // si la ville n'a pas ete trouvee ($identifiant_ville vaut false ou vide)
        if (!$identifiant_ville) {
            $requete_insertion_ville = $this->objet_pdo->prepare("INSERT INTO VILLE (ville_nom) VALUES (:nom)");
            $insertion_reussie = $requete_insertion_ville->execute([':nom' => $nom_ville]);
            if ($insertion_reussie) {
                $identifiant_ville = $this->objet_pdo->lastInsertId(); // on recupere le tout nouvel ID
            } else {
                $identifiant_ville = null;
            }
        }

        $requete_verification_code_postal = $this->objet_pdo->prepare("SELECT cp_id FROM CODE_POSTAL WHERE cp_code = :cp");
        $requete_verification_code_postal->execute([':cp' => $code_postal]);
        $identifiant_code_postal = $requete_verification_code_postal->fetchColumn();

        if (!$identifiant_code_postal) {
            $requete_insertion_code_postal = $this->objet_pdo->prepare("INSERT INTO CODE_POSTAL (cp_code) VALUES (:cp)");
            $insertion_reussie = $requete_insertion_code_postal->execute([':cp' => $code_postal]);
            
            if ($insertion_reussie) {
                $identifiant_code_postal = $this->objet_pdo->lastInsertId();
            } else {
                $identifiant_code_postal = null;
            }
        }

        $requete_verification_localite = $this->objet_pdo->prepare("SELECT localite_id FROM LOCALITE WHERE ville_id = :identifiant_ville AND cp_id = :identifiant_code_postal");
        $requete_verification_localite->execute([':identifiant_ville' => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
        $identifiant_localite = $requete_verification_localite->fetchColumn();

        if (!$identifiant_localite) {
            $requete_insertion_localite = $this->objet_pdo->prepare("INSERT INTO LOCALITE (ville_id, cp_id) VALUES (:identifiant_ville, :identifiant_code_postal)");
            $insertion_reussie = $requete_insertion_localite->execute([':identifiant_ville' => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
            
            if ($insertion_reussie) {
                $identifiant_localite = $this->objet_pdo->lastInsertId();
            } else {
                $identifiant_localite = null;
            }
        }

        // mise a jour de la requete
        $requete_sql = "
            UPDATE ENTREPRISE SET 
                entreprise_nom = :nom, 
                entreprise_taille = :taille, 
                entreprise_siteweb = :siteweb, 
                entreprise_email = :email, 
                entreprise_telephone = :telephone, 
                entreprise_rue = :rue, 
                entreprise_extrait = :extrait, 
                entreprise_presentation = :presentation, 
                entreprise_nb_stagiaires = :nb_stagiaires, 
                localite_id = :localite_id, 
                secteur_id = :secteur_id 
            WHERE entreprise_id = :id
        ";

        $requete_mise_a_jour_entreprise = $this->objet_pdo->prepare($requete_sql);

        $nom_entreprise = $donnees_entreprise['nom'];
        $email_entreprise = $donnees_entreprise['email'];
        $presentation_entreprise = $donnees_entreprise['description'];
        $extrait_presentation = mb_substr($donnees_entreprise['description'], 0, 255); // recuperer une partie d'une chaine de caracteres mb_substr(chaine, debut, longueur)
        $identifiant_secteur = $donnees_entreprise['secteur_id'];

        $taille_entreprise = null;
        if (isset($donnees_entreprise['taille'])) {
            $taille_entreprise = $donnees_entreprise['taille'];
        }

        $site_web_entreprise = null;
        if (isset($donnees_entreprise['site_web'])) {
            $site_web_entreprise = $donnees_entreprise['site_web'];
        }

        $telephone_entreprise = null;
        if (isset($donnees_entreprise['telephone'])) {
            $telephone_entreprise = $donnees_entreprise['telephone'];
        }

        $rue_entreprise = '';
        if (isset($donnees_entreprise['adresse'])) {
            $rue_entreprise = $donnees_entreprise['adresse'];
        }

        $nombre_stagiaires = 0;
        if (isset($donnees_entreprise['anciens_stagiaires'])) {
            $nombre_stagiaires = $donnees_entreprise['anciens_stagiaires'];
        }

        $parametres_mise_a_jour = [
            ':id' => $identifiant_entreprise,
            ':nom' => $nom_entreprise,
            ':taille' => $taille_entreprise,
            ':siteweb' => $site_web_entreprise,
            ':email' => $email_entreprise,
            ':telephone' => $telephone_entreprise,
            ':rue' => $rue_entreprise,
            ':extrait' => $extrait_presentation,
            ':presentation' => $presentation_entreprise,
            ':nb_stagiaires' => $nombre_stagiaires,
            ':localite_id' => $identifiant_localite,
            ':secteur_id' => $identifiant_secteur
        ];

        return $requete_mise_a_jour_entreprise->execute($parametres_mise_a_jour);
    }

    public function getNombreOffres($identifiant_entreprise) {
        $requete_comptage = $this->objet_pdo->prepare("SELECT COUNT(*) FROM OFFRE WHERE entreprise_id = :id");
        $requete_comptage->execute([':id' => $identifiant_entreprise]);
        
        return (int) $requete_comptage->fetchColumn();
    }

    public function supprimerEntreprise($identifiant_entreprise) {
        try {
            // cette fonction permet de regrouper les requetes sql que nous allons faire. si une seule echoue, rien n'est change dans notre bdd, c'est une securite
            $this->objet_pdo->beginTransaction();
            /*
            PDO::beginTransaction() désactive le mode autocommit.
            Lorsque l'autocommit est désactivé, les modifications faites sur la base de donnéees via les instances des objets PDO ne sont pas appliquees
            tant qu'on ne met pas fin à la transaction en appelant la fonction PDO::commit(). L'appel de PDO::rollBack() annulera toutes les modifications
            faites à la base de donnees et remettra la connexion en mode autocommit.

            Quelques bases de donnees, dont MySQL, executeront automatiquement un COMMIT lorsqu'une requete de définition de langage de base de donnees (DDL)
            comme DROP TABLE ou CREATE TABLE est exécutée dans une transaction. Ce COMMIT implicite empêchera d'annuler toutes autres modifications faites
            dans cette transaction.
            */

            $this->objet_pdo->prepare("DELETE FROM WISHLIST WHERE offre_id IN (SELECT offre_id FROM OFFRE WHERE entreprise_id = :id)")->execute([':id' => $identifiant_entreprise]);
            $this->objet_pdo->prepare("DELETE FROM CANDIDATURE WHERE offre_id IN (SELECT offre_id FROM OFFRE WHERE entreprise_id = :id)")->execute([':id' => $identifiant_entreprise]);
            $this->objet_pdo->prepare("DELETE FROM OFFRE_COMPETENCE WHERE offre_id IN (SELECT offre_id FROM OFFRE WHERE entreprise_id = :id)")->execute([':id' => $identifiant_entreprise]);
            $this->objet_pdo->prepare("DELETE FROM OFFRE WHERE entreprise_id = :id")->execute([':id' => $identifiant_entreprise]);
            
            $this->objet_pdo->prepare("DELETE FROM EVALUATION WHERE entreprise_id = :id")->execute([':id' => $identifiant_entreprise]);
            $this->objet_pdo->prepare("DELETE FROM ENTREPRISE WHERE entreprise_id = :id")->execute([':id' => $identifiant_entreprise]);

            // on confirme toutes les suppressions dans la bdd, si aucune erreur, tout est applique
            $this->objet_pdo->commit();
            return true;
            
        } catch (\PDOException $exception_pdo) { // si une erreur survient, tout est annule !
            $this->objet_pdo->rollBack();
            return false;
        }
    }

    public function aDesOffresOuCandidatures($identifiant_entreprise) {
        $requete_verification = $this->objet_pdo->prepare("
            SELECT COUNT(*) FROM OFFRE WHERE entreprise_id = :id
            UNION ALL
            SELECT COUNT(*) FROM CANDIDATURE WHERE offre_id IN (SELECT offre_id FROM OFFRE WHERE entreprise_id = :id2)
        ");
        $requete_verification->execute([':id' => $identifiant_entreprise, ':id2' => $identifiant_entreprise]);
        $liste_resultats = $requete_verification->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($liste_resultats as $nombre_trouve) {
            if ((int)$nombre_trouve > 0) {
                return true;
            }
        }
        
        return false;
    }

    public function archiverEntreprise($identifiant_entreprise) {
        $requete_archivage_entreprise = $this->objet_pdo->prepare("UPDATE ENTREPRISE SET entreprise_archive = 1 WHERE entreprise_id = :id");
        $requete_archivage_entreprise->execute([':id' => $identifiant_entreprise]);
        
        $requete_archivage_offres = $this->objet_pdo->prepare("UPDATE OFFRE SET offre_archive = 1 WHERE entreprise_id = :id");
        $requete_archivage_offres->execute([':id' => $identifiant_entreprise]);
    }
}