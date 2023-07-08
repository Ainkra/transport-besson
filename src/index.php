<?php

require_once 'Client/Client.php';
require_once 'TaxCondition/TaxCondition.php';

$exit = false;

// Permet d'éviter que le programme s'arrête, pour pouvoir effectuer plusieurs tâches sans taper la commande.
while (!$exit) {

    // Charger les fichier de données format XML
    $clientXml = simplexml_load_file('data/client.xml');
    $tarifXml = simplexml_load_file('data/tarif.xml');
    $localiteXml = simplexml_load_file('data/localite.xml');
    $taxConditionXml = simplexml_load_file('data/conditionTaxation.xml');

    // Créer un tableau pour stocker les instances de Client
    $clients = [];

    // Parcourir les objets Client du fichier XML
    foreach ($clientXml->Response->Object->ObjectClient as $clientData) {
        $client = new Client(
            (int)$clientData->idClient,
            (string)$clientData->raisonSociale,
            (string)$clientData->ville,
            (string)$clientData->codePostal
        );
        $clients[] = $client;
    }

    // Demander à l'utilisateur ce qu'il souhaite faire
    echo "Que souhaitez-vous faire ? (Tapez le numéro correspondant puis la touche ENTER)" . PHP_EOL;
    echo "[1] Afficher la liste des clients." . PHP_EOL;
    echo "[2] Rechercher un client avec son ID." . PHP_EOL;
    echo "[3] Calculer la taxe." . PHP_EOL;
    echo "[4] Quitter le programme." . PHP_EOL;

    $choice = readline("Entrez votre choix : ");

    // Effectuer l'action correspondante
    switch ($choice) {
        case '1':
            // Afficher la liste des clients
            echo "Liste des clients :" . PHP_EOL;
            foreach ($clients as $client) {
                $client->afficher();
            }

            break;
        case '2':
            // Rechercher un client par son ID
            $clientId = readline("Entrez l'ID du client : ");
            $foundClient = null;
    
            foreach ($clients as $client) {
                if ($client->id === (int)$clientId) {
                    $foundClient = $client;
                    break;
                }
            }
    
            if ($foundClient) {
                echo "Client trouvé :" . PHP_EOL;
                $foundClient->afficher();
            } else {
                echo "Aucun client trouvé avec l'ID : " . $clientId . PHP_EOL;
            }
            break;

        // Calculer le prix HT et afficher le détail du calcul
        case '3':
            // Sélectionner un expéditeur et un destinataire
            $expediteur = $clients[array_rand($clients)];
            $destinataire = $clients[array_rand($clients)];

            // Saisir le nombre de colis et le poids de l'expédition
            $nombreColis = (int) readline("Entrez le nombre de colis : ");
            $poids = (float) readline("Entrez le poids de l'expédition : ");

            // Sélectionner qui paie le transport : l'expéditeur ou le destinataire
            $paieTransport = readline("Qui paie le transport (E pour l'expéditeur, D pour le destinataire) : ");

            // Demander à l'utilisateur de saisir les informations du destinataire
            $destinataireCodePostal = readline("Entrez le code postal du destinataire : ");
            $destinataireVille = readline("Entrez la ville du destinataire : ");
            $destinataireZone = (int) readline("Entrez la zone du destinataire : ");

            // Déterminer la zone du destinataire en fonction de ses informations
            $zone = null;

            foreach ($localiteXml->Response->Object as $object) {
                $localiteData = $object->ObjectLocalite;
                if ($localiteData->codePostal == $destinataireCodePostal && $localiteData->ville == $destinataireVille && (int) $localiteData->zone === $destinataireZone) {
                    $zone = (int) $localiteData->zone;
                    break;
                } else {
                    echo "La zone du destinataire n'a pas pu être déterminée pour les informations saisies. Veuillez réessayer." . PHP_EOL;
                    break;
                }
            }

            // Charger les fichiers XML contenant les tarifs et les conditions de taxation
            $tarifXml = simplexml_load_file('data/tarif.xml');
            $conditionTaxationXml = simplexml_load_file('data/conditiontaxation.xml');

            // Rechercher le tarif correspondant dans le fichier tarif.xml en utilisant la zone et les informations sur l'expédition
            $tarif = null;

            foreach ($tarifXml->Response->Object as $object) {
                $tarifData = $object->ObjectTarif;
                if ($tarifData->idClient == $destinataire->id && $tarifData->codeDepartement == $destinataire->codePostal && $tarifData->zone == $zone) {
                    $tarif = $tarifData;
                    break;
                }
            }

            if (!$tarif) {
                // Si le tarif n'est pas trouvé pour la zone spécifique, utiliser le tarif de la zone précédente (z-1)
                $zoneMinusOne = $zone - 1;

                foreach ($tarifXml->Response->Object as $object) {
                    $tarifData = $object->ObjectTarif;
                    
                    if ($tarifData->idClient == $destinataire->id && $tarifData->codeDepartement == $destinataire->codePostal && $tarifData->zone == $zoneMinusOne) {
                        $tarif = $tarifData;
                        break;
                    }
                }
            }

            if (!$tarif) {
                // Si le client ne possède pas de tarif pour ce département, utiliser le tarif général ou un tarif hérité
                $tarif = TaxCondition::getGeneralTarif();
            }

            // Calculer le montant HT
            $montantHTTarif = $tarif->montant * $nombreColis;

            // Rechercher les conditions de taxation correspondantes dans le fichier conditiontaxation.xml
            $conditionTaxation = null;

            foreach ($conditionTaxationXml->Response->Object as $object) {
                $conditionTaxationData = $object->ObjectConditionTaxation;

                if ($conditionTaxationData->idClient == $expediteur->id) {
                    $conditionTaxation = $conditionTaxationData;
                    break;
                }
            }

            if (!$conditionTaxation) {
                // Si le client ne possède pas de condition de taxation, utiliser les conditions de taxation générales
                $conditionTaxation = TaxCondition::getGeneralConditionTaxation();
            }

            // Déterminer la taxe à appliquer en fonction de qui paie le transport (expéditeur ou destinataire)
            $taxe = 0;

            if ($paieTransport === 'E') {
                if ($conditionTaxation->useTaxePortPayeGenerale === 'true') {
                    $taxe = $conditionTaxation->taxePortPaye;
                }
            } elseif ($paieTransport === 'D') {
                if ($conditionTaxation->useTaxePortDuGenerale === 'true') {
                    $taxe = $conditionTaxation->taxePortDu;
                }
            }

            // Calculer le montant total
            $montantTotal = $montantHTTarif + ($montantHTTarif * $taxe / 100);

            // Afficher le détail du calcul
            echo "Détail du calcul :" . PHP_EOL;
            echo "Expéditeur : " . $expediteur->raisonSociale . " (" . $expediteur->ville . ")" . PHP_EOL;
            echo "Destinataire : " . $destinataire->raisonSociale . " (" . $destinataire->ville . ")" . PHP_EOL;
            echo "Nombre de colis : " . $nombreColis . PHP_EOL;
            echo "Poids de l'expédition : " . $poids . PHP_EOL;
            echo "Montant HT (tarif) : " . $montantHTTarif . PHP_EOL;
            echo "Taxe à appliquer : " . $taxe . "%" . PHP_EOL;
            echo "Montant total : " . $montantTotal . PHP_EOL;

            break;

        case '4':
            // Quitter le programme
            $exit = true;
            break;

        default:
            echo PHP_EOL;
            echo "CHOIX INVALIDE.";
            echo PHP_EOL;
            echo PHP_EOL;
            break;
    }
}