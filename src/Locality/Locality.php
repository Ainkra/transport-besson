<?php

class Locality
{
    private $codePostal;
    private $ville;
    private $zone;

    public function __construct($codePostal, $ville, $zone)
    {
        $this->codePostal = $codePostal;
        $this->ville = $ville;
        $this->zone = $zone;
    }

    public function getCodePostal()
    {
        return $this->codePostal;
    }

    public function getVille()
    {
        return $this->ville;
    }

    public function getZone()
    {
        return $this->zone;
    }
}

?>
