<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\OffreModel;

// cd site/ && ./vendor/bin/phpunit tests/OffreModelTest.php ; cd ..

class OffreModelTest extends TestCase {
    
    private OffreModel $model;
    private $pdoStub;
    private $stmtStub;

    protected function setUp(): void {
        $this->pdoStub = $this->createStub(\PDO::class);
        $this->stmtStub = $this->createStub(\PDOStatement::class);
        $this->model = new OffreModel($this->pdoStub);
    }

    // TEST : CRÉER UNE OFFRE
    public function testCreerOffreExecuteSansErreur() {
        $this->pdoStub->method('lastInsertId')->willReturn('1');
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);

        $data = [
            'titre' => 'Dev C++', 'reference' => 'REF123', 'description' => 'Faire du C++',
            'profil' => 'Junior', 'date_debut' => '2026-05-01', 'duree' => 6, 
            'entreprise_id' => 1, 'ville_nom' => 'Lyon', 'code_postal' => '69000'
        ];

        $this->model->creerOffre($data);
        $this->assertTrue(true);
    }

    // TEST : MODIFIER UNE OFFRE
    public function testModifierOffreExecuteSansErreur() {
        $this->stmtStub->method('fetchColumn')->willReturn('1');
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);

        $data = [
            'titre' => 'Dev C++', 'description' => 'Faire du C++', 'profil' => 'Junior', 
            'date_debut' => '2026-05-01', 'duree' => 6, 'entreprise_id' => 1, 
            'ville_nom' => 'Lyon', 'code_postal' => '69000'
        ];

        $this->model->modifierOffre(1, $data);
        $this->assertTrue(true);
    }

    // TEST : SUPPRIMER UNE OFFRE
    public function testSupprimerOffreRetourneTrueSiSucces() {
        $this->stmtStub->method('execute')->willReturn(true);
        $this->pdoStub->method('prepare')->willReturn($this->stmtStub);
        $this->pdoStub->method('commit')->willReturn(true);

        $resultat = $this->model->supprimerOffre(99);
        $this->assertTrue($resultat);
    }

    public function testSupprimerOffreRetourneFalseSiErreur() {
        $this->pdoStub->method('prepare')->willThrowException(new \PDOException());

        $resultat = $this->model->supprimerOffre(1);
        $this->assertFalse($resultat);
    }
}