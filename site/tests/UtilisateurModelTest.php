<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\UtilisateurModel;

// cd site/ && ./vendor/bin/phpunit tests/UtilisateurModelTest.php ; cd ..

class UtilisateurModelTest extends TestCase {

    private UtilisateurModel $model;
    private $pdoStub;
    private $stmtStub;

    protected function setUp(): void {
        $this->pdoStub = $this->createStub(\PDO::class);
        $this->stmtStub = $this->createStub(\PDOStatement::class);
        $this->model = new UtilisateurModel($this->pdoStub);
    }

    // TEST : CRÉER UN UTILISATEUR
    public function testCreerUtilisateurExecuteSansErreur() {
        $this->pdoStub->method('lastInsertId')->willReturn('10');
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);

        $data = [
            'prenom' => 'Alice', 'nom' => 'Merveille', 'email' => 'alice@cesi.fr',
            'password' => 'motdepasse', 'campus_id' => 1, 'promotion_id' => 2
        ];

        $this->model->creerUtilisateur($data, 3);
        $this->assertTrue(true);
    }

    // TEST : MODIFIER UN UTILISATEUR
    public function testModifierUtilisateurExecuteSansErreur() {
        $this->stmtStub->method('fetch')->willReturn(['utilisateur_id' => 10]);
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);

        $data = [
            'prenom' => 'Bob', 'nom' => 'Lennon', 'email' => 'bob@cesi.fr',
            'password' => 'nouveaumdp', 'role_id' => 2, 'campus_id' => 1, 'promotion_id' => 1
        ];

        $this->model->modifierUtilisateur(10, $data);
        $this->assertTrue(true);
    }

    // TEST : SUPPRIMER UN UTILISATEUR
    public function testSupprimerUtilisateurRetourneTrueSiSucces() {
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);
        $this->pdoStub->method('commit')->willReturn(true);

        $resultat = $this->model->supprimerUtilisateur(5);
        $this->assertTrue($resultat);
    }

    public function testSupprimerUtilisateurRetourneFalseSiErreur() {
        $this->pdoStub->method('prepare')->willThrowException(new \PDOException());

        $resultat = $this->model->supprimerUtilisateur(5);
        $this->assertFalse($resultat);
    }
}