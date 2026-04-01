<?php
namespace App\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ContactController extends Controller {
    public function afficher() {

        // Vérifie si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $prenom_expediteur = '';
            if (isset($_POST['prenom'])) {
                $prenom_expediteur = htmlspecialchars(trim($_POST['prenom']));
            }

            $nom_expediteur = '';
            if (isset($_POST['nom'])) {
                $nom_expediteur = htmlspecialchars(trim($_POST['nom']));
            }

            $adresse_email = '';
            if (isset($_POST['email'])) {
                $adresse_email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL); // enleve tous les caractères qui ne sont pas autorises dans une adresse email selon les standards.
            }

            $sujet_message = '';
            if (isset($_POST['sujet'])) {
                $sujet_message = htmlspecialchars(trim($_POST['sujet']));
            }

            $contenu_message = '';
            if (isset($_POST['message'])) {
                $contenu_message = htmlspecialchars(trim($_POST['message']));
            }

            // verification des champs obligatoires
            if (empty($prenom_expediteur) || empty($nom_expediteur) || empty($adresse_email) || empty($sujet_message) || empty($contenu_message)) {
                $_SESSION['flash'] = "Erreur : Tous les champs sont obligatoires.";
                header('Location: /contact');
                exit;
            }

            // FILTER_VALIDATE_EMAIL sert à verifier si une adresse email est valide selon le format standard.
            if (!filter_var($adresse_email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash'] = "Erreur : L'adresse e-mail n'est pas valide.";
                header('Location: /contact');
                exit;
            }

            $courriel = new PHPMailer(true);

            /*
            Ce bloc configure PHPMailer, definit qui envoie et reçoit le mail, et construit le message en HTML pour que l’administrateur du site reçoive un
            email lisible et bien presente avec toutes les informations du formulaire.
            */
            try {
                $courriel->isSMTP();
                $courriel->Host = $_ENV['SMTP_HOST']; 
                $courriel->SMTPAuth = true;
                $courriel->Username = $_ENV['SMTP_USER'];
                $courriel->Password = $_ENV['SMTP_PASS'];
                $courriel->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $courriel->Port = $_ENV['SMTP_PORT'];
                $courriel->CharSet = 'UTF-8';

                $courriel->setFrom($_ENV['SMTP_USER'], 'Stage4All - Contact');
                $courriel->addAddress($_ENV['SMTP_USER']); 
                $courriel->addReplyTo($adresse_email, $prenom_expediteur . ' ' . $nom_expediteur);

                $courriel->isHTML(true);
                $courriel->Subject = "[Contact Stage4All] " . $sujet_message;
                $courriel->Body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <h2 style='color: #5a189a;'>Nouveau message depuis le site Stage4All</h2>
                        <p><strong>De :</strong> {$prenom_expediteur} {$nom_expediteur}</p>
                        <p><strong>Email :</strong> {$adresse_email}</p>
                        <p><strong>Sujet :</strong> {$sujet_message}</p>
                        <hr>
                        <p><strong>Message :</strong></p>
                        <p style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>" . nl2br($contenu_message) . "</p>
                    </div>
                ";

                $courriel->send();
                $_SESSION['flash'] = "Votre message a été envoyé avec succès !";

            } catch (Exception $exception_courriel) {
                $_SESSION['flash'] = "Erreur lors de l'envoi : " . $exception_courriel->getMessage();
            }

            header('Location: /contact');
            exit;
        }

        // Affiche toujours la page, que ce soit GET ou après POST (redirection)
        echo $this->twig->render('contact.twig.html');
    }
}