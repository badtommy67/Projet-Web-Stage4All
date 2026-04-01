<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\EntrepriseModel;

// cd site/ && ./vendor/bin/phpunit tests/EntrepriseModelTest.php ; cd ..

class EntrepriseModelTest extends TestCase {

    private EntrepriseModel $model;
    private $pdoStub;
    private $stmtStub;

    protected function setUp(): void {
        $this->pdoStub = $this->createStub(\PDO::class);
        $this->stmtStub = $this->createStub(\PDOStatement::class);
        $this->model = new EntrepriseModel($this->pdoStub);
    }

    // TEST : CRÉER UNE ENTREPRISE
    public function testCreerEntrepriseExecuteSansErreur() {
        $this->pdoStub->method('lastInsertId')->willReturn('1');
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);

        $data = [
            'nom' => 'Cesi', 'ville_nom' => 'Strasbourg', 'code_postal' => '67000', 
            'email' => 'contact@cesi.fr', 'description' => 'Ecole', 'secteur_id' => 1
        ];
        
        $this->model->creerEntreprise($data);
        $this->assertTrue(true);
    }

    // TEST : MODIFIER UNE ENTREPRISE
    public function testModifierEntrepriseRetourneTrue() {
        $this->stmtStub->method('fetchColumn')->willReturn(1);
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);

        $data = [
            'nom' => 'Cesi', 'ville_nom' => 'Strasbourg', 'code_postal' => '67000', 
            'email' => 'contact@cesi.fr', 'description' => 'Ecole', 'secteur_id' => 1
        ];

        $resultat = $this->model->modifierEntreprise(1, $data);
        $this->assertTrue($resultat);
    }

    // TEST : SUPPRIMER UNE ENTREPRISE
    public function testSupprimerEntrepriseRetourneTrueSiSucces() {
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);
        $this->pdoStub->method('commit')->willReturn(true);

        $resultat = $this->model->supprimerEntreprise(1);
        $this->assertTrue($resultat);
    }

    public function testSupprimerEntrepriseRetourneFalseSiErreur() {
        $this->pdoStub->method('prepare')->willThrowException(new \PDOException());
        
        $resultat = $this->model->supprimerEntreprise(1);
        $this->assertFalse($resultat);
    }
}