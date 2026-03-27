<?php
require_once 'includes/config.php';

try {
    $db = getDB();

    // Read and execute migration SQL
    $sql = file_get_contents(__DIR__ . '/database_migration_operational_dates.sql');

    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $db->beginTransaction();

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 100) . "...\n";
            $db->exec($statement);
        }
    }

    $db->commit();

    echo "\n✅ Migration completed successfully!\n";
    echo "- Table 'closed_dates' created\n";
    echo "- Setting 'operation_start_date' added with value '2026-02-02'\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
