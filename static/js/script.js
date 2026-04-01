// 1. NAVIGATION BURGER (Menu Mobile)

const boutonBurger = document.getElementById("burger-toggle");
const menuNavigation = document.querySelector(".navigation-principale");

// on s'assure que les elements existent sur la page actuelle avant de lancer le code
if (boutonBurger !== null && menuNavigation !== null) {

    // Action 1 : Ouvrir/Fermer le menu au clic sur le bouton
    boutonBurger.addEventListener("click", () => {
        // toggle() est pratique : il ajoute la classe si elle n'existe pas, ou la retire si elle existe
        menuNavigation.classList.toggle("ouvert");
    });

    // Action 2 : Fermer le menu si on clique sur un lien à l'intérieur
    const tousLesLiensDuMenu = document.querySelectorAll(".navigation-principale a");
    
    tousLesLiensDuMenu.forEach((lien) => {
        lien.addEventListener("click", () => {
            menuNavigation.classList.remove("ouvert");
        });
    });

    // Action 3 : Fermer le menu si on clique en dehors du menu ou du bouton burger
    document.addEventListener("click", (evenement) => {
        const elementClique = evenement.target;
        
        const clicEstSurLeBouton = boutonBurger.contains(elementClique);
        const clicEstSurLeMenu = menuNavigation.contains(elementClique);

        // Si on a clique ni sur le bouton, ni sur le menu, alors on ferme
        if (clicEstSurLeBouton === false && clicEstSurLeMenu === false) {
            menuNavigation.classList.remove("ouvert");
        }
    });

    // Action 4 : Fermer le menu automatiquement si on agrandit l'écran (passage sur ordinateur)
    window.addEventListener("resize", () => {
        const largeurEcran = window.innerWidth;
        
        if (largeurEcran >= 768) {
            menuNavigation.classList.remove("ouvert");
        }
    });
}

// 2. RECHERCHE PAR VILLE OU CODE POSTAL

/*document.addEventListener('DOMContentLoaded', ...) sert a dire au navigateur
"Attends que toute la structure de la page (le HTML) soit entièrement lue et construite avant d'exécuter ce code."
*/

