<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController as BaseAppController;
use Cake\Http\Response;

/**
 * Base controller for the `/api/v1/*` JSON API consumed by the
 * separately-deployed FRONT (see docs/specifications/010_SystemArchitecture.md
 * "ディレクトリとAPI・FRONTの分離"). Every response is JSON; there are no
 * templates or Flash messages here.
 */
abstract class AppController extends BaseAppController
{
    /**
     * @param array<string, mixed> $data Response payload, wrapped under `data`.
     * @param int $status HTTP status code.
     * @return \Cake\Http\Response
     */
    protected function json(array $data, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode(
                ['data' => $data],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));
    }

    /**
     * @param string $code Machine-readable error code (stable, for FRONT to branch on).
     * @param string $message Human-readable (Japanese) message, safe to show directly.
     * @param int $status HTTP status code.
     * @param array<string, mixed> $extra Extra fields merged into the error object (e.g. retryAfter).
     * @return \Cake\Http\Response
     */
    protected function jsonError(string $code, string $message, int $status = 422, array $extra = []): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode(
                ['error' => ['code' => $code, 'message' => $message] + $extra],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));
    }
}
