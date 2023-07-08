<?php

class Rate
{
    private $codeDepartement;
    private $idClient;
    private $idClientHeritage;
    private $montant;
    private $zone;

    public function __construct($codeDepartement, $idClient, $idClientHeritage, $montant, $zone)
    {
        $this->codeDepartement = $codeDepartement;
        $this->idClient = $idClient;
        $this->idClientHeritage = $idClientHeritage;
        $this->montant = $montant;
        $this->zone = $zone;
    }

    public function getCodeDepartement()
    {
        return $this->codeDepartement;
    }

    public function getIdClient()
    {
        return $this->idClient;
    }

    public function getIdClientHeritage()
    {
        return $this->idClientHeritage;
    }

    public function getMontant()
    {
        return $this->montant;
    }

    public function getZone()
    {
        return $this->zone;
    }
}

?>
