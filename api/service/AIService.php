<?php
/** Server-side gateway to the local AI service. Never expose this URL to browsers. */
class AIService
{
    private array $config;

    public function __construct()
    {
        $path = __DIR__ . '/../config/ai/ai.json';
        $config = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
        $this->config = is_array($config) ? $config : [];
    }

    public function enabled(): bool { return !empty($this->config['enabled']); }
    public function model(): string { return (string)($this->config['model'] ?? ''); }

    public function request(string $path, array $payload, string $method = 'POST'): array
    {
        if (!$this->enabled()) return $this->unavailable('AI_DISABLED');
        if (!function_exists('curl_init')) return $this->unavailable('AI_CURL_UNAVAILABLE');
        $base = rtrim((string)($this->config['gateway_url'] ?? ''), '/');
        if ($base === '') return $this->unavailable('AI_GATEWAY_NOT_CONFIGURED');
        $url = $base . '/' . ltrim($path, '/');
        $curl = curl_init($url);
        $timeout = max(1, (int)($this->config['timeout_seconds'] ?? 120));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout, CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        if (strtoupper($method) !== 'GET') curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $body = curl_exec($curl);
        $errno = curl_errno($curl);
        $http = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // CurlHandle cleanup is automatic. curl_close() emits a deprecation
        // warning in PHP 8.5, and SaQshi correctly treats warnings as errors.
        unset($curl);
        if ($errno || $body === false || $http < 200 || $http >= 300) return $this->unavailable($errno === CURLE_OPERATION_TIMEDOUT ? 'AI_GATEWAY_TIMEOUT' : 'AI_SERVICE_UNAVAILABLE');
        $decoded = json_decode((string)$body, true);
        if (!is_array($decoded)) return $this->unavailable('AI_INVALID_RESPONSE');
        if (empty($decoded['success'])) return ['success' => false, 'code' => (string)($decoded['code'] ?? 'AI_SERVICE_ERROR'), 'message' => (string)($decoded['message'] ?? 'SaQshi AI is temporarily unavailable. You can continue the assessment normally.')];
        return ['success' => true, 'data' => $decoded, 'model' => (string)($decoded['model'] ?? $this->model())];
    }

    private function unavailable(string $code): array { return ['success' => false, 'code' => $code, 'message' => 'SaQshi AI is temporarily unavailable. You can continue the assessment normally.']; }
}
