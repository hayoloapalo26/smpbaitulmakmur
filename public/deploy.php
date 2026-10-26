<?php
// Script auto-deploy triggered by GitHub Webhook

$repoDir = dirname(__DIR__);

if (!is_dir($repoDir)) {
    http_response_code(500);
    die(json_encode(['error' => 'Repository directory not found']));
}

chdir($repoDir);

$output = [];
$returnVar = 0;

// Jalankan git pull
exec('git pull origin main 2>&1', $output, $returnVar);

// Bersihkan cache Laravel agar perubahan langsung terbaca
if ($returnVar === 0 && file_exists($repoDir . '/artisan')) {
    exec('php artisan optimize:clear 2>&1', $output);
}

header('Content-Type: application/json');
echo json_encode([
    'status' => $returnVar === 0 ? 'success' : 'error',
    'timestamp' => date('Y-m-d H:i:s'),
    'output' => $output
], JSON_PRETTY_PRINT);
