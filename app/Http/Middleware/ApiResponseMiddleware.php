<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof Response && $request->is('api/*')) {
            $content = json_decode($response->getContent(), true) ?? [];
            
            // Add status if not present
            if (!isset($content['status'])) {
                $statusCode = $response->getStatusCode();
                $content['status'] = $statusCode >= 200 && $statusCode < 300 ? 'success' : 'error';
            }

            // Add message if not present
            if (!isset($content['message'])) {
                $content['message'] = $this->getDefaultMessage($response->getStatusCode());
            }

            // Ensure consistent response structure
            $formattedContent = [
                'status' => $content['status'],
                'message' => $content['message']
            ];

            // Add data or errors if present
            if (isset($content['data'])) {
                $formattedContent['data'] = $content['data'];
            }
            if (isset($content['errors'])) {
                $formattedContent['errors'] = $content['errors'];
            }

            $response->setContent(json_encode($formattedContent));
        }

        return $response;
    }

    /**
     * Get default message for HTTP status code
     */
    private function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Validation Failed',
            429 => 'Too Many Requests',
            500 => 'Server Error',
            503 => 'Service Unavailable',
            default => 'Unknown Status'
        };
    }
} 