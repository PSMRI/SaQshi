<?php
require_once __DIR__ . '/_endpoint.php';
$request = aiJsonRequest(); $context = AIContextService::checkpoint($request);
if ($context['checkpoint_text'] === '' && $context['checkpoint_code'] === '') Response::validation(['checkpoint_text' => 'checkpoint_text or checkpoint_code is required']);
aiRespond('checkpoint_explain', 'v1/checkpoint/explain', $context, (int)($request['assessment_id'] ?? 0));
