<?php

require_once 'Client/Client.php';
require_once 'TaxCondition/TaxCondition.php';

$exit = false;

// Permet d'éviter que le programme s'arrête, pour pouvoir effectuer les tâches sans taper la commande.
// Pour que le programme s'arrête complètement, soit faire CTRL+C soit taper 4 au menu.
while (!$exit) {

    // Charger les fichier de données format XML
    $clientXml = simplexml_load_file('data/client.xml');
    $tarifXml = simplexml_load_file('data/tarif.xml');
    $localiteXml = simplexml_load_file('data/localite.xml');
    $taxConditionXml = simplexml_load_file('data/conditionTaxation.xml');

    // Stocker les instances du client
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

    // Demander à l'utilisateur ce qu'il souhaite faire comme tâche.
    // Il peut: Vérifier la liste de ses clients, rechercher un client à partir d'un ID, Calculer la taxe HT d'un transport.
    echo "Que souhaitez-vous faire ? (Tapez le numéro correspondant puis la touche ENTER)" . PHP_EOL;
    echo "[1] Afficher la liste des clients." . PHP_EOL;
    echo "[2] Rechercher un client avec son ID." . PHP_EOL;
    echo "[3] Calculer la taxe." . PHP_EOL;
    echo "[4] Quitter le programme." . PHP_EOL;

    $choice = readline("Entrez votre choix : ");

    // ce switch nous propose le choix entre 4 tâches.
    // Vérifier la liste de ses clients, rechercher un client à partir d'un ID, Calculer la taxe HT d'un transport
    // ou arrêter le programme.
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
            $clientId = (int) readline("Entrez l'ID du client : ");
            $foundClient = null;

            foreach ($clients as $client) {
                if ($client->id === (int)$clientId) {
                    $foundClient = $client;
                    break;
                }
            }
            
            // Si le client est trouvé, on l'affiche, sinon on retourne une erreur.
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
            $sender = $clients[array_rand($clients)];
            $recipient = $clients[array_rand($clients)];

            // Saisir le nombre de colis et le poids de l'expédition
            $packageNumber = 0;

            while ($packageNumber <= 0 || empty($packageNumber)) {
                $packageNumber = (int) readline("Entrez le nombre de colis : ");
                if ($packageNumber <= 0 || empty($packageNumber)) {
                    echo "Veuillez entrer un nombre de colis valide (supérieur à zéro)." . PHP_EOL;
                }
            }
            
            $weight = 0;
            while ($weight <= 0 || empty($weight)) {
                $weight = (float) readline("Entrez le poids de l'expédition : ");
                if ($weight <= 0 || empty($weight)) {
                    echo "Veuillez entrer un poids valide (supérieur à zéro)." . PHP_EOL;
                }
            }

            $recipientPostalCode = '';

            do {
                $recipientPostalCode = readline("Entrez le code postal du destinataire : ");

                if (empty($recipientPostalCode)) {
                    echo "Veuillez entrer un code postal valide." . PHP_EOL;
                } else {
                    // Vérifier si le code postal existe dans les données XML
                    $postalCodeExists = null;
                    
                    foreach ($localiteXml->Response->Object->ObjectLocalite as $object) {
                        $localiteData = $object;
                        
                        if ($localiteData->codePostal == $recipientPostalCode) {
                            $postalCodeExists = true;
                            break;
                        }
                    }
                    
                    if (!$postalCodeExists) {
                        echo "Le code postal saisi n'existe pas. Veuillez réessayer." . PHP_EOL;
                    }
                }
            } while (!$postalCodeExists);

            $recipientCity = '';

            do {
                $recipientCity = readline("Entrez la ville du destinataire : ");
                if (!$recipientCity || empty($recipientCity)) {
                    echo "Veuillez entrer une ville valide." . PHP_EOL;
                } else {
                    // Vérifier si la ville existe dans les données XML
                    $cityExists = false;
                    
                    foreach ($localiteXml->Response->Object->ObjectLocalite as $object) {
                        $localiteData = $object;
                        
                        if ($localiteData->ville == $recipientCity) {
                            $cityExists = true;
                            break;
                        }
                    }
                    
                    if (!$cityExists) {
                        echo "La ville saisie n'existe pas. Veuillez réessayer." . PHP_EOL;
                    }
                }
            } while (!$cityExists);

            $recipientZone = 0;

            do {
                $recipientZone = (int) readline("Entrez la zone du destinataire : ");

                if ($recipientZone <= 0 || empty($recipientZone) || !$recipientZone) {
                    echo "Veuillez entrer une zone valide." . PHP_EOL;
                } else {
                    // Déterminer la zone du destinataire en fonction de ses informations
                    $zone = null;
                    $zoneFound = false;
            
                    foreach ($localiteXml->Response->Object->ObjectLocalite as $object) {
                        $localiteData = $object;
            
                        if ($localiteData->codePostal == $recipientPostalCode && $localiteData->ville == $recipientCity && (int) $localiteData->zone === $recipientZone) {
                            $zone = (int) $localiteData->zone;
                            $zoneFound = true;
                            break;
                        }
                    }
            
                    if (!$zoneFound) {
                        echo "La zone du destinataire n'a pas pu être déterminée pour les informations saisies. Veuillez réessayer." . PHP_EOL;
                    }
                }

            } while (!$zoneFound);

            // Charger les fichiers XML contenant les tarifs et les conditions de taxation
            $tarifXml = simplexml_load_file('data/tarif.xml');
            $conditionTaxationXml = simplexml_load_file('data/conditiontaxation.xml');

            // Rechercher le tarif correspondant dans le fichier tarif.xml en utilisant la zone et les informations sur l'expédition
            $tarif = null;

            foreach ($tarifXml->Response->Object as $object) {
                $tarifData = $object->ObjectTarif;
                if ($tarifData->idClient == $recipient->id && $tarifData->codeDepartement == $recipient->codePostal && $tarifData->zone == $zone) {
                    $tarif = $tarifData;
                    break;
                }
            }

            if (!$tarif) {
                // Si le tarif n'est pas trouvé pour la zone spécifique, utiliser le tarif de la zone précédente (z-1)
                $zoneMinusOne = $zone - 1;

                foreach ($tarifXml->Response->Object as $object) {
                    $tarifData = $object->ObjectTarif;
                    
                    if ($tarifData->idClient == $recipient->id && $tarifData->codeDepartement == $recipient->codePostal && $tarifData->zone == $zoneMinusOne) {
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
            $montantHTTarif = $tarif->montant * $packageNumber;

            // Rechercher les conditions de taxation correspondantes dans le fichier conditiontaxation.xml
            $conditionTaxation = null;

            foreach ($conditionTaxationXml->Response->Object as $object) {
                $conditionTaxationData = $object->ObjectConditionTaxation;

                if ($conditionTaxationData->idClient == $sender->id) {
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

            // Sélectionner qui paie le transport : l'expéditeur ou le destinataire
            $paieTransport = "";
            
            // E est Expéditeur
            while ($paieTransport !== 'E' && $paieTransport !== 'D') {
                $paieTransport = readline("Qui paie le transport (E pour l'expéditeur, D pour le destinataire) : ");
                $paieTransport = strtoupper($paieTransport); // Convertir l'entrée en majuscules
                
                if ($paieTransport !== 'E' && $paieTransport !== 'D') {
                    echo "Veuillez entrer une valeur valide (E ou D)." . PHP_EOL;
                }
            }

            // Calculer le montant total
            $montantTotal = $montantHTTarif + ($montantHTTarif * $taxe / 100);

            // Afficher le détail du calcul
            echo "Détail du calcul :\n" .
            "Expéditeur : " . $sender->raisonSociale . " (" . $sender->ville . ")\n" .
            "Destinataire : " . $recipient->raisonSociale . " (" . $recipient->ville . ")\n" .
            "Nombre de colis : " . $packageNumber . "\n" .
            "Poids de l'expédition : " . $weight . "\n" .
            "Montant HT (tarif) : " . $montantHTTarif . "\n" .
            "Taxe à appliquer : " . $taxe . "%\n" .
            "Montant total : " . $montantTotal . "\n";

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