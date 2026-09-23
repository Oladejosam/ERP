<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../app/Models/ProjectModel.php';

if (!class_exists('ProjectModel')) {
    fwrite(STDERR, "Missing ProjectModel\n");
    exit(1);
}

if (!method_exists('ProjectModel', 'getProjectRFIs') || !method_exists('ProjectModel', 'addProjectRfi') || !method_exists('ProjectModel', 'updateProjectRfiStatus')) {
    fwrite(STDERR, "Missing project RFI workflow API\n");
    exit(1);
}

echo "Project RFI workflow is available.\n";
