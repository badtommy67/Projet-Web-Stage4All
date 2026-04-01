<?php
// script de la pagination des entreprises et des offres

namespace App\Utilities;

class Pagination {
    private $items;
    private $perPage;
    private $totalItems;
    private $totalPages;
    private $currentPage;

    public function __construct(array $items, int $perPage = 10){
        $this->items=$items;
        $this->perPage = $perPage;
        $this->totalItems = count($items);
        $this->totalPages = (int) ceil($this->totalItems / $this->perPage);

        $this->setPageActuelle();
    }

    private function setPageActuelle() {
        if (isset($_GET['page'])) {
            $page=$_GET['page'];
        } else {
            $page=1;
        }
        // si nb negatif ou 0, alors on passe sur la page 1
        if ($page < 1) {
            $page = 1;
        } 
        // si la page demandee est superieure au nombre total de pages
        elseif ($page > $this->totalPages && $this->totalPages > 0) {
            $page = $this->totalPages;
        }
        
        $this->currentPage = $page;
    }

    public function itemsPage(){
        $debut=($this->currentPage-1)*$this->perPage;
        return array_slice($this->items, $debut, $this->perPage);
    }

    public function getCurrentPage() {
        return $this->currentPage;
    }

    public function getTotalPages() {
        return $this->totalPages;
    }
}

?>