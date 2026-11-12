<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Service resmi integrasi Fingerspot Developer Cloud API (developer.fingerspot.io).
 *
 * Seluruh endpoint menggunakan Bearer token dan format payload sesuai
 * spesifikasi OpenAPI developer.fingerspot.io/docs/introduction.
 */
class FingerspotCloudService
{
    public const BASE_URL = 'https://developer.fingerspot.io';

    public const MAX_ATTLOG_HISTORY_DAYS = 60;

    public const ATTLOG_STEP_DAYS = 1;

    /**
     * Ambil metadata perangkat dan status aktivitas terakhir dari Cloud API (sinkron).
     */
    public function getDevice(string $apiKey, string $cloudId, ?string $transId = null): array
    {
        return $this->post('/api/get_device', $apiKey, $cloudId, [], $transId, 8);
    }

    /**
     * Ambil log presensi pada rentang tanggal tertentu (sinkron).
     *
     * Sesuai dokumentasi resmi developer.fingerspot.io:
     * - Rentang per permintaan /api/get_attlog maksimal 2 hari.
     * - Riwayat maksimal 60 hari ke belakang.
     * Method ini memecah rentang > 2 hari menjadi beberapa potongan secara otomatis.
     */
    public function getAttlogRange(
        string $apiKey,
        string $cloudId,
        string $startDate,
        string $endDate
    ): array {
        $tz = 'Asia/Jakarta';
        $today = Carbon::now($tz)->startOfDay();
        $oldestAllowed = $today->copy()->subDays(self::MAX_ATTLOG_HISTORY_DAYS);

        $start = Carbon::parse($startDate, $tz)->startOfDay();
        $end = Carbon::parse($endDate, $tz)->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        if ($start->lt($oldestAllowed)) {
            $start = $oldestAllowed->copy();
        }
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $chunks = $this->buildAttlogDateChunks($start, $end);
        $logs = [];
        $errors = [];

        foreach ($chunks as $idx => [$chunkStart, $chunkEnd]) {
            $resp = $this->post(
                '/api/get_attlog',
                $apiKey,
                $cloudId,
                [
                    'start_date' => $chunkStart,
                    'end_date'   => $chunkEnd,
                ],
                'attlog_' . time() . '_' . ($idx + 1),
                12
            );

            if (! $resp['ok']) {
                $errors[] = "{$chunkStart}..{$chunkEnd}: " . ($resp['message'] ?? 'HTTP ' . $resp['status']);
                continue;
            }

            $rows = $resp['data'] ?? [];
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (is_array($row)) {
                        $logs[] = $row;
                    }
                }
            }
        }

        if (empty($logs) && count($errors) === count($chunks)) {
            return [
                'ok'      => false,
                'logs'    => [],
                'chunks'  => count($chunks),
                'message' => implode(' | ', $errors),
            ];
        }

        return [
            'ok'       => true,
            'logs'     => $logs,
            'chunks'   => count($chunks),
            'warnings' => $errors,
            'start'    => $start->toDateString(),
            'end'      => $end->toDateString(),
        ];
    }

    /**
     * Pecah rentang tanggal menjadi jendela <= 2 hari sesuai batas Cloud API.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function buildAttlogDateChunks(Carbon $start, Carbon $end): array
    {
        $chunks = [];
        $cursor = $start->copy()->startOfDay();
        $limit = $end->copy()->startOfDay();

        while ($cursor->lte($limit)) {
            $chunkEnd = $cursor->copy()->addDays(self::ATTLOG_STEP_DAYS);
            if ($chunkEnd->gt($limit)) {
                $chunkEnd = $limit->copy();
            }

            $chunks[] = [$cursor->toDateString(), $chunkEnd->toDateString()];
            $cursor = $chunkEnd->copy()->addDay();
        }

        return $chunks;
    }

    /**
     * Perintah asinkron: ambil daftar semua PIN terdaftar di mesin (callback type: get_userid_list).
     */
    public function getAllPin(string $apiKey, string $cloudId, ?string $transId = null): array
    {
        return $this->post('/api/get_all_pin', $apiKey, $cloudId, [], $transId);
    }

    /**
     * Perintah asinkron: ambil detail user + template biometrik di mesin (callback type: get_userinfo).
     */
    public function getUserInfo(string $apiKey, string $cloudId, string $pin, ?string $transId = null): array
    {
        return $this->post('/api/get_userinfo', $apiKey, $cloudId, [
            'pin' => $pin,
        ], $transId);
    }

    /**
     * Perintah asinkron: kirim/perbarui data user ke mesin (callback type: set_userinfo).
     */
    public function setUserInfo(string $apiKey, string $cloudId, array $userData, ?string $transId = null): array
    {
        return $this->post('/api/set_userinfo', $apiKey, $cloudId, [
            'data' => $userData,
        ], $transId);
    }

    /**
     * Perintah asinkron: hapus user berdasarkan PIN di mesin (callback type: delete_userinfo).
     */
    public function deleteUserInfo(string $apiKey, string $cloudId, string $pin, ?string $transId = null): array
    {
        return $this->post('/api/delete_userinfo', $apiKey, $cloudId, [
            'pin' => $pin,
        ], $transId);
    }

    /**
     * Perintah asinkron: sinkronkan zona waktu mesin, default Asia/Jakarta (callback type: set_time).
     */
    public function setTime(
        string $apiKey,
        string $cloudId,
        string $timezone = 'Asia/Jakarta',
        ?string $transId = null
    ): array {
        return $this->post('/api/set_time', $apiKey, $cloudId, [
            'timezone' => $timezone,
        ], $transId);
    }

    /**
     * Perintah asinkron: restart mesin absensi dari jarak jauh (callback type: restart_device).
     */
    public function restartDevice(string $apiKey, string $cloudId, ?string $transId = null): array
    {
        return $this->post('/api/restart_device', $apiKey, $cloudId, [], $transId);
    }

    /**
     * Perintah asinkron: picu mode registrasi sidik jari online pada mesin (callback type: register_online).
     */
    public function registerOnline(
        string $apiKey,
        string $cloudId,
        string $pin,
        string $verification = '0',
        ?string $transId = null
    ): array {
        return $this->post('/api/reg_online', $apiKey, $cloudId, [
            'pin'          => $pin,
            'verification' => $verification,
        ], $transId);
    }

    /**
     * Eksekutor HTTP POST bersama ke developer.fingerspot.io/api/*.
     */
    public function post(
        string $endpoint,
        string $apiKey,
        string $cloudId,
        array $payload = [],
        ?string $transId = null,
        int $timeout = 10
    ): array {
        $resolvedTransId = $transId ?: ('smpi_' . time() . '_' . random_int(100, 999));
        $body = array_merge([
            'trans_id' => $resolvedTransId,
            'cloud_id' => trim($cloudId),
        ], $payload);

        try {
            $response = Http::withToken(trim($apiKey))
                ->timeout($timeout)
                ->acceptJson()
                ->post(self::BASE_URL . '/' . ltrim($endpoint, '/'), $body);

            $json = $response->json();
            if (! $response->successful()) {
                return [
                    'ok'       => false,
                    'status'   => $response->status(),
                    'trans_id' => $resolvedTransId,
                    'data'     => null,
                    'raw'      => $json ?? $response->body(),
                    'message'  => is_array($json) && ! empty($json['message'])
                        ? (string) $json['message']
                        : 'HTTP ' . $response->status() . ': ' . substr((string) $response->body(), 0, 200),
                ];
            }

            $success = is_array($json) ? (bool) ($json['success'] ?? false) : false;

            return [
                'ok'       => $success,
                'status'   => $response->status(),
                'trans_id' => (string) ($json['trans_id'] ?? $resolvedTransId),
                'data'     => $json['data'] ?? null,
                'raw'      => $json,
                'message'  => $success ? null : (is_array($json) ? ($json['message'] ?? 'Respon API success=false') : 'Respon JSON tidak valid'),
            ];
        } catch (\Throwable $e) {
            return [
                'ok'       => false,
                'status'   => 0,
                'trans_id' => $resolvedTransId,
                'data'     => null,
                'raw'      => null,
                'message'  => $e->getMessage(),
            ];
        }
    }
}
