<?php
/**
 * GitHub Webhook Deploy Script
 * Triggered automatically on every push to main branch
 */

define('WEBHOOK_SECRET', 'clp-deploy-secret-2026');
define('REPO_PATH',      '/home/lsjcloab/repositories/cursurilapahar');
define('DEPLOY_PATH',    '/home/lsjcloab/public_html');
define('BRANCH',         'main');

// Verify GitHub signature
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Forbidden: invalid signature');
}

// Only deploy on push to main
$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/' . BRANCH) {
    die('Ignored: not main branch');
}

// Pull latest code
$output = [];
exec('cd ' . REPO_PATH . ' && git fetch origin && git reset --hard origin/' . BRANCH . ' 2>&1', $output);

// Sync files to public_html (exclude dev files)
$excludes = '--exclude=.git --exclude=.github --exclude=.claude --exclude=.cpanel.yml --exclude=preview.html --exclude=server.py --exclude=deploy.php';
exec('rsync -a ' . $excludes . ' ' . REPO_PATH . '/ ' . DEPLOY_PATH . '/ 2>&1', $output);

http_response_code(200);
echo implode("\n", $output);
