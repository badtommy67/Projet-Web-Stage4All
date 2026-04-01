<?php
namespace App\Models;

class OffreModel {
    private $objet_pdo;

    public function __construct(\PDO $pdo) {
        $this->objet_pdo = $pdo;
    }

    public function rechercherOffres($mots_cles, $ville_recherchee, $duree_filtree, $secteur_recherche, $tri_selectionne) {
        $requete_sql = "
            SELECT
                offre.offre_id,
                offre.offre_titre,
                offre.offre_reference,
                offre.offre_description,
                offre.offre_duree,
                offre.offre_remuneration,
                offre.offre_date_publication,
                entreprise.entreprise_id,
                entreprise.entreprise_nom,
                ville.ville_nom,
                code_postal.cp_code,
                secteur.secteur_nom
            FROM OFFRE AS offre
            LEFT JOIN ENTREPRISE  AS entreprise ON entreprise.entreprise_id = offre.entreprise_id
            LEFT JOIN LOCALITE AS localite ON localite.localite_id = offre.localite_id
            LEFT JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
            LEFT JOIN CODE_POSTAL AS code_postal ON code_postal.cp_id = localite.cp_id
            LEFT JOIN SECTEUR AS secteur ON secteur.secteur_id = entreprise.secteur_id
            WHERE offre.offre_archive = 0
        ";

        $parametres_requete = [];

        if ($mots_cles !== '') {
            $requete_sql .= "
                AND (
                    offre.offre_titre LIKE :mots_cles
                    OR offre.offre_description LIKE :mots_cles2
                    OR entreprise.entreprise_nom LIKE :mots_cles4 -- AJOUT ICI
                    OR EXISTS (
                        SELECT 1
                        FROM OFFRE_COMPETENCE AS oc
                        JOIN COMPETENCE AS competence ON competence.competence_id = oc.competence_id
                        WHERE oc.offre_id = offre.offre_id
                        AND competence.competence_libelle LIKE :mots_cles3
                    )
                )
            ";
            $parametres_requete[':mots_cles'] = '%' . $mots_cles . '%';
            $parametres_requete[':mots_cles2'] = '%' . $mots_cles . '%';
            $parametres_requete[':mots_cles3'] = '%' . $mots_cles . '%';
            $parametres_requete[':mots_cles4'] = '%' . $mots_cles . '%';
        }

        if ($ville_recherchee !== '') {
            $requete_sql .= " AND (ville.ville_nom LIKE :ville OR code_postal.cp_code LIKE :ville_cp)";
            $parametres_requete[':ville'] = '%' . $ville_recherchee . '%';
            $parametres_requete[':ville_cp'] = '%' . $ville_recherchee . '%';
        }

        if ($duree_filtree !== '') {
            $condition_duree = match($duree_filtree) {
                'inf2' => "offre.offre_duree < 2",
                '2-3'=> "offre.offre_duree BETWEEN 2 AND 3",
                '4-6' => "offre.offre_duree BETWEEN 4 AND 6",
                '6-12'=> "offre.offre_duree BETWEEN 6 AND 12",
                'sup12' => "offre.offre_duree > 12",
                default => ""
            };

            if ($condition_duree !== "") {
                $requete_sql .= " AND " . $condition_duree;
            }
        }

        if ($secteur_recherche !== '') {
            $requete_sql .= " AND secteur.secteur_nom = :secteur";
            $parametres_requete[':secteur'] = $secteur_recherche;
        }

        $requete_sql .= match($tri_selectionne) {
            'croissant'=> " ORDER BY offre.offre_date_publication ASC",
            'decroissant'=> " ORDER BY offre.offre_date_publication DESC",
            'alpha' => " ORDER BY offre.offre_titre ASC",
            default => " ORDER BY offre.offre_date_publication DESC",
        };

        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute($parametres_requete);

        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTousLesSecteurs() {
        $requete_sql = "SELECT secteur_id, secteur_nom FROM SECTEUR ORDER BY secteur_nom ASC";
        $resultat_secteurs = $this->objet_pdo->query($requete_sql);
        return $resultat_secteurs->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getToutesLesCompetences() {
        $requete_sql = "SELECT competence_id, competence_libelle FROM COMPETENCE ORDER BY competence_libelle ASC";
        $resultat_competences = $this->objet_pdo->query($requete_sql);
        return $resultat_competences->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCompetencesIdParOffreId($identifiant_offre) {
        $requete_sql = "SELECT competence_id FROM OFFRE_COMPETENCE WHERE offre_id = :offre_id";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':offre_id' => $identifiant_offre]);
        return $req->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getOffreParId($identifiant_offre) {
        $requete_sql = "
            SELECT
                offre.offre_id,
                offre.offre_titre,
                offre.offre_reference,
                offre.offre_description,
                offre.offre_missions,
                offre.offre_profil,
                offre.offre_date_debut,
                offre.offre_duree,
                offre.offre_remuneration,
                offre.offre_date_publication,
                entreprise.entreprise_id,
                entreprise.entreprise_nom,
                entreprise.entreprise_presentation,
                entreprise.entreprise_email,
                entreprise.entreprise_siteweb,
                ville.ville_nom,
                code_postal.cp_code,
                secteur.secteur_nom
            FROM OFFRE AS offre
            JOIN ENTREPRISE  AS entreprise  ON entreprise.entreprise_id = offre.entreprise_id
            JOIN LOCALITE AS localite ON localite.localite_id = offre.localite_id
            JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
            JOIN CODE_POSTAL AS code_postal ON code_postal.cp_id = localite.cp_id
            JOIN SECTEUR AS secteur ON secteur.secteur_id = entreprise.secteur_id
            WHERE offre.offre_id = :offre_id
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':offre_id' => $identifiant_offre]);

        return $req->fetch(\PDO::FETCH_ASSOC);
    }

