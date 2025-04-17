<?php
// config/bootstrap.php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

function getEntityManager() {
    // Path to entities or metadata
    $paths = [__DIR__ . "/../src/Entity"];

    // Whether to run Doctrine in development mode
    $isDevMode = true;

    // Database configuration parameters
    $dbParams = [
        'driver' => 'pdo_sqlite',
        'memory' => true,  // Use in-memory SQLite
    ];

    // Set up Doctrine configuration
    $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

    // Obtain the entity manager
    return EntityManager::create($dbParams, $config);
}
