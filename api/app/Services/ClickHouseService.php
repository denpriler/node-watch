<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class ClickHouseService
{
    public function __construct(
        private string $host,
        private string $user,
        private string $password,
    ) {}

    /**
     * INSERT rows into a table using JSONEachRow format.
     *
     * @param  array<int, array<string, mixed>>  $rows
     *
     * @throws ConnectionException
     * @throws \JsonException
     */
    public function insert(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $body = implode("\n", array_map(
            fn (array $row) => json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $rows,
        ));

        $response = Http::withBasicAuth($this->user, $this->password)
            ->withHeaders([
                'Content-Type' => 'text/plain',
            ])
            ->withBody($body, 'text/plain')
            ->post("{$this->host}?query=".urlencode("INSERT INTO {$table} FORMAT JSONEachRow"));

        if ($response->failed()) {
            throw new RuntimeException("ClickHouse INSERT failed: {$response->body()}");
        }
    }

    /**
     * Run a SELECT query and return rows.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function select(string $query): array
    {
        $response = Http::withBasicAuth($this->user, $this->password)
            ->get($this->host, ['query' => $query.' FORMAT JSON']);

        if ($response->failed()) {
            throw new RuntimeException("ClickHouse SELECT failed: {$response->body()}");
        }

        return $response->json('data', []);
    }
}