document.addEventListener('DOMContentLoaded', () => {
    const champSaisieVille = document.getElementById('where');
    const listeSuggestionsHTML = document.getElementById('suggestions');

    // On verifie que les elements existent sur cette page
    if (champSaisieVille !== null && listeSuggestionsHTML !== null) {

        // On ecoute chaque fois que l'utilisateur tape une lettre
        champSaisieVille.addEventListener('input', () => {
            const texteSaisi = champSaisieVille.value.trim();

            // Si le texte fait moins de 3 caracteres, on ne le prend pas en compte pour la suite pour eviter la surcharge serveur
            if (texteSaisi.length < 3) {
                listeSuggestionsHTML.innerHTML = '';
                listeSuggestionsHTML.classList.remove('active');
                return; // Arrete l'execution de la fonction ici
            }

            // On verifie si ce qui est tape est uniquement compose de chiffres
            // L'expression régulière /^\d+$/ signifie "du debut à la fin, que des chiffres"
            const estUnCodePostal = /^\d+$/.test(texteSaisi);
            
            // On prepare l'adresse de l'API gouvernementale
            let adresseAPI = "";

            if (estUnCodePostal === true) {
                // Recherche par code postal
                adresseAPI = `https://geo.api.gouv.fr/communes?codePostal=${texteSaisi}&fields=nom,codesPostaux&limit=5`;
            } else {
                // Recherche par nom de ville
                adresseAPI = `https://geo.api.gouv.fr/communes?nom=${texteSaisi}&fields=nom,codesPostaux&boost=population&limit=5`;
            }

            // On interroge l'API
            fetch(adresseAPI)
                .then((reponse) => {
                    return reponse.json(); // On transforme la reponse en donnees comprehensibles (JSON)
                })
                .then((villesTrouvees) => {
                    // On vide l'ancienne liste de suggestions
                    listeSuggestionsHTML.innerHTML = '';

                    // S'il y a des resultats
                    if (villesTrouvees.length > 0) {
                        listeSuggestionsHTML.classList.add('active');

                        // Pour chaque ville trouvee, on cree un element <li>
                        villesTrouvees.forEach((ville) => {
                            const nouvelElementLi = document.createElement('li');
                            
                            // On affiche : Nom de la ville (Code postal)
                            nouvelElementLi.textContent = `${ville.nom} (${ville.codesPostaux})`;
                            
                            // Au clic sur cette suggestion
                            nouvelElementLi.addEventListener('click', () => {
                                champSaisieVille.value = nouvelElementLi.textContent;
                                listeSuggestionsHTML.innerHTML = '';
                                listeSuggestionsHTML.classList.remove('active');
                            });

                            // On ajoute ce <li> a la liste <ul>
                            listeSuggestionsHTML.appendChild(nouvelElementLi);
                        });
                    } else {
                        // S'il n'y a aucun resultat, on cache la liste
                        listeSuggestionsHTML.classList.remove('active');
                    }
                })
                .catch((erreur) => {
                    console.error("Erreur lors de la recherche de ville :", erreur);
                });
        });

        // Fermer les suggestions si on clique ailleurs sur la page
        document.addEventListener('click', (evenement) => {
            const elementClique = evenement.target;
            
            const clicSurChamp = champSaisieVille.contains(elementClique);
            const clicSurListe = listeSuggestionsHTML.contains(elementClique);

            if (clicSurChamp === false && clicSurListe === false) {
                listeSuggestionsHTML.classList.remove('active');
            }
        });
    }

    // 3. MENU PROFIL - ONGLET ACTIF EN SURBRILLANCE
    const liensMenuProfil = document.querySelectorAll('.lien-menu-profil');
    
    // Si on a trouve des liens de profil
    if (liensMenuProfil.length > 0) {
        
        // On met le premier lien en "actif" par défaut
        liensMenuProfil.classList.add('actif');
        
        // On ecoute le clic sur chaque lien
        liensMenuProfil.forEach((lien) => {
            lien.addEventListener('click', () => {
                
                // 1. On retire la classe "actif" de TOUS les liens
                liensMenuProfil.forEach((l) => {
                    l.classList.remove('actif');
                });
                
                // 2. On ajoute la classe "actif" UNIQUEMENT sur celui qui vient d'être cliqué
                lien.classList.add('actif');
            });
        });
    }

    // 4. CODE POSTAL → VILLE LORS DE L'EDITION DE L'ENTREPRISE
    const champCodePostalEdition = document.getElementById('code_postal');
    const menuDeroulantVille = document.getElementById('ville_nom');

    if (champCodePostalEdition !== null && menuDeroulantVille !== null) {
        
        // On recupere la ville actuelle si elle existe
        const villeActuelle = champCodePostalEdition.dataset.villeActuelle || '';

        champCodePostalEdition.addEventListener('input', () => {
            const codePostalSaisi = champCodePostalEdition.value;

            // Un code postal français fait exactement 5 caracteres
            if (codePostalSaisi.length !== 5) {
                menuDeroulantVille.innerHTML = '<option value="" disabled selected>Entrez 5 chiffres</option>';
                return; // On s'arrete ici
            }

            // Message d'attente
            menuDeroulantVille.innerHTML = '<option value="" disabled selected>Chargement...</option>';

            const adresseAPI = `https://geo.api.gouv.fr/communes?codePostal=${codePostalSaisi}&fields=nom&format=json`;

            fetch(adresseAPI)
                .then((reponse) => {
                    return reponse.json();
                })
                .then((villesTrouvees) => {
                    // On vide le menu deroulant
                    menuDeroulantVille.innerHTML = '';

                    // On cree l'option par defaut
                    const optionParDefaut = document.createElement('option');
                    optionParDefaut.value = '';
                    optionParDefaut.textContent = '-- Choisir une ville --';
                    optionParDefaut.disabled = true;
                    optionParDefaut.selected = true;
                    menuDeroulantVille.appendChild(optionParDefaut);

                    if (villesTrouvees.length > 0) {
                        // On ajoute chaque ville trouvee au menu deroulant
                        villesTrouvees.forEach((ville) => {
                            const optionVille = document.createElement('option');
                            optionVille.value = ville.nom;
                            optionVille.textContent = ville.nom;
                            
                            // Si cette ville est la ville actuelle de l'utilisateur, on la selectionne
                            if (ville.nom === villeActuelle) {
                                optionVille.selected = true;
                                optionParDefaut.selected = false;
                            }
                            
                            menuDeroulantVille.appendChild(optionVille);
                        });
                    } else {
                        // Aucun resultat pour ce code postal
                        optionParDefaut.textContent = 'Code postal inconnu';
                    }
                })
                .catch(() => {
                    menuDeroulantVille.innerHTML = '<option value="" disabled selected>Erreur de réseau</option>';
                });
        });
    }

    // 5. LIAISON ENTREPRISE
    const champSaisieEntreprise = document.getElementById('entreprise_input');
    const champCacheIdEntreprise = document.getElementById('entreprise_id_hidden');

    if (champSaisieEntreprise !== null && champCacheIdEntreprise !== null) {
        
        champSaisieEntreprise.addEventListener('input', () => {
            const nomSaisi = champSaisieEntreprise.value.toLowerCase().trim();
            
            // On verifie si la variable `entreprisesMap` existe
            const mapExiste = typeof entreprisesMap !== 'undefined';
            
            // Si la map existe et que le nom saisi correspond a une entreprise existante
            if (mapExiste && entreprisesMap[nomSaisi] !== undefined) {
                // On met l'ID de l'entreprise dans le champ cache
                champCacheIdEntreprise.value = entreprisesMap[nomSaisi];
                
                // On enleve le message d'erreur du navigateur
                champSaisieEntreprise.setCustomValidity(''); 
            } else {
                // L'entreprise n'existe pas dans la liste
                champCacheIdEntreprise.value = '';
                
                // On cree un message d'erreur personnalise
                champSaisieEntreprise.setCustomValidity('Veuillez sélectionner une entreprise valide dans la liste.');
            }
        });
    }

    // 6. GESTION DES COMPÉTENCES
    const champSaisieCompetence = document.getElementById('competence-input');
    const conteneurTags = document.getElementById('competences-selectionnees');
    const champCacheIdsCompetences = document.getElementById('competences-ids-input');
    const champCacheNouvellesCompetences = document.getElementById('nouvelles-competences-input');

    // Tableaux pour stocker nos donnees
    let idsSelectionnes = [];
    let nouvellesCompetences = [];

    if (champSaisieCompetence !== null && conteneurTags !== null) {
        
        // On ecoute les touches tapees dans le champ
        champSaisieCompetence.addEventListener('keydown', (evenement) => {
            
            // Si la touche appuyée est "Entree"
            if (evenement.key === 'Enter') {
                // On empeche le formulaire de s'envoyer
                evenement.preventDefault();
                
                const texteSaisi = champSaisieCompetence.value.trim();
                
                // Si le champ est vide, on ne fait rien
                if (texteSaisi === '') {
                    return; 
                }

                const texteSaisiMinuscule = texteSaisi.toLowerCase();
                
                // --- CREATION DE L'AFFICHAGE DU TAG ---
                const nouvelElementTag = document.createElement('span');
                nouvelElementTag.className = 'tag-competence';
                nouvelElementTag.innerHTML = `${texteSaisi} <i class="fas fa-times" style="cursor:pointer; margin-left:5px;"></i>`;
                
                // --- LOGIQUE D'AJOUT ---
                const mapCompetencesExiste = typeof competencesExistantes !== 'undefined';
                const competenceEstConnue = mapCompetencesExiste && competencesExistantes[texteSaisiMinuscule] !== undefined;

                if (competenceEstConnue) {
                    // C'est une competence qui existe déjà dans la base de données
                    const idDeLaCompetence = competencesExistantes[texteSaisiMinuscule];
                    
                    // On verifie qu'on ne l'a pas deja ajoutee
                    if (idsSelectionnes.includes(idDeLaCompetence) === false) {
                        idsSelectionnes.push(idDeLaCompetence); // Ajout au tableau
                        champCacheIdsCompetences.value = idsSelectionnes.join(','); // Mise a jour du champ cache
                        conteneurTags.appendChild(nouvelElementTag); // Affichage
                    }
                } else {
                    // C'est une toute nouvelle competence inventee par l'utilisateur
                    if (nouvellesCompetences.includes(texteSaisi) === false) {
                        nouvellesCompetences.push(texteSaisi); // Ajout au tableau
                        champCacheNouvellesCompetences.value = nouvellesCompetences.join(','); // Mise a jour du champ cache
                        conteneurTags.appendChild(nouvelElementTag); // Affichage
                    }
                }

                // On vide le champ de saisie pour la prochaine compétence
                champSaisieCompetence.value = '';

                // --- LOGIQUE DE SUPPRESSION (Quand on clique sur la croix du tag) ---
                const iconeCroix = nouvelElementTag.querySelector('i');
                
                iconeCroix.addEventListener('click', () => {
                    // 1. On retire le tag visuellement de l'ecran
                    nouvelElementTag.remove();
                    
                    // 2. On retire la donnée de nos tableaux
                    if (competenceEstConnue) {
                        const idAsupprimer = competencesExistantes[texteSaisiMinuscule];
                        // On garde tout SAUF l'ID qu'on vient de supprimer
                        idsSelectionnes = idsSelectionnes.filter(id => id !== idAsupprimer);
                        champCacheIdsCompetences.value = idsSelectionnes.join(',');
                    } else {
                        // On garde tout SAUF le nom qu'on vient de supprimer
                        nouvellesCompetences = nouvellesCompetences.filter(nom => nom !== texteSaisi);
                        champCacheNouvellesCompetences.value = nouvellesCompetences.join(',');
                    }
                });
            }
        });
    }
});

