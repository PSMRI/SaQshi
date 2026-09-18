<?php
require_once __DIR__ . '/_endpoint.php';
$request = aiJsonRequest();
$allowed = ['facility', 'assessment', 'gaps', 'performance', 'certification', 'language']; $payload = [];
foreach ($allowed as $key) if (array_key_exists($key, $request)) $payload[$key] = $request[$key];
if (empty($payload['facility']) || empty($payload['assessment'])) Response::validation(['facility' => 'facility and assessment are required']);
aiRespond('facility_summary', 'v1/facility/summary', $payload, (int)($request['assessment']['assessment_id'] ?? 0));
