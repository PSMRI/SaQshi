<?php
require_once __DIR__ . '/_endpoint.php';
$request = aiJsonRequest(); $context = AIContextService::checkpoint($request);
$context['finding'] = AIContextService::text($request['finding'] ?? '', 4000);
$context['current_score'] = is_numeric($request['current_score'] ?? null) ? (float)$request['current_score'] : null;
$context['local_context'] = AIContextService::text($request['local_context'] ?? '', 4000);
if ($context['finding'] === '') Response::validation(['finding' => 'finding is required']);
aiRespond('cqi_suggest_action', 'v1/cqi/suggest-action', $context, (int)($request['assessment_id'] ?? 0));
