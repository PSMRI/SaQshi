<?php
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../service/AIService.php';
require_once __DIR__ . '/../../service/AIContextService.php';
require_once __DIR__ . '/../../service/AIAuditService.php';
require_once __DIR__ . '/../../service/AIRagMetadataService.php';

function aiRespond(string $feature, string $path, array $payload, int $assessmentId = 0): void
{
    $service = new AIService();
    $startedAt = hrtime(true);
    try {
        $result = $service->request($path, $payload);
        if (empty($result['success'])) Response::error((string)$result['message'], ['code' => (string)$result['code']], 503);
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        AIAuditService::log([
            'user_id' => class_exists('SessionManager') ? SessionManager::userId() : 0,
            'facility_id' => class_exists('SessionManager') ? SessionManager::facilityId() : 0,
            'assessment_id' => $assessmentId,
            'feature' => $feature . (!empty($payload['search_mode']) ? ':' . (string)$payload['search_mode'] : ''),
            'model' => (string)($result['model'] ?? $service->model()),
            'duration_ms' => (int)((hrtime(true) - $startedAt) / 1000000),
            'success' => true,
            'source_count' => count($data['sources'] ?? []),
        ]);
        // Keep the browser contract deliberately scalar/array-only.
        Response::success('AI guidance generated', [
            'answer' => (string)($data['answer'] ?? ''),
            'sources' => is_array($data['sources'] ?? null) ? $data['sources'] : [],
            'model' => (string)($result['model'] ?? $service->model()),
            'advisory' => true
        ]);
    } catch (Throwable) {
        Response::error('SaQshi AI is temporarily unavailable. You can continue the assessment normally.', ['code' => 'AI_GATEWAY_ERROR'], 503);
    }
}

function aiJsonRequest(): array { Security::requireMethod('POST'); $data = Security::jsonInput(); if (!is_array($data)) Response::validation(['body' => 'A JSON object is required']); return $data; }
