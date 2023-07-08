# Exercice transport-besson

Le projet a été développé sous PHP 8.1.10, sur un ordinateur sous Windows 11, effectué en environ 16 heures dispatchées sur
2 jours.

## Prérequis: Avoir PHP d'installé sur son ordinateur (Passez cette étape si c'est déjà fait)

(Linux) Ouvrez votre terminal, puis exécutez: sudo apt-get install php

(Windows) Cliquez ici: <https://windows.php.net/downloads/releases/php-8.1.21-Win32-vs16-x64.zip>.
Une fois cela fait, naviguez dans C:\Program Files puis effectuez l'extraction du fichier .ZIP dans ce même dossier.

Une fois cela fait, tapez dans la barre de recherche "environment var",
allez dans Environment Variables -> System variables (Double clic sur Path) -> Cliquez sur New puis collez C:\Program Files\php-8.1.21

## Mettre le projet en route

1. Ouvrez Visual Studio Code puis ouvrez le dossier du projet (transport-besson-main)

2. Allez sur la roue crantée en bas à gauche, cliquez sur paramètres.
Tapez "php" puis cliquez sur "Modifier dans settings.json" sous PHP > Validate: Executable Path. Vous devrez mettre le path vers l'exécutable PHP. Pour ma part ce sera C:/Program Files/php-8.1.21/php.exe.

2. Une fois dans le projet, naviguez jusqu'au dossier src avec la ligne de commande: cd src

3. Exécutez la commande php index.php pour exécuter le programme.
L'ensemble des instructions à suivre seront indiquées dans le terminal.

## Structure du projet

index.php est le programme principal. C'est le point d'entrée, avec l'ensemble de la logique métier.

Les fichiers XML se trouvent dans le dossier data.

La classe TaxCondition, qui permettent de mettre les tarifs
généraux et les taxes générales.

La classe Client, qui permet de récupérer les clients, d'utiliser leurs informations et d'afficher
notamment leurs informations.
