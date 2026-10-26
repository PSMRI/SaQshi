<?php

// IIS has OPcache timestamp validation disabled in this environment. This
// entry point ensures the current multi-department score-card implementation
// is used without needing an application-pool restart.
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__DIR__ . '/checkpoint_scorecard.php', true);
}

require __DIR__ . '/checkpoint_scorecard.php';