// 7. MOT DE PASSE - VISIBILITE (Transformé en fonction fléchée)
const togglePassword = (idDuChamp) => {
    const champMotDePasse = document.getElementById(idDuChamp);
    const iconeOeil = event.target; // L'icone sur laquelle on a clique

    // Si le champ est masque (type="password")
    if (champMotDePasse.type === 'password') {
        champMotDePasse.type = 'text'; // On affiche le texte
        iconeOeil.classList.replace('fa-eye-slash', 'fa-eye'); // On change l'icône
    } else {
        // Sinon, c'est qu'il etait visible, on le masque
        champMotDePasse.type = 'password';
        iconeOeil.classList.replace('fa-eye', 'fa-eye-slash');
    }
};

// 8. FORMULAIRE D'INSCRIPTION (Validation avec Regex)
const formulaireInscription = document.getElementById('registrationForm');

if (formulaireInscription !== null) {
    
    // Logique pour les champs Campus et Promo selon le role
    const selectRole = document.getElementById('role_id');
    
    if (selectRole !== null) {
        selectRole.addEventListener('change', () => {
            const champCampus = document.getElementById('campus_id');
            const champPromo = document.getElementById('promotion_id');
            
            const roleSelectionne = selectRole.value;
            const estAdministrateur = (roleSelectionne === '1');
            
            // Si c'est un admin, les champs ne sont plus obligatoires, sinon ils le sont
            if (champCampus !== null) champCampus.required = !estAdministrateur;
            if (champPromo !== null) champPromo.required = !estAdministrateur;
        });
    }

    // Validation a la soumission du formulaire
    formulaireInscription.addEventListener('submit', (evenement) => {
        const motDePasse = document.getElementById('password').value;
        const confirmationMotDePasse = document.getElementById('confirm_password').value;
        const messageErreur = document.getElementById('flash-message');

        // Notre Regex (Min 8 caractères, 1 Majuscule, 1 Chiffre)
        const regexMotDePasse = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

        // Vérification 1 : Les mots de passe sont-ils identiques ?
        if (motDePasse !== confirmationMotDePasse) {
            evenement.preventDefault(); // On bloque l'envoi du formulaire
            messageErreur.textContent = 'Les mots de passe ne correspondent pas.';
            messageErreur.style.display = 'block';
            messageErreur.scrollIntoView({ behavior: 'smooth' });
            return; // On arrête l'execution ici
        }

        // Vérification 2 : Le mot de passe respecte-t-il la Regex ?
        const motDePasseEstValide = regexMotDePasse.test(motDePasse);

        if (motDePasseEstValide === false) {
            evenement.preventDefault(); // On bloque l'envoi
            messageErreur.textContent = 'Le mot de passe doit contenir au moins 8 caractères, 1 majuscule et 1 chiffre.';
            messageErreur.style.display = 'block';
            messageErreur.scrollIntoView({ behavior: 'smooth' });
        }
    });
}

