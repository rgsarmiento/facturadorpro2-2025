<?php

return [
    'name' => 'Backup',
    'description' => 'Sistema de copias de seguridad y restauración de la base de datos',

    // Configuración de backup
    'backup_path' => storage_path('app/backups'),
    'max_backups' => 10, // Máximo número de backups a mantener
    'timeout' => 300, // Timeout en segundos para operaciones de backup

    // Configuración de mysqldump
    'mysqldump_options' => [
        '--single-transaction',
        '--routines',
        '--triggers',
        '--add-drop-table',
        '--lock-tables=false'
    ],

    // Extensiones de archivo permitidas para restauración
    'allowed_restore_extensions' => ['sql'],
    'max_restore_file_size' => 102400, // 100MB en KB
];
