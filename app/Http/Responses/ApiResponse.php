<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;

class ApiResponse implements Responsable
{
    public function __construct(
        protected mixed $data = null,
        protected mixed $errors = null,
        protected string $message = 'success',
        protected int $status = 200,
        protected bool $success = true,
        protected array $meta = [],
    ) {}

    public static function success(
        mixed $data = null,
        string $message = 'success',
        int $status = 200,
    ): static {
        return new static(data: $data, message: $message, status: $status);
    }

    public static function error(
        mixed $errors = null,
        string $message = 'error',
        int $status = 500,
    ): static {
        return new static(errors: $errors, message: $message, status: $status, success: false);
    }

    public function toResponse($request): JsonResponse
    {
        $payload = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        [$resolvedData, $resolvedMeta] = $this->resolve($this->data);

        if ($this->success && $this->data !== null) {
            $payload['data'] = $resolvedData;
        }

        if (! empty($resolvedMeta) || ! empty($this->meta)) {
            $payload['meta'] = array_merge($resolvedMeta, $this->meta);
        }

        if (! $this->success && $this->errors !== null) {
            $payload['errors'] = $this->errors;
        }

        return Response::json($payload, $this->status);
    }

    private function resolve(mixed $data): array
    {
        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $paginator = $data->resource;

            return [
                $data->collection,
                [
                    'currentPage' => $paginator->currentPage(),
                    'lastPage' => $paginator->lastPage(),
                    'perPage' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'hasMore' => $paginator->hasMorePages(),
                ],
            ];
        }

        if ($data instanceof JsonResource) {
            return [$data->resolve(), []];
        }

        return [$data, []];
    }
}
