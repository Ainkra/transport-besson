<?php

class TaxCondition
{
    public static function getGeneralTarif()
    {
        // Charger le fichier tarif.xml
        $tarifXml = simplexml_load_file('data/tarif.xml');

        // Rechercher le tarif général avec idClient = 0
        foreach ($tarifXml->Response->Object as $object) {
            $tarifData = $object->ObjectTarif;
            if ($tarifData->idClient == 0) {
                return $tarifData;
            }
        }

        return null;
    }
    
    public static function getGeneralConditionTaxation()
    {
        // Charger le fichier conditiontaxation.xml
        $conditionTaxationXml = simplexml_load_file('data/conditiontaxation.xml');

        // Rechercher la condition de taxation générale avec idClient = 0
        foreach ($conditionTaxationXml->Response->Object as $object) {
            $conditionTaxationData = $object->ObjectConditionTaxation;
            if ($conditionTaxationData->idClient == 0) {
                return $conditionTaxationData;
            }
        }

        return null;
    }
}

?>
