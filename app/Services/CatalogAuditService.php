<?php

namespace App\Services;

use App\Models\CatalogAudit;
use Illuminate\Http\Request;

class CatalogAuditService
{
    public function record(
        Request $request,
        string $action,
        string $entityType,
        int $entityId,
        ?array $before,
        ?array $after,
        ?string $description = null,
    ): void {
        CatalogAudit::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $this->safeUserAgent($request),
        ]);
    }

    private function safeUserAgent(Request $request): ?string
    {
        $value = $request->userAgent();

        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 1000);
    }
}
