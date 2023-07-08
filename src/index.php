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
            (string)$clientData->codePostal,
            (int)$clientData->zone
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
            // Sélectionner un expéditeur
            $senderId = (int) readline("Entrez l'ID de l'expéditeur : ");
            $sender = null;
        
            // Parcourir la liste des client pour trouver un ID correspondant à l'entrée
            // de l'utilisateur.
            foreach ($clients as $client) {
                if ($client->id === $senderId) {
                    $sender = $client;
                    break;
                }
            }
        
            // Si l'expéditeur est inexistant
            if (!$sender) {
                echo "Aucun expéditeur trouvé avec l'ID : " . $senderId . PHP_EOL;
                break;
            }
        
            // Sélectionner un destinataire
            $recipientId = (int) readline("Entrez l'ID du destinataire : ");
            $recipient = null;
        
            // Parcourir la liste des client pour trouver un ID correspondant à la réponse
            // de l'utilisateur.
            foreach ($clients as $client) {
                if ($client->id === $recipientId) {
                    $recipient = $client;
                    break;
                }
            }
        
            // Si le destinataire est inexistant
            if (!$recipient) {
                echo "Aucun destinataire trouvé avec l'ID : " . $recipientId . PHP_EOL;
                break;
            }
        
            // Saisir le nombre de colis et le poids de l'expédition
            $packageNumber = 0;

            // Demander le nombre de colis à l'utilisateur.
            // L'utilisateur ne peux pas rentrer une valeur inférieure ou égale à zéro, ou une valeur vide (null)
            while ($packageNumber <= 0 || empty($packageNumber)) {
                $packageNumber = (int) readline("Entrez le nombre de colis : ");
                if ($packageNumber <= 0 || empty($packageNumber)) {
                    echo "Veuillez entrer un nombre de colis valide (supérieur à zéro)." . PHP_EOL;
                }
            }
        
            $weight = 0;

            // Demander le poids de l'ensemble des colis à l'utilisateur.
            // L'utilisateur ne peux pas rentrer une valeur inférieure ou égale à zéro, ou une valeur vide (null)
            while ($weight <= 0 || empty($weight)) {
                $weight = (float) readline("Entrez le poids de l'expédition : ");
                if ($weight <= 0 || empty($weight)) {
                    echo "Veuillez entrer un poids valide (supérieur à zéro)." . PHP_EOL;
                }
            }
        
            // Charger les fichiers XML contenant les tarifs et les conditions de taxation
            $tarifXml = simplexml_load_file('data/tarif.xml');
            $conditionTaxationXml = simplexml_load_file('data/conditiontaxation.xml');
        
            // Rechercher le tarif correspondant dans le fichier tarif.xml en utilisant le code département du destinataire et la zone
            $tarif = null;
            $generalTarif = null;
        
            // Recherche du tarif correspondant à la zone et au département du destinataire,
            // et identification du tarif général ou d'un tarif hérité le cas échéant
            foreach ($tarifXml->Response->Object as $object) {
                $tarifData = $object->ObjectTarif;
        
                // Vérifier si le tarif correspond à la zone et au département du destinataire
                if ($tarifData->codeDepartement == $recipient->codePostal && $tarifData->zone == $recipient->zone) {
                    $tarif = $tarifData;
                    break;
                }
        
                // Vérifier si c'est le tarif général ou un tarif hérité
                if ($tarifData->idClient == 0) {
                    $generalTarif = $tarifData;
                }
            }
        
            // Si le tarif n'est pas trouvé pour la zone spécifique, 
            // utiliser le tarif de la zone précédente (z-1)
            if (!$tarif) {
                $zoneMinusOne = $recipient->zone - 1;
        
                foreach ($tarifXml->Response->Object as $object) {
                    $tarifData = $object->ObjectTarif;
        
                    if ($tarifData->codeDepartement == $recipient->codePostal && $tarifData->zone == $zoneMinusOne) {
                        $tarif = $tarifData;
                        break;
                    }
                }
            }
        
            // Si le tarif n'est toujours pas trouvé, utiliser le tarif général ou un tarif hérité
            if (!$tarif) {
                $tarif = $generalTarif;
            }
        
            // Calculer le montant HT
            $montantHTTarif = $tarif->montant * $packageNumber;
        
            // Rechercher les conditions de taxation correspondantes dans le fichier conditiontaxation.xml
            $conditionTaxation = null;

            // Rechercher les conditions de taxation correspondantes dans 
            // le fichier conditiontaxation.xml en utilisant l'ID du client expéditeur
            foreach ($conditionTaxationXml->Response->Object as $object) {
                $conditionTaxationData = $object->ObjectConditionTaxation;
        
                if ($conditionTaxationData->idClient == $sender->id) {
                    $conditionTaxation = $conditionTaxationData;
                    break;
                }
            }

            // Si le client ne possède pas de condition de taxation, 
            // utiliser les conditions de taxation générales (voir également la classe TaxCondition)
            if (!$conditionTaxation) {
                $conditionTaxation = TaxCondition::getGeneralConditionTaxation();
            }
        
            // Déterminer la taxe à appliquer en fonction de 
            // qui paie le transport (expéditeur ou destinataire)
            $taxe = 0;
            $paieTransport = "";
        
            // Sélectionner qui paie le transport : l'expéditeur ou le destinataire
            // E est Expéditeur, D est le destinataire.
            // Tant que l'utilisateur n'aura pas rentré les valeurs E ou D, on boucle.
            while ($paieTransport !== 'E' && $paieTransport !== 'D') {
                $paieTransport = readline("Qui paie le transport (E pour l'expéditeur ou D pour le destinataire) : ");
                $paieTransport = strtoupper($paieTransport); // Convertir l'entrée en majuscules
        
                if ($paieTransport !== 'E' && $paieTransport !== 'D') {
                    echo "Veuillez entrer une valeur valide (E ou D)." . PHP_EOL;
                }
            }
            
            // Calculer le montant total
            $montantTotal = $montantHTTarif;
        
            // Appliquer la taxe en fonction de qui paie le transport

            // Calcul de la taxe en fonction de la personne qui paie (expéditeur ou destinataire)
            if ($paieTransport === 'E') {
                // Vérifier si les conditions de taxation utilisent la taxe port dû générale
                if ($conditionTaxation->useTaxePortDuGenerale) {
                    // Utiliser la taxe port dû générale
                    $taxe = $conditionTaxationXml->Response->Object[0]->ObjectConditionTaxation->taxePortDu;
                } else {
                    // Utiliser la taxe spécifique au client expéditeur
                    $taxe = $conditionTaxation->taxePortDu;
                }

                // Le calcul final
                /*Le calcul ($montantHTTarif * $taxe / 100) applique la taxe au montant hors taxe du tarif de transport. 
                Il multiplie le montant hors taxe par le pourcentage de taxe, puis divise le résultat par 100 pour obtenir 
                la valeur de la taxe à ajouter. Ensuite, cette taxe est ajoutée au montant total de la transaction ($montantTotal) 
                pour obtenir le montant total final incluant la taxe. */
                $montantTotal += ($montantHTTarif * $taxe / 100);

            } elseif ($paieTransport === 'D') {
                // Vérifier si les conditions de taxation utilisent la taxe port dû générale
                if ($conditionTaxation->useTaxePortPayeGenerale) {
                    // Utiliser la taxe port dû générale
                    $taxe = $conditionTaxationXml->Response->Object[0]->ObjectConditionTaxation->taxePortPaye;
                } else {
                    $taxe = $conditionTaxation->taxePortDu;
                    $taxe = $conditionTaxation->taxePortPaye;
                }

                // Le calcul final
                /*Le calcul ($montantHTTarif * $taxe / 100) applique la taxe au montant hors taxe du tarif de transport. 
                Il multiplie le montant hors taxe par le pourcentage de taxe, puis divise le résultat par 100 pour obtenir 
                la valeur de la taxe à ajouter. Ensuite, cette taxe est ajoutée au montant total de la transaction ($montantTotal) 
                pour obtenir le montant total final incluant la taxe. */
                $montantTotal += ($montantHTTarif * $taxe / 100);
            }
        
            // Afficher les différents résultats.
            echo PHP_EOL;
            echo "Voici les résultats :\n" .
                "Opération effectuée : " . $montantTotal . " = ". $montantHTTarif . " + ( ". $montantHTTarif. " * " . $taxe . " / " . 100 . " )".  PHP_EOL .
                "Expéditeur : " . $sender->raisonSociale . " (" . $sender->ville . ")\n" .
                "Destinataire : " . $recipient->raisonSociale . " (" . $recipient->ville . ")\n" .
                "Nombre de colis : " . $packageNumber . "\n" .
                "Poids de l'expédition : " . $weight . "\n" .
                "Montant HT (tarif) : " . $montantHTTarif . "\n" .
                "Taxe à appliquer : " . $taxe . "%\n" .
                "Montant total : " . $montantTotal . "\n" .
                PHP_EOL . PHP_EOL
                ;

            break;

        case '4':
            // Quitter le programme
            echo 'À bientôt !'. PHP_EOL .'Vous pouvez relancer le programme avec la commande: [php index.php]';
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