    public function getCompetencesParOffreId($identifiant_offre) {
        $requete_sql = "
            SELECT competence.competence_libelle
            FROM COMPETENCE AS competence
            JOIN OFFRE_COMPETENCE AS offre_competence ON offre_competence.competence_id = competence.competence_id
            WHERE offre_competence.offre_id = :offre_id
        ";
        $req = $this->objet_pdo->prepare($requete_sql);
        $req->execute([':offre_id' => $identifiant_offre]);

        return $req->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getDernieresOffres(int $limite_affichage = 3) {
        $requete_sql = "
            SELECT
                offre.offre_id,
                offre.offre_titre,
                offre.offre_description,
                offre.offre_duree,
                ville.ville_nom,
                code_postal.cp_code
            FROM OFFRE AS offre
            JOIN LOCALITE AS localite ON localite.localite_id = offre.localite_id
            JOIN VILLE AS ville ON ville.ville_id = localite.ville_id
            JOIN CODE_POSTAL AS code_postal ON code_postal.cp_id = localite.cp_id
            WHERE offre.offre_archive = 0
            ORDER BY offre.offre_date_publication DESC
            LIMIT :limite
        ";

        $req = $this->objet_pdo->prepare($requete_sql);
        $req->bindValue(':limite', $limite_affichage, \PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getToutesLesEntreprises() {
        $requete_sql = "SELECT entreprise_id, entreprise_nom FROM ENTREPRISE WHERE entreprise_archive = 0 ORDER BY entreprise_nom ASC";
        $resultat_entreprises = $this->objet_pdo->query($requete_sql);
        return $resultat_entreprises->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function ajouterCompetencesOffre($identifiant_offre, $donnees_formulaire) {
        if (!empty($donnees_formulaire['competences_ids'])) {
            foreach (explode(',', $donnees_formulaire['competences_ids']) as $identifiant_competence) {
                $identifiant_competence = (int)trim($identifiant_competence);
                if ($identifiant_competence > 0) {
                    $requete_lien = $this->objet_pdo->prepare("INSERT IGNORE INTO OFFRE_COMPETENCE (offre_id, competence_id) VALUES (:identifiant_offre, :identifiant_competence)");
                    $requete_lien->execute([':identifiant_offre' => $identifiant_offre, ':identifiant_competence' => $identifiant_competence]);
                }
            }
        }

        /*
        On recupere la chaîne complète depuis le formulaire $donnees_formulaire['nouvelles_competences'].
        On parcourt chaque caractere pour detecter les virgules comme separateurs.
        substr permet de recuperer chaque mot entre les virgules.
        trim enleve les espaces superflus autour de chaque mot.
        On ajoute dans $nouvelles seulement si la valeur n’est pas vide,
        */

        if (!empty($donnees_formulaire['nouvelles_competences'])) {
            $chaine_competences = $donnees_formulaire['nouvelles_competences'];
            $liste_nouvelles_competences = [];
            $position_debut = 0; // position du début de chaque compétence
            $longueur_totale = strlen($chaine_competences);

            for ($i = 0; $i <= $longueur_totale; $i++) {
                if ($i == $longueur_totale || $chaine_competences[$i] == ',') {
                    $libelle_competence = substr($chaine_competences, $position_debut, $i - $position_debut);
                    $libelle_competence = trim($libelle_competence);
                    if ($libelle_competence !== '') {
                        $liste_nouvelles_competences[] = mb_strtoupper($libelle_competence, 'UTF-8');
                    }
                    $position_debut = $i + 1;
                }
            }

            foreach ($liste_nouvelles_competences as $libelle_final) {
                $requete_insertion_competence = $this->objet_pdo->prepare("INSERT IGNORE INTO COMPETENCE (competence_libelle) VALUES (:libelle)");
                $requete_insertion_competence->execute([':libelle' => $libelle_final]);
                $identifiant_competence_cree = $this->objet_pdo->lastInsertId();
                
                if (empty($identifiant_competence_cree)) {
                    $requete_selection_competence = $this->objet_pdo->prepare("SELECT competence_id FROM COMPETENCE WHERE competence_libelle = :libelle");
                    $requete_selection_competence->execute([':libelle' => $libelle_final]);
                    $identifiant_competence_cree = $requete_selection_competence->fetchColumn();
                }
                $requete_lien_nouvelle = $this->objet_pdo->prepare("INSERT IGNORE INTO OFFRE_COMPETENCE (offre_id, competence_id) VALUES (:identifiant_offre, :identifiant_competence)");
                $requete_lien_nouvelle->execute([':identifiant_offre' => $identifiant_offre, ':identifiant_competence' => $identifiant_competence_cree]);
            }
        }
    }

    public function creerOffre($donnees_offre) {
        $requete_ville = $this->objet_pdo->prepare("INSERT IGNORE INTO VILLE (ville_nom) VALUES (:nom)");
        $requete_ville->execute([':nom' => $donnees_offre['ville_nom']]);
        $identifiant_ville = $this->objet_pdo->lastInsertId();
        if (empty($identifiant_ville)) {
            $requete_selection_ville = $this->objet_pdo->prepare("SELECT ville_id FROM VILLE WHERE ville_nom = :nom");
            $requete_selection_ville->execute([':nom' => $donnees_offre['ville_nom']]);
            $identifiant_ville = $requete_selection_ville->fetchColumn();
        }

        $requete_code_postal = $this->objet_pdo->prepare("INSERT IGNORE INTO CODE_POSTAL (cp_code) VALUES (:cp)");
        $requete_code_postal->execute([':cp' => $donnees_offre['code_postal']]);
        $identifiant_code_postal = $this->objet_pdo->lastInsertId();
        if (empty($identifiant_code_postal)) {
            $requete_selection_code_postal = $this->objet_pdo->prepare("SELECT cp_id FROM CODE_POSTAL WHERE cp_code = :cp");
            $requete_selection_code_postal->execute([':cp' => $donnees_offre['code_postal']]);
            $identifiant_code_postal = $requete_selection_code_postal->fetchColumn();
        }

        $requete_localite = $this->objet_pdo->prepare("INSERT IGNORE INTO LOCALITE (ville_id, cp_id) VALUES (:identifiant_ville, :identifiant_code_postal)");
        $requete_localite->execute([':identifiant_ville' => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
        $identifiant_localite = $this->objet_pdo->lastInsertId();
        if (empty($identifiant_localite)) {
            $requete_selection_localite = $this->objet_pdo->prepare("SELECT localite_id FROM LOCALITE WHERE ville_id = :identifiant_ville AND cp_id = :identifiant_code_postal");
            $requete_selection_localite->execute([':identifiant_ville' => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
            $identifiant_localite = $requete_selection_localite->fetchColumn();
        }

        $requete_sql = "
            INSERT INTO OFFRE (
                offre_titre, offre_reference, offre_description, offre_missions,
                offre_profil, offre_date_debut, offre_duree, offre_remuneration,
                offre_date_publication, localite_id, entreprise_id
            ) VALUES (
                :titre, :reference, :description, :missions,
                :profil, :date_debut, :duree, :remuneration,
                CURDATE(), :localite_id, :entreprise_id
            )
        ";
        $requete_insertion_offre = $this->objet_pdo->prepare($requete_sql);
        $missions = '';
        if (isset($donnees_offre['missions'])) {
            $missions = $donnees_offre['missions'];
        }

        $remuneration = null;
        if (isset($donnees_offre['remuneration'])) {
            $remuneration = $donnees_offre['remuneration'];
        }

        $requete_insertion_offre->execute([
            ':titre' => $donnees_offre['titre'],
            ':reference' => $donnees_offre['reference'],
            ':description' => $donnees_offre['description'],
            ':missions' => $missions,
            ':profil' => $donnees_offre['profil'],
            ':date_debut' => $donnees_offre['date_debut'],
            ':duree' => (int) $donnees_offre['duree'],
            ':remuneration' => $remuneration,
            ':localite_id' => $identifiant_localite,
            ':entreprise_id' => $donnees_offre['entreprise_id'],
        ]);

        $identifiant_nouvelle_offre = $this->objet_pdo->lastInsertId();
        $this->ajouterCompetencesOffre($identifiant_nouvelle_offre, $donnees_offre);
    }

    public function modifierOffre($identifiant_offre, $donnees_offre) {
        $requete_ville = $this->objet_pdo->prepare("INSERT IGNORE INTO VILLE (ville_nom) VALUES (:nom)");
        $requete_ville->execute([':nom' => $donnees_offre['ville_nom']]);
        $identifiant_ville = $this->objet_pdo->lastInsertId();
        if (empty($identifiant_ville)) {
            $requete_selection_ville = $this->objet_pdo->prepare("SELECT ville_id FROM VILLE WHERE ville_nom = :nom");
            $requete_selection_ville->execute([':nom' => $donnees_offre['ville_nom']]);
            $identifiant_ville = $requete_selection_ville->fetchColumn();
        }

        $requete_code_postal = $this->objet_pdo->prepare("INSERT IGNORE INTO CODE_POSTAL (cp_code) VALUES (:cp)");
        $requete_code_postal->execute([':cp' => $donnees_offre['code_postal']]);
        $identifiant_code_postal = $this->objet_pdo->lastInsertId();
        if (empty($identifiant_code_postal)) {
            $requete_selection_code_postal = $this->objet_pdo->prepare("SELECT cp_id FROM CODE_POSTAL WHERE cp_code = :cp");
            $requete_selection_code_postal->execute([':cp' => $donnees_offre['code_postal']]);
            $identifiant_code_postal = $requete_selection_code_postal->fetchColumn();
        }

        $requete_localite = $this->objet_pdo->prepare("INSERT IGNORE INTO LOCALITE (ville_id, cp_id) VALUES (:identifiant_ville, :identifiant_code_postal)");
        $requete_localite->execute([':identifiant_ville' => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
        $identifiant_localite = $this->objet_pdo->lastInsertId();
        if (empty($identifiant_localite)) {
            $requete_selection_localite = $this->objet_pdo->prepare("SELECT localite_id FROM LOCALITE WHERE ville_id = :identifiant_ville AND cp_id = :identifiant_code_postal");
            $requete_selection_localite->execute([':identifiant_ville' => $identifiant_ville, ':identifiant_code_postal' => $identifiant_code_postal]);
            $identifiant_localite = $requete_selection_localite->fetchColumn();
        }

        $requete_sql = "
            UPDATE OFFRE SET
                offre_titre = :titre,
                offre_description  = :description,
                offre_missions = :missions,
                offre_profil = :profil,
                offre_date_debut = :date_debut,
                offre_duree = :duree,
                offre_remuneration = :remuneration,
                localite_id = :localite_id,
                entreprise_id = :entreprise_id
            WHERE offre_id = :id
        ";
        $requete_mise_a_jour = $this->objet_pdo->prepare($requete_sql);
        
        $titre_offre = $donnees_offre['titre'];
        $description_offre = $donnees_offre['description'];

        $missions_offre = '';
        if (isset($donnees_offre['missions'])) {
            $missions_offre = $donnees_offre['missions'];
        }

        $profil_offre = $donnees_offre['profil'];
        $date_debut_offre = $donnees_offre['date_debut'];
        $duree_offre = (int) $donnees_offre['duree'];

        $remuneration_offre = null;
        if (isset($donnees_offre['remuneration'])) {
            $remuneration_offre = $donnees_offre['remuneration'];
        }

        $entreprise_id_offre = $donnees_offre['entreprise_id'];

        $requete_mise_a_jour->execute([
            ':titre' => $titre_offre,
            ':description' => $description_offre,
            ':missions' => $missions_offre,
            ':profil' => $profil_offre,
            ':date_debut' => $date_debut_offre,
            ':duree' => $duree_offre,
            ':remuneration' => $remuneration_offre,
            ':localite_id' => $identifiant_localite,
            ':entreprise_id' => $entreprise_id_offre,
            ':id' => $identifiant_offre,
        ]);

        $this->objet_pdo->prepare("DELETE FROM OFFRE_COMPETENCE WHERE offre_id = :id")->execute([':id' => $identifiant_offre]);
        $this->ajouterCompetencesOffre($identifiant_offre, $donnees_offre);
    }

    public function getNombreCandidatures($identifiant_offre) {
        $requete_comptage = $this->objet_pdo->prepare("SELECT COUNT(*) FROM CANDIDATURE WHERE offre_id = :id");
        $requete_comptage->execute([':id' => $identifiant_offre]);
        return (int) $requete_comptage->fetchColumn();
    }

    public function aDesCandidatures($identifiant_offre) {
        return $this->getNombreCandidatures($identifiant_offre) > 0;
    }

    public function archiverOffre($identifiant_offre) {
        $requete_archivage = $this->objet_pdo->prepare("UPDATE OFFRE SET offre_archive = 1 WHERE offre_id = :id");
        $requete_archivage->execute([':id' => $identifiant_offre]);
    }

    public function supprimerOffre($identifiant_offre) {
        try {
            $this->objet_pdo->beginTransaction();
            $this->objet_pdo->prepare("DELETE FROM WISHLIST WHERE offre_id = :id")->execute([':id' => $identifiant_offre]);
            $this->objet_pdo->prepare("DELETE FROM CANDIDATURE WHERE offre_id = :id")->execute([':id' => $identifiant_offre]);
            $this->objet_pdo->prepare("DELETE FROM OFFRE_COMPETENCE WHERE offre_id = :id")->execute([':id' => $identifiant_offre]);
            $this->objet_pdo->prepare("DELETE FROM OFFRE WHERE offre_id = :id")->execute([':id' => $identifiant_offre]);
            $this->objet_pdo->commit();
            return true;
        } catch (\PDOException $exception_pdo) {
            $this->objet_pdo->rollBack();
            return false;
        }
    }
}