<?php

return [
    // Token de la integración interna de Notion (Settings → Connections → Integrations).
    'token'   => env('NOTION_TOKEN'),
    'version' => env('NOTION_VERSION', '2022-06-28'),

    // IDs de las bases (databases) de Notion del workspace BigStudio.
    'db_tareas'   => env('NOTION_DB_TAREAS', '83f6f29f-b44a-432e-960f-155aaf27610c'),
    'db_clientes' => env('NOTION_DB_CLIENTES', '446283998513438b94e1f9573503d899'),

    // Cache de lecturas (segundos) para no golpear la API en cada carga.
    'cache_ttl' => env('NOTION_CACHE_TTL', 60),
];
