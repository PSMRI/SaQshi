<?php
class AIRagMetadataService
{
    public static function sources(array $sources): array
    {
        return array_map(static fn($source) => [
            'document_name' => (string)($source['document_name'] ?? ''), 'page' => $source['page'] ?? null,
            'framework' => (string)($source['framework'] ?? ''), 'source_type' => (string)($source['source_type'] ?? '')
        ], array_filter($sources, 'is_array'));
    }
}
