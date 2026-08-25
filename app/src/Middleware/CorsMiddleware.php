<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Minimal CORS support for the `/api` scope, since CakePHP core ships no
 * CORS middleware of its own.
 *
 * Allowed origins come from `Cors.allowedOrigins` (see config/app.php,
 * env `CORS_ALLOWED_ORIGINS`). Only an exact, allow-listed origin is ever
 * echoed back - never a wildcard - because credentials (the session
 * cookie) are allowed, and browsers refuse wildcard origins together
 * with `Access-Control-Allow-Credentials: true`.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!str_starts_with($request->getUri()->getPath(), '/api/')) {
            return $handler->handle($request);
        }

        $origin = $request->getHeaderLine('Origin');
        $allowedOrigins = (array)Configure::read('Cors.allowedOrigins');
        $isAllowed = $origin !== '' && in_array($origin, $allowedOrigins, true);

        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $response = $response->withStatus(204);

            return $this->withCorsHeaders($response, $origin, $isAllowed);
        }

        $response = $handler->handle($request);

        return $this->withCorsHeaders($response, $origin, $isAllowed);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response The response to decorate.
     * @param string $origin The request's `Origin` header value.
     * @param bool $isAllowed Whether `$origin` is on the allow-list.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function withCorsHeaders(ResponseInterface $response, string $origin, bool $isAllowed): ResponseInterface
    {
        if (!$isAllowed) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
            ->withHeader('Vary', 'Origin');
    }
}
