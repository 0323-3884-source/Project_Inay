<?php

namespace Tests\Feature;

use App\Http\Middleware\OfflineResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class OfflineResponseTest extends TestCase
{
    private function responseFor(?string $role, ?int $id, int $status = 200)
    {
        $request = Request::create('/mother/dashboard');
        $session = app('session')->driver();
        $session->put(['auth_role' => $role, 'auth_id' => $id]);
        $request->setLaravelSession($session);

        return (new OfflineResponse)->handle($request, fn () => response('page', $status));
    }

    public function test_successful_portal_pages_have_distinct_opaque_account_keys(): void
    {
        $first = $this->responseFor('mother', 1)->headers->get('X-INAY-Offline-User');
        $second = $this->responseFor('mother', 2)->headers->get('X-INAY-Offline-User');
        $staff = $this->responseFor('staff', 1)->headers->get('X-INAY-Offline-User');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first);
        $this->assertNotSame($first, $second);
        $this->assertNotSame($first, $staff);
    }

    public function test_guest_redirect_and_error_responses_are_not_marked_for_offline_storage(): void
    {
        foreach ([[null, null, 200], ['mother', 1, 302], ['mother', 1, 403], ['mother', 1, 500], ['admin', 1, 200]] as [$role, $id, $status]) {
            $this->assertFalse($this->responseFor($role, $id, $status)->headers->has('X-INAY-Offline-User'));
        }
    }
}
