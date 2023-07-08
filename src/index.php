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

    // Rechercher les objets Client présents dans le fichier XML
    foreach ($clientXml -> Response -> Object -> ObjectClient as $clientData) 
    {
        $client = new Client(
            // On cast l'ID et la zone en INT sachant qu'ils sont en string
            (int)$clientData -> idClient,
            $clientData -> raisonSociale,
            $clientData -> ville,
            $clientData -> codePostal,

            (int)$clientData -> zone
        );

        $clients[] = $client;

    }

    // Demander à l'utilisateur ce qu'il veut faire
    echo "Que souhaitez-vous faire ? (Tapez le numéro correspondant puis la touche ENTER)" . PHP_EOL;
    echo "[1] Afficher la liste des clients." . PHP_EOL;
    echo "[2] Rechercher un client avec son ID." . PHP_EOL;
    echo "[3] Calculer la taxe." . PHP_EOL;
    echo "[4] Quitter le programme." . PHP_EOL;

    $choice = readline("Entrez votre choix : ");

    switch ($choice) 
    {


        case '1':
            // Afficher la liste des clients

            echo "Liste des clients :" . PHP_EOL;

            foreach ($clients as $client) 
            {

                $client -> afficher();

            }
            break;

        case '2':
            // Rechercher un client par son ID

            $clientId = (int)readline("Entrez l'ID du client : ");

            $foundClient = null;

            foreach ($clients as $client) 
            {
                if ($client -> id === (int)$clientId) {
                    $foundClient = $client;
                    break;
                }
            }
            
            // Si le client est trouvé, on l'affiche sinon on retourne une erreur.
            if ($foundClient) 
            {
                echo "Client trouvé :" . PHP_EOL;

                $foundClient -> afficher();
            } else 
            {
                echo "Aucun client trouvé avec l'ID : " . $clientId . PHP_EOL;
            }

            break;

        // Calculer le prix HT et afficher le détail du calcul
        case '3':
            // Sélectionner un expéditeur avec un ID
            $senderId = (int) readline("Entrez l'ID de l'expéditeur : ");

            $sender = null;
        
            // Parcourir la liste des client pour trouver un ID correspondant à l'entrée utilisateur.
            foreach ($clients as $client) 
            {
                if ($client -> id === $senderId) {
                    $sender = $client;
                    break;
                }
            }
        
            // Si l'expéditeur n'existe pas
            if (!$sender) 
            {
                echo "Aucun expéditeur trouvé avec l'ID : " . $senderId . PHP_EOL;
                break;
            }
        
            // Sélectionner un destinataire avec un ID
            $recipientId = (int) readline("Entrez l'ID du destinataire : ");

            $recipient = null;
        
            // Parcourir la liste des clients pour trouver un ID correspondant à l'entrée utilisateur.
            foreach ($clients as $client) 
            {
                if ($client -> id === $recipientId) {

                    $recipient = $client;

                    break;
                    
                }
            }
        
            // Si le destinataire n'existe pas
            if (!$recipient) 
            {
                echo "Aucun destinataire trouvé avec l'ID : " . $recipientId . PHP_EOL;

                break;
            }
        
            // Entrer nombre de colis et poids de l'expédition
            $packageNumber = 0;

            // Demander nombre de colis.
            // L'utilisateur ne peux pas rentrer une valeur inférieure ou égale à zéro, ou une valeur vide (null)
            while ($packageNumber <= 0 || empty($packageNumber)) 
            {
                $packageNumber = (int) readline("Entrez le nombre de colis : ");

                if ($packageNumber <= 0 || empty($packageNumber)) {

                    echo "Veuillez entrer un nombre de colis valide (supérieur à zéro)." . PHP_EOL;
                }

            }
        
            $weight = 0;

            // Demander le poids de l'ensemble des colis à l'utilisateur.
            // L'utilisateur ne peux pas rentrer une valeur inférieure ou égale à zéro, ou une valeur vide (null)
            while ($weight <= 0 || empty($weight)) 
            {

                $weight = readline("Entrez le poids de l'expédition : ");

                if ($weight <= 0 || empty($weight)) {
                    
                    echo "Veuillez entrer un poids valide (supérieur à zéro)." . PHP_EOL;
                }
            }
        
            // Charger les fichiers XML contenant les tarifs et conditions de taxation
            $tarifXml = simplexml_load_file('data/tarif.xml');

            $taxConditionXml = simplexml_load_file('data/conditiontaxation.xml');
        
            // Rechercher tarif correspondant en utilisant le code postal et la zone
            $tarif = null;

            $generalTarif = null;
        
            // Recherche du tarif correspondant au code postal et la zone du destinataire
            foreach ($tarifXml -> Response -> Object as $object) {
                $tarifData = $object -> ObjectTarif;
        
                // Vérifier si le tarif correspond à la zone et au code postal du destinataire
                if ($tarifData -> codeDepartement == $recipient -> codePostal && $tarifData -> zone == $recipient -> zone) {
                    $tarif = $tarifData;
                    break;
                }
        
                // Vérifie si c'est le tarif général ou un tarif hérité
                if ($tarifData -> idClient == 0) {
                    $generalTarif = $tarifData;
                }
            }
        
            // Si tarif n'est pas trouvé pour la zone spécifique, 
            // utiliser tarif de la zone précédente
            if (!$tarif) {
                $zoneMin = $recipient -> zone - 1;
        
                foreach ($tarifXml -> Response -> Object as $object) {
                    $tarifData = $object -> ObjectTarif;
        
                    if ($tarifData -> codeDepartement == $recipient -> codePostal && $tarifData -> zone == $zoneMin) {
                        $tarif = $tarifData;
                        break;
                    }
                }
            }
        
            // Si le tarif est toujours introuvable, utiliser le tarif général ou un tarif hérité
            if (!$tarif) {
                $tarif = $generalTarif;
            }
        
            // Calculer le montant HT
            $amountHtRat = $tarif  ->  montant * $packageNumber;
        
            // Rechercher les conditions de taxation correspondantes
            $taxCondition = null;

            // Rechercher les conditions de taxation correspondantes en utilisant l'ID du client expéditeur
            foreach ($taxConditionXml -> Response -> Object as $object) 
            {
                $taxConditionData = $object -> ObjectConditionTaxation;
        
                if ($taxConditionData -> idClient == $sender -> id) 
                {
                    $taxCondition = $taxConditionData;
                    break;
                }
            }

            // Si pas de condition de taxation, utiliser conditions de taxation générale
            if (!$taxCondition) 
            {
                $taxCondition = TaxCondition::getGeneralConditionTaxation();
            }
        
            // Déterminer la taxe à appliquer en fonction de qui paie le transport (expéditeur ou destinataire)
            $taxe = 0;
            $payTransport = "";
        
            // Sélectionner qui paie le transport : l'expéditeur ou le destinataire
            // E est Expéditeur, D est le destinataire. Tant que l'utilisateur n'aura pas rentré les valeurs E ou D, on boucle.
            while ($payTransport !== 'E' && $payTransport !== 'D') 
            {
                $payTransport = readline("Qui paie le transport (E pour l'expéditeur ou D pour le destinataire) : ");
                $payTransport = strtoupper($payTransport); // Convertir l'entrée en majuscules
        
                if ($payTransport !== 'E' && $payTransport !== 'D') {
                    echo "Veuillez entrer une valeur valide (E ou D)." . PHP_EOL;
                }
            }
            
            // Calculer le montant total
            $totalAmount = $amountHtRat;
        
            // Appliquer la taxe en fonction de qui paie le transport

            // Calcul de la taxe en fonction de la personne qui paie (expéditeur ou destinataire)
            if ($payTransport === 'E') 
            {
                // Vérifier si les conditions de taxation utilisent la taxe portDu générale
                if ($taxCondition -> useTaxePortDuGenerale) 
                {
                    // Utiliser la taxe portDu générale
                    $taxe = $taxConditionXml -> Response -> Object[0] -> ObjectConditionTaxation -> taxePortDu;
                } else {
                    // Utiliser la taxe spécifique de l'expéditeur
                    $taxe = $taxCondition -> taxePortDu;
                }

                // Le calcul final
                $totalAmount += ($amountHtRat * $taxe / 100);

            } elseif ($payTransport === 'D') 
            {
                // Vérifier si utilise la taxe port dû générale
                if ($taxCondition -> useTaxePortPayeGenerale) 
                {
                    // Utiliser la taxe port dû générale
                    $taxe = $taxConditionXml -> Response -> Object[0] -> ObjectConditionTaxation -> taxePortPaye;
                } else 
                {
                    $taxe = $taxCondition -> taxePortDu;
                    $taxe = $taxCondition -> taxePortPaye;
                }
                
                // Le calcul final
                $totalAmount += ($amountHtRat * $taxe / 100);
            }
        
            // Afficher les différents résultats
            echo PHP_EOL;
            echo "Résultats :".PHP_EOL;
            echo "Opération : " . $totalAmount . " = ". $amountHtRat . " + ( ". $amountHtRat. " * " . $taxe . " / " . 100 . " )".  PHP_EOL;
            echo "Expéditeur : " . $sender -> raisonSociale . " (" . $sender -> ville . ")" . PHP_EOL;
            echo "Destinataire : " . $recipient -> raisonSociale . " (" . $recipient -> ville . ")".PHP_EOL;
            echo "Nombre colis : " . $packageNumber.PHP_EOL;
            echo "Poids : " . $weight.PHP_EOL;
            echo "Montant HT (tarif) : " . $amountHtRat.PHP_EOL;
            echo  "Taxe à appliquer : " . $taxe.PHP_EOL;
            echo "Montant total : " . $totalAmount.PHP_EOL;
            echo PHP_EOL . PHP_EOL;
            echo "⇗⇗ Les résultats sont ici ⇖⇖".PHP_EOL;

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
            break;
    }
}
