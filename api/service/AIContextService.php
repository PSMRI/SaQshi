<?php
/** Builds only explicitly permitted structured context; it does not grant model database access. */
class AIContextService
{
    public static function checkpoint(array $request): array
    {
        return [
            'checkpoint_code' => self::text($request['checkpoint_code'] ?? '', 100),
            'checkpoint_text' => self::text($request['checkpoint_text'] ?? '', 4000),
            'framework' => self::text($request['framework'] ?? '', 100),
            'facility_type' => self::text($request['facility_type'] ?? '', 100),
            'department' => self::text($request['department'] ?? '', 200),
            'language' => self::text($request['language'] ?? 'en', 20)
        ];
    }
    public static function text(mixed $value, int $max): string
    {
        $text = trim((string)$value);
        // Some Windows PHP installations do not enable mbstring.
        return function_exists('mb_substr') ? mb_substr($text, 0, $max) : substr($text, 0, $max);
    }
}
