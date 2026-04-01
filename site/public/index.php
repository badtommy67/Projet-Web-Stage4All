<?php
session_start();

if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $twig_debug = true;
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    $twig_debug = false;
}

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Controllers\MainController;
use App\Controllers\EntrepriseController;
use App\Controllers\OffreController;
use App\Controllers\UtilisateurController;
use App\Controllers\WishlistController;
use App\Controllers\EvaluationController;
use App\Controllers\CandidatureController;
use App\Controllers\StatistiqueController;
use App\Controllers\ContactController;

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader, [
    'debug' => $twig_debug,
]);

$twig->addGlobal('ASSETS_URL', 'https://stage4all-static.local');

require_once __DIR__ . '/../config/db.php';

if (isset($pdo)) {
    $twig->addGlobal('is_connected', true);
    $twig->addGlobal('dbname', $dbname);
} else {
    $twig->addGlobal('is_connected', false);
    $twig->addGlobal('dbname', '');
    $pdo = null;
}

$uri = $_GET['uri'] ?? 'home';

$twig->addGlobal('current_uri', $uri);
$twig->addGlobal('session', $_SESSION);
$twig->addGlobal('utilisateur', $_SESSION['utilisateur'] ?? null);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$twig->addGlobal('flash', $flash);

$controller = new MainController($twig, $pdo);
$controller_entreprise = new EntrepriseController($twig, $pdo);
$controller_offre = new OffreController($twig, $pdo);
$controller_utilisateur = new UtilisateurController($twig, $pdo);
$controller_wishlist = new WishlistController($twig, $pdo);
$controller_evaluation = new EvaluationController($twig, $pdo);
$controller_candidature = new CandidatureController($twig, $pdo);
$controller_statistique = new StatistiqueController($twig, $pdo);
$controller_contact = new ContactController($twig, $pdo);

$pages_main = ['home', 'mentions-legales', '404'];
$pages_entreprise = ['entreprises', 'detail-entreprise', 'creer-entreprise', 'modifier-entreprise', 'supprimer-entreprise', 'gestion-entreprises'];
$pages_offre = ['offres', 'detail-offre', 'creer-offre', 'modifier-offre', 'supprimer-offre', 'gestion-offres'];
$pages_utilisateur = ['connexion', 'inscription', 'profil', 'creer-utilisateur', 'modifier-utilisateur', 'supprimer-utilisateur', 'gestion-utilisateurs', 'suivi-eleves', 'deconnexion'];
$pages_wishlist = ['wishlist', 'wishlist-ajouter', 'wishlist-retirer', 'mes-favoris'];
$pages_evaluation = ['evaluer-entreprise', 'traiter-notation'];
$pages_candidature = ['postuler', 'mes-candidatures', 'detail-candidature', 'voir-cv'];
$pages_statistique = ['statistiques'];
$pages_contact = ['contact'];

if (in_array($uri, $pages_main)) {
    $controller->redirection($uri);
} elseif (in_array($uri, $pages_entreprise)) {
    $controller_entreprise->redirection($uri);
} elseif (in_array($uri, $pages_offre)) {
    $controller_offre->redirection($uri);
} elseif (in_array($uri, $pages_utilisateur)) {
    $controller_utilisateur->redirection($uri);
} elseif (in_array($uri, $pages_wishlist)) {
    $controller_wishlist->redirection($uri);
} elseif (in_array($uri, $pages_evaluation)) {
    $controller_evaluation->redirection($uri);
} elseif (in_array($uri, $pages_candidature)) {
    $controller_candidature->redirection($uri);
} elseif (in_array($uri, $pages_statistique)) {
    $controller_statistique->redirection($uri);
} elseif (in_array($uri, $pages_contact)) {
    $controller_contact->afficher(); 
} else {
    $controller->redirection('404');
}