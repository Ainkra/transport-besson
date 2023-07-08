# transport-besson

## Description

  This project is a test for the company Transport Besson. The goal is to develop a program that can calculate the net amount of a transportation cost.

## Prerequisites

Before getting started, make sure you have the following installed on your system:

- PHP (version 8.0 or higher). I personally have the Laragon software that installs it for me directly.
- Composer (for managing PHP dependencies)

## Getting started

1. Clone the project on your computer :

  ```shell
  git clone https://github.com/Ainkra/transport-besson.git
  ```

2. Navigate to the project directory

  ```shell
  cd transport-besson
  ```

3. Install project dependencies using Composer:

  ```shell
  composer install
  ```

## Usage

1. Make sure the required data files are present in the data directory. Check that the client.xml, tariff.xml, locality.xml, and taxation-condition.xml files are present and contain the correct data.

2. Run the program using the following command:

```shell
php index.php
```

This will start the program and display a menu with different options.

3. Follow the program instructions to perform various actions, such as displaying clients, searching for a client by ID, and calculating taxes.
