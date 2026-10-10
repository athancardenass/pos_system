<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DirectoryClient
{
    public function rows(string $system, string $path): array
    {
        $url = rtrim((string) config("services.$system.base_url"), '/');
        $token = (string) config("services.$system.token");
        if (! Endpoint::allowed($url, $system) || $token === '') {
            throw new RuntimeException("Configure $system API URL and receiver token first.");
        }
        $rows = [];
        $total = null;
        for ($page = 1; $page <= 100; $page++) {
            $payload = Http::acceptJson()->withToken($token)->connectTimeout(3)->timeout(15)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->get($url.$path, ['page' => $page, 'per_page' => 100])->throw()->json();
            // The team's POS-specific HR feed is an unpaginated snapshot.
            if ($system === 'hrms' && $page === 1 && is_array($payload)
                && ! isset($payload['meta']) && array_key_exists('count', $payload)) {
                if (! is_array($payload['data'] ?? null) || ! is_int($payload['count'])
                    || $payload['count'] < 0 || $payload['count'] > 10000
                    || count($payload['data']) !== $payload['count']) {
                    throw new RuntimeException('Invalid or incomplete HRMS employee snapshot.');
                }
                return array_map(static fn ($employee) => is_array($employee)
                    ? \App\Services\Integration\HR\EmployeeFeedNormalizer::normalize($employee) : $employee,
                    array_values($payload['data']));
            }
            $meta = $payload['meta'] ?? [];
            if (! is_array($payload['data'] ?? null) || ($meta['page'] ?? null) !== $page
                || ! is_int($meta['total'] ?? null) || ! is_int($meta['last_page'] ?? null)
                || $meta['total'] < 0 || $meta['last_page'] < 1 || $meta['last_page'] > 100
                || ($total !== null && $total !== $meta['total'])) {
                throw new RuntimeException("Invalid or changing $system directory; retry synchronization.");
            }
            $total = $meta['total'];
            $rows = array_merge($rows, $payload['data']);
            if ($page === $meta['last_page']) {
                if (count($rows) !== $total) throw new RuntimeException("Incomplete $system directory.");
                return $rows;
            }
        }
        throw new RuntimeException("$system directory exceeds the synchronization limit.");
    }
}
