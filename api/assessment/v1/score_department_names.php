<?php

// Serve the current score response while this IIS environment has OPcache
// timestamp validation disabled.
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__DIR__ . '/score.php', true);
}

require __DIR__ . '/score.php';
