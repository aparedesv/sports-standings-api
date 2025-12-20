<?php

/**
 * Entitats que tenen permisos assignables.
 * Cada entitat genera 4 permisos: read_{entity}, create_{entity}, update_{entity}, delete_{entity}
 */
define('PERMISSION_ENTITIES', [
    // Users & Auth
    'users',
    'role_permissions',

    // Football entities
    'countries',
    'leagues',
    'seasons',
    'teams',
    'fixtures',
    'standings',

    // Settings
    'settings',
]);

/**
 * Accions CRUD disponibles per cada entitat.
 */
define('PERMISSION_ACTIONS', [
    'read',
    'create',
    'update',
    'delete',
]);
