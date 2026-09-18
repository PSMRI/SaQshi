<?php
require_once __DIR__ . '/_endpoint.php';
$request = aiJsonRequest();
$question = AIContextService::text($request['question'] ?? '', 4000);
if ($question === '') Response::validation(['question' => 'question is required']);
$context = AIContextService::checkpoint($request); $context['question'] = $question;
$context['search_mode'] = in_array(strtolower((string)($request['search_mode'] ?? 'auto')), ['local', 'web', 'auto'], true) ? strtolower((string)($request['search_mode'] ?? 'auto')) : 'auto';
aiRespond('ask', 'v1/ask', $context, (int)($request['assessment_id'] ?? 0));
