<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SecurityAudit
{
    public function handle(Request $request, Closure $next)
    {
        $actor = $this->actor($request);
        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $status = $exception instanceof ValidationException ? 422 : ($exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500);
            $this->record($request, $actor, $status);
            throw $exception;
        }
        $this->record($request, $actor, $response->getStatusCode());
        return $response;
    }

    private function actor(Request $request): array
    {
        return $request->session()->get('admin_authenticated') === true
            ? ['role' => 'admin', 'id' => $request->session()->get('admin_id')]
            : ['role' => $request->session()->get('auth_role', 'guest'), 'id' => $request->session()->get('auth_id')];
    }

    private function record(Request $request, array $actor, int $status): void
    {
        $event = null;
        if ($request->isMethod('POST') && $request->routeIs('login.store', 'admin.login.store')) {
            $current = $this->actor($request);
            $success = $status < 400 && $request->attributes->get('login_authenticated') === true;
            $event = $success ? 'login_succeeded' : 'login_failed';
            if ($success) {
                $actor = $current;
                \App\Support\StaffActivity::record($actor['role'], (int) $actor['id'], true);
            }
        } elseif ($request->isMethod('POST') && $request->routeIs('logout', 'admin.logout')) {
            $event = 'logout';
        } elseif ($status === 403) {
            $event = 'access_denied';
        } elseif ($actor['id'] && $status < 400 && ($request->has('export') || $request->routeIs('*.download', '*.pdf', '*.print'))) {
            $event = 'export_or_download_requested';
        } elseif ($actor['id'] && $status < 400 && ! $request->isMethod('GET') && $request->routeIs('admin.program-staff.*', 'admin.dswd-staff.*')) {
            $event = 'staff_administration_requested';
        } elseif ($actor['id'] && $status < 400 && ! $request->isMethod('GET') && $request->routeIs('api.staff.maternal-vitals.*', 'staff.neonatal.*', 'staff.mothers.update', 'staff.f1kd.update', 'dswd.f1kd.update', 'dswd.f1kd.entry.update')) {
            $event = 'clinical_write_requested';
        }
        if ($event) {
            // Never include credentials, submitted email, URL/query, patient data,
            // message text, reset tokens or exception messages in the audit stream.
            $context = ['actor_role' => $actor['role'], 'actor_id' => $actor['id'], 'route' => $request->route()?->getName(), 'method' => $request->method(), 'status' => $status];
            Log::channel('security')->info($event, $context);
            if (in_array($event, ['login_failed', 'access_denied', 'export_or_download_requested'], true)) {
                $identity = $event === 'login_failed' ? hash('sha256', (string) $request->ip()) : $actor['role'].':'.$actor['id'];
                $key = 'security-alert:'.$event.':'.$identity;
                \Illuminate\Support\Facades\Cache::add($key, 0, now()->addMinute());
                $count = \Illuminate\Support\Facades\Cache::increment($key);
                $threshold = $event === 'export_or_download_requested' ? 10 : 5;
                if ($count === $threshold) {
                    Log::channel('security')->warning('security_alert', $context + ['rule' => $event.'_burst', 'count' => $count, 'window_seconds' => 60]);
                }
            }
        }
    }
}
