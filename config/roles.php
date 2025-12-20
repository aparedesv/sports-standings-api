<?php

/**
 * Configuració de rols i els seus permisos.
 *
 * Format: 'entity' => ['action1', 'action2', ...]
 * Exemple: 'leagues' => ['read', 'create', 'update', 'delete']
 */

return [
    // Super admin - accés total (gestionat per Gate::before, no necessita permisos)
    'super_admin' => [],

    // Admin - gestió completa del sistema
    'admin' => [
        'users' => ['read', 'create', 'update', 'delete'],
        'role_permissions' => ['read', 'create', 'update', 'delete'],
        'countries' => ['read', 'create', 'update', 'delete'],
        'leagues' => ['read', 'create', 'update', 'delete'],
        'seasons' => ['read', 'create', 'update', 'delete'],
        'teams' => ['read', 'create', 'update', 'delete'],
        'fixtures' => ['read', 'create', 'update', 'delete'],
        'standings' => ['read', 'create', 'update', 'delete'],
        'settings' => ['read', 'create', 'update', 'delete'],
    ],

    // Editor - pot editar contingut però no gestionar usuaris
    'editor' => [
        'users' => ['read'],
        'countries' => ['read', 'create', 'update'],
        'leagues' => ['read', 'create', 'update'],
        'seasons' => ['read', 'create', 'update'],
        'teams' => ['read', 'create', 'update'],
        'fixtures' => ['read', 'create', 'update'],
        'standings' => ['read', 'create', 'update'],
        'settings' => ['read'],
    ],

    // User - només lectura
    'user' => [
        'countries' => ['read'],
        'leagues' => ['read'],
        'seasons' => ['read'],
        'teams' => ['read'],
        'fixtures' => ['read'],
        'standings' => ['read'],
    ],

    // Guest - accés mínim (només dades públiques)
    'guest' => [
        'leagues' => ['read'],
        'teams' => ['read'],
        'fixtures' => ['read'],
        'standings' => ['read'],
    ],
];
