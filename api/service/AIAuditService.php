<?php
/** Append-only, minimal audit record. Raw prompts and model responses are deliberately not retained. */
class AIAuditService
{
    public static function log(array $record): void
    {
        $safe = [
            'id' => bin2hex(random_bytes(12)), 'user_id' => (int)($record['user_id'] ?? 0),
            'facility_id' => (int)($record['facility_id'] ?? 0), 'assessment_id' => (int)($record['assessment_id'] ?? 0),
            'feature' => (string)($record['feature'] ?? ''), 'model' => (string)($record['model'] ?? ''),
            'request_timestamp' => gmdate('c'), 'duration_ms' => (int)($record['duration_ms'] ?? 0),
            'success' => !empty($record['success']), 'source_count' => (int)($record['source_count'] ?? 0),
            'accepted' => null, 'error_code' => (string)($record['error_code'] ?? '')
        ];
        // Reuse SaQshi's non-blocking, redacting event log. It is safe under
        // IIS and cannot turn an AI response into a workflow failure.
        if (class_exists('Event')) {
            Event::dispatch('ai.usage', $safe);
        }
    }
}
