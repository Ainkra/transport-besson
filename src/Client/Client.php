<?php

class Client
{
    public $id;
    public $raisonSociale;
    public $ville;
    public $codePostal;

    public function __construct($id, $raisonSociale, $ville, $codePostal)
    {
        $this->id = $id;
        $this->raisonSociale = $raisonSociale;
        $this->ville = $ville;
        $this->codePostal = $codePostal;
    }

    public function afficher()
    {
        echo "Identifiant du client: " . $this->id . PHP_EOL;
        echo "Raison sociale : " . $this->raisonSociale . PHP_EOL;
        echo "Ville : " . $this->ville . PHP_EOL;
        echo "Code postal : ". $this->codePostal . PHP_EOL;
        echo "---------------------" . PHP_EOL;
    }
}