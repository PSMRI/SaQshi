<?php
require_once __DIR__ . '/_endpoint.php';
Security::requireMethod('GET');
aiRespond('health', 'health', [], 0);
