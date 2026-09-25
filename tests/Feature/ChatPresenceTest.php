<?php

namespace Tests\Feature;

use App\Models\ProgramStaff;
use App\Support\ChatPresence;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatPresenceTest extends TestCase
{
    public function test_local_demo_presence_expires_and_only_applies_to_selected_staff(): void
    {
        $this->app->instance('env', 'local');
        $staff = new ProgramStaff;
        $staff->id = 42;
        $staff->updated_at = now()->subDay();
        Cache::put('local-chat-online:program-staff:42', true, now()->addDay());

        $this->assertTrue(ChatPresence::isOnline($staff));
        $other = clone $staff;
        $other->id = 43;
        $this->assertFalse(ChatPresence::isOnline($other));
        $this->travel(25)->hours();
        $this->assertFalse(ChatPresence::isOnline($staff));
    }

    public function test_demo_override_is_ignored_outside_local_environment(): void
    {
        $staff = new ProgramStaff;
        $staff->id = 42;
        $staff->updated_at = now()->subDay();
        Cache::put('local-chat-online:program-staff:42', true, now()->addDay());

        $this->assertFalse(ChatPresence::isOnline($staff));
        $staff->updated_at = now();
        $this->assertTrue(ChatPresence::isOnline($staff));
        $this->assertFalse(ChatPresence::isOnline(null));
    }
}