// 9. FORMULAIRE EDITION UTILISATEUR
const formulaireEdition = document.getElementById('editForm');

if (formulaireEdition !== null) {
    formulaireEdition.addEventListener('submit', (evenement) => {
        const motDePasse = document.getElementById('password').value;
        const confirmationMotDePasse = document.getElementById('confirm_password').value;
        const messageErreur = document.getElementById('flash-message');
        
        const regexMotDePasse = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

        // En edition, le mot de passe peut être vide (si l'utilisateur ne veut pas le changer)
        const lUtilisateurVeutChangerSonMotDePasse = (motDePasse !== '');

        if (lUtilisateurVeutChangerSonMotDePasse) {
            
            // Les deux mots de passe correspondent-ils ?
            if (motDePasse !== confirmationMotDePasse) {
                evenement.preventDefault();
                messageErreur.textContent = 'Les mots de passe ne correspondent pas.';
                messageErreur.style.display = 'block';
                messageErreur.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            // Le nouveau mot de passe est-il valide ?
            if (regexMotDePasse.test(motDePasse) === false) {
                evenement.preventDefault();
                messageErreur.textContent = 'Min. 8 caractères, 1 majuscule, 1 chiffre.';
                messageErreur.style.display = 'block';
                messageErreur.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });
}

// 10. ÉTOILES / NOTE
const texteAvis = document.getElementById('note-label');
const boutonsRadiosEtoiles = document.querySelectorAll('.etoiles input');

if (texteAvis !== null && boutonsRadiosEtoiles.length > 0) {
    
    // Le tableau des mots correspondant à la note (L'index 0 est vide car les notes vont de 1 a 5)
    const motsCorrespondantsAuxNotes = ['', 'Très mauvais', 'Mauvais', 'Moyen', 'Bien', 'Excellent'];

    // Fonction qui met a jour le texte en fonction du chiffre passé (Transformée en fléchée)
    const mettreAJourLeTexteDeLaNote = (valeurNote) => {
        if (valeurNote) {
            const texte = motsCorrespondantsAuxNotes[valeurNote];
            texteAvis.textContent = texte;
        }
    };

    // Au chargement, on verifie si une etoile est deja cochee
    const etoileDejaCochee = document.querySelector('.etoiles input:checked');
    if (etoileDejaCochee !== null) {
        mettreAJourLeTexteDeLaNote(etoileDejaCochee.value);
    }

    // On ecoute le changement sur chaque etoile
    boutonsRadiosEtoiles.forEach((boutonRadio) => {
        boutonRadio.addEventListener('change', () => {
            const noteChoisie = boutonRadio.value;
            mettreAJourLeTexteDeLaNote(noteChoisie);
        });
    });
}

// 11. REDIRECTION AUTOMATIQUE
const declencheurRedirection = document.getElementById('redirect-auto');

if (declencheurRedirection !== null) {
    // setTimeout attend le temps indiqué en millisecondes avant d'executer la fonction
    const tempsAttenteEnMillisecondes = 3000; // 3 secondes

    setTimeout(() => {
        // Redirige le navigateur vers la page de connexion
        window.location.href = '/connexion';
    }, tempsAttenteEnMillisecondes);
}

// 12. FORMULAIRE DE CONTACT
const formulaireContact = document.querySelector('form[action="/contact"]');

if (formulaireContact !== null) {

    const champPrenom = document.getElementById('prenom');
    const champNom = document.getElementById('nom');
    const champEmail = document.getElementById('email');
    const champSujet = document.getElementById('sujet');
    const champMessage = document.getElementById('message');

    // --- NOS EXPRESSIONS REGULIERES (REGEX) ---
    // Accepte les lettres (y compris accentuees), les espaces et les tirets. Minimum 2 caractères.
    const regexNomPrenom = /^[a-zA-ZÀ-ÿ\s'-]{2,}$/;
    
    // Verifie le format classique d'une adresse email (texte @ texte . texte)
    const regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,}$/;

    if (champPrenom !== null) {
        champPrenom.addEventListener('change', () => {
            const valeurSaisie = champPrenom.value.trim();
            if (valeurSaisie !== '') {
                if (regexNomPrenom.test(valeurSaisie) === false) {
                    champPrenom.style.backgroundColor = "#ffe6e6";
                } else {
                    champPrenom.style.backgroundColor = "#eaffe6";
                }
            } else {
                champPrenom.style.backgroundColor = "";
            }
        });
    }

    if (champNom !== null) {
        champNom.addEventListener('change', () => {
            const valeurSaisie = champNom.value.trim();
            if (valeurSaisie !== '') {
                if (regexNomPrenom.test(valeurSaisie) === false) {
                    champNom.style.backgroundColor = "#ffe6e6";
                } else {
                    champNom.style.backgroundColor = "#eaffe6";   
                }
            } else {
                champNom.style.backgroundColor = "";
            }
        });
    }

    if (champEmail !== null) {
        champEmail.addEventListener('change', () => {
            const valeurSaisie = champEmail.value.trim();
            if (valeurSaisie !== '') {
                if (regexEmail.test(valeurSaisie) === false) {
                    champEmail.style.backgroundColor = "#ffe6e6";
                } else {
                    champEmail.style.backgroundColor = "#eaffe6";
                }
            } else {
                champEmail.style.backgroundColor = "";
            }
        });
    }

    if (champSujet !== null) {
        champSujet.addEventListener('change', () => {
            const valeurSaisie = champSujet.value.trim();
            if (valeurSaisie !== '') {
                if (valeurSaisie.length < 3) {
                    champSujet.style.backgroundColor = "#ffe6e6";
                } else {
                    champSujet.style.backgroundColor = "#eaffe6";
                }
            } else {
                champSujet.style.backgroundColor = "";
            }
        });
    }

    if (champMessage !== null) {
        champMessage.addEventListener('change', () => {
            const valeurSaisie = champMessage.value.trim();
            if (valeurSaisie !== '') {
                if (valeurSaisie.length < 10) {
                    champMessage.style.backgroundColor = "#ffe6e6";
                } else {
                    champMessage.style.backgroundColor = "#eaffe6";
                }
            } else {
                champMessage.style.backgroundColor = "";
            }
        });
    }

    formulaireContact.addEventListener('submit', (evenement) => {
        
        // Variables pour suivre l'etat global du formulaire
        let formulaireEstValide = true;
        let messageErreurGlobal = "";

        // Verification 1 : Le prenom
        if (regexNomPrenom.test(champPrenom.value.trim()) === false) {
            formulaireEstValide = false; // Le formulaire n'est plus valide
            messageErreurGlobal += "- Le prénom doit contenir au moins 2 lettres, aucun chiffre et aucun caractère spécial sauf le \"-\".\n";
            //champPrenom.style.borderColor = "red"; // On met la bordure en rouge
        } /*else {
            champPrenom.style.borderColor = ""; // On remet par defaut si c'est bon
        }*/

        // Verification 2 : Le nom
        if (regexNomPrenom.test(champNom.value.trim()) === false) {
            formulaireEstValide = false;
            messageErreurGlobal += "- Le nom doit contenir au moins 2 lettres, aucun chiffre et aucun caractère spécial sauf le \"-\".\n";
            //champNom.style.borderColor = "red";
        } /*else {
            champNom.style.borderColor = "";
        }*/

        // Verification 3 : L'adresse e-mail
        if (regexEmail.test(champEmail.value.trim()) === false) {
            formulaireEstValide = false;
            messageErreurGlobal += "- L'adresse e-mail n'a pas un format valide.\n";
            //champEmail.style.borderColor = "red";
        } /*else {
            champEmail.style.borderColor = "";
        }*/

        // Verification 4 : Le sujet (Min. 3 caracteres)
        if (champSujet.value.trim().length < 3) {
            formulaireEstValide = false;
            messageErreurGlobal += "- Le sujet doit contenir au moins 3 caractères.\n";
            //champSujet.style.borderColor = "red";
        } /*else {
            champSujet.style.borderColor = "";
        }*/

        // Verification 5 : Le message (Min. 10 caractdres)
        if (champMessage.value.trim().length < 10) {
            formulaireEstValide = false;
            messageErreurGlobal += "- Votre message est trop court (minimum 10 caractères).\n";
            //champMessage.style.borderColor = "red";
        } /*else {
            champMessage.style.borderColor = "";
        }*/

        // CONCLUSION
        // Si au moins une de nos conditions n'a pas ete respectee
        if (formulaireEstValide === false) {
            evenement.preventDefault(); // On bloque l'envoi du formulaire au serveur
            
            // On affiche une fenetre d'alerte avec toutes les erreurs accumulees
            alert("Veuillez corriger les erreurs suivantes :\n\n" + messageErreurGlobal);
        }
        // Si tout est vrai, le code ne fait rien et laisse le navigateur envoyer le formulaire naturellement !
    });
}

// 13. FORMULAIRE DE CANDIDATURE (Postuler)
const formulaireCandidature = document.getElementById('candidatureForm');

if (formulaireCandidature !== null) {

    // On récupère tous les champs du formulaire
    const champPrenomC = document.getElementById('prenom');
    const champNomC = document.getElementById('nom');
    const champEmailC = document.getElementById('email');
    const champTelephoneC = document.getElementById('telephone');
    const champCvC = document.getElementById('cv');
    const champLettreC = document.getElementById('lettre_motivation');

    // --- NOS EXPRESSIONS RÉGULIÈRES (REGEX) ---
    const regexNomPrenom = /^[a-zA-ZÀ-ÿ\s'-]{2,}$/;
    const regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,}$/;
    const regexTelephone = /^(0|\+33)[1-9][0-9]{8}$/;

    // --- CHANGEMENT DE COULEUR À LA VOLÉE (Quand on quitte le champ) ---

    // Fonction factorisée pour éviter de répéter le code
    const validerChampVisuellement = (champ, regex) => {
        if (champ !== null) {
            champ.addEventListener('change', () => {
                const valeurSaisie = champ.value.trim();
                if (valeurSaisie !== '') {
                    // Pour le téléphone, on enlève les espaces temporairement pour tester
                    const valeurAtester = (champ.id === 'telephone') ? valeurSaisie.replace(/\s/g, '') : valeurSaisie;
                    
                    if (regex.test(valeurAtester) === false) {
                        champ.style.backgroundColor = "#ffe6e6"; // Rouge
                    } else {
                        champ.style.backgroundColor = "#eaffe6"; // Vert
                        if (champ.id === 'telephone') champ.value = valeurAtester; // On réécrit sans espaces
                    }
                } else {
                    champ.style.backgroundColor = ""; // Remise à zéro si vide
                }
            });
        }
    };

    const cvInfo = document.getElementById('cv-info');

    if (champCvC !== null && cvInfo !== null) {
        champCvC.addEventListener('change', () => {
            if (champCvC.files.length > 0) {
                const fichier = champCvC.files[0];
                const tailleMo = (fichier.size / (1024 * 1024)).toFixed(2); // taille en Mo
                const nomFichier = fichier.name.toLowerCase();
                
                // Vérification extension
                const extensionsValides = ['pdf', 'doc', 'docx'];
                const extensionFichier = nomFichier.split('.').pop();

                // Vérification taille max 5 Mo
                const tailleValide = fichier.size <= 5 * 1024 * 1024;

                if (extensionsValides.includes(extensionFichier) && tailleValide) {
                    champCvC.style.backgroundColor = "#eaffe6"; // vert
                } else {
                    champCvC.style.backgroundColor = "#ffe6e6"; // rouge
                }

                // Affichage nom + taille
                cvInfo.textContent = `${fichier.name} (${tailleMo} Mo)`;
            } else {
                cvInfo.textContent = "";
                champCvC.style.backgroundColor = ""; // reset
            }
        });
    }

    validerChampVisuellement(champPrenomC, regexNomPrenom);
    validerChampVisuellement(champNomC, regexNomPrenom);
    validerChampVisuellement(champEmailC, regexEmail);
    validerChampVisuellement(champTelephoneC, regexTelephone);

    // Contrôle visuel spécifique pour la lettre de motivation (ex: min 50 caractères)
    if (champLettreC !== null) {
        champLettreC.addEventListener('change', () => {
            const valeurSaisie = champLettreC.value.trim();
            if (valeurSaisie !== '') {
                champLettreC.style.backgroundColor = (valeurSaisie.length < 50) ? "#ffe6e6" : "#eaffe6";
            } else {
                champLettreC.style.backgroundColor = "";
            }
        });
    }

    // --- VÉRIFICATION GLOBALE À LA SOUMISSION ---
    formulaireCandidature.addEventListener('submit', (evenement) => {
        
        let formulaireEstValide = true;
        let messageErreurGlobal = "";

        // 1. Prénom
        if (regexNomPrenom.test(champPrenomC.value.trim()) === false) {
            formulaireEstValide = false;
            messageErreurGlobal += "- Le prénom doit contenir au moins 2 lettres et aucun caractère spécial.\n";
            champPrenomC.style.backgroundColor = "#ffe6e6";
        }

        // 2. Nom
        if (regexNomPrenom.test(champNomC.value.trim()) === false) {
            formulaireEstValide = false;
            messageErreurGlobal += "- Le nom doit contenir au moins 2 lettres et aucun caractère spécial.\n";
            champNomC.style.backgroundColor = "#ffe6e6";
        }

        // 3. Email
        if (regexEmail.test(champEmailC.value.trim()) === false) {
            formulaireEstValide = false;
            messageErreurGlobal += "- L'adresse e-mail n'est pas valide.\n";
            champEmailC.style.backgroundColor = "#ffe6e6";
        }

        // 4. Téléphone
        const valeurTelephone = champTelephoneC.value.replace(/\s/g, ''); // Nettoyage des espaces
        if (regexTelephone.test(valeurTelephone) === false) {
            formulaireEstValide = false;
            messageErreurGlobal += "- Le numéro de téléphone doit être français (ex: 0612345678 ou +33612345678).\n";
            champTelephoneC.style.backgroundColor = "#ffe6e6";
        } else {
            champTelephoneC.value = valeurTelephone;
        }

        // 5. CV
        if (champCvC.files.length > 0) {
            const fichier = champCvC.files[0];
            const nomFichier = fichier.name.toLowerCase();
            const tailleValide = fichier.size <= 5 * 1024 * 1024; // 5 Mo
            const extensionValide = ['pdf', 'doc', 'docx'].includes(nomFichier.split('.').pop());

            if (!extensionValide) {
                formulaireEstValide = false;
                messageErreurGlobal += "- Le CV doit être un fichier .pdf, .doc ou .docx.\n";
                champCvC.style.backgroundColor = "#ffe6e6";
            }

            if (!tailleValide) {
                formulaireEstValide = false;
                messageErreurGlobal += "- Le CV doit peser moins de 5 Mo.\n";
                champCvC.style.backgroundColor = "#ffe6e6";
            }
        } else {
            formulaireEstValide = false;
            messageErreurGlobal += "- Vous devez joindre un CV.\n";
            champCvC.style.backgroundColor = "#ffe6e6";
        }

        // 6. Lettre de motivation
        if (champLettreC.value.trim().length < 50) {
            formulaireEstValide = false;
            messageErreurGlobal += "- Votre lettre de motivation est trop courte (minimum 50 caractères).\n";
            champLettreC.style.backgroundColor = "#ffe6e6";
        }

        // --- BLOQUER SI UN CHAMP EST ROUGE ---
        const champs = [champPrenomC, champNomC, champEmailC, champTelephoneC, champCvC, champLettreC];
        const unChampEstRouge = champs.some(champ => champ.style.backgroundColor === "rgb(255, 230, 230)"); // couleur rouge #ffe6e6 en RGB

        if (unChampEstRouge) {
            evenement.preventDefault(); // Bloque l'envoi
            alert("Impossible d'envoyer la candidature. Veuillez corriger les champs en rouge :\n\n" + messageErreurGlobal);
        }
    });
}