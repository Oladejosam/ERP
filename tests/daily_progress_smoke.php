<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../app/Models/ProjectModel.php';

if (!class_exists('ProjectModel')) {
    fwrite(STDERR, "Missing ProjectModel\n");
    exit(1);
}

if (!method_exists('ProjectModel', 'getProjectDailyProgress') || !method_exists('ProjectModel', 'addProjectDailyProgress') || !method_exists('ProjectModel', 'updateProjectProgressPercent') || !method_exists('ProjectModel', 'getProjectDelayLogs')) {
    fwrite(STDERR, "Missing project daily progress workflow API\n");
    exit(1);
}

echo "Project daily progress workflow is available.\n";
