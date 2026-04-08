<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "1. PHP OK\n";

define("FCPATH", __DIR__ . DIRECTORY_SEPARATOR);
echo "2. FCPATH defined\n";

require dirname(__DIR__) . "/app/Config/Paths.php";
echo "3. Paths loaded\n";

$paths = new Config\Paths();
echo "4. Paths instantiated\n";

require $paths->systemDirectory . "/Boot.php";
echo "5. Boot loaded\n";

$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["REQUEST_URI"] = "/";

echo "6. About to boot web...\n";
try {
    CodeIgniter\Boot::bootWeb($paths);
    echo "7. SHOULD NOT SEE THIS\n";
} catch (Throwable $e) {
    echo "\n\n=== ERROR ===\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
}
