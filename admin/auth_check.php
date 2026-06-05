<?php
/**
 * Shared auth check for admin sub-sections (e.g. Statistici, API).
 * Provides: is_authenticated(), is_owner_auth(), csrf_token(), verify_csrf()
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/settings.php';
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/todos.php';
