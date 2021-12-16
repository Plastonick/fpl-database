<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'pgsql',
            'host' => 'localhost',
            'name' => 'postgres',
            'user' => 'postgres',
            'pass' => 'postgres-fpl',
            'port' => '5432',
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'execution'
];
