<?php

namespace Tests\Feature;

use App\Models\Mother;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HealthServicesDirectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_services_uses_valid_facility_coordinates_for_google_maps_directions(): void
    {
        $mother = Mother::create([
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Reyes',
            'email' => 'health-services-directions@example.test',
            'password' => Hash::make('password123'),
            'barangay' => 'San Rafael',
            'contact_number' => '09170000000',
            'pregnancy_status' => 'pregnant',
            'is_4ps_beneficiary' => false,
        ]);

        $facilities = config('health_facilities.facilities', []);

        $response = $this->withSession([
            'auth_role' => 'mother',
            'auth_id' => $mother->id,
            'auth_name' => $mother->full_name,
            'auth_email' => $mother->email,
        ])->get('/health-services');

        $response
            ->assertOk()
            ->assertSee('Location coordinates are unavailable for this healthcare facility.');

        foreach ($facilities as $facility) {
            $this->assertFacilityHasValidSanPabloCoordinates($facility);

            $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination='
                .$facility['latitude'].','.$facility['longitude']
                .'&travelmode=driving';

            $response
                ->assertSee('data-latitude="'.e((string) $facility['latitude']).'"', false)
                ->assertSee('data-longitude="'.e((string) $facility['longitude']).'"', false)
                ->assertSee('data-directions-url="'.e($directionsUrl).'"', false);
        }
    }

    private function assertFacilityHasValidSanPabloCoordinates(array $facility): void
    {
        $name = $facility['name'] ?? 'Unnamed facility';

        $this->assertArrayHasKey('latitude', $facility, "{$name} is missing latitude.");
        $this->assertArrayHasKey('longitude', $facility, "{$name} is missing longitude.");
        $this->assertIsNumeric($facility['latitude'], "{$name} latitude must be numeric.");
        $this->assertIsNumeric($facility['longitude'], "{$name} longitude must be numeric.");

        $latitude = (float) $facility['latitude'];
        $longitude = (float) $facility['longitude'];

        $this->assertGreaterThanOrEqual(14.03, $latitude, "{$name} latitude is outside San Pablo City.");
        $this->assertLessThanOrEqual(14.10, $latitude, "{$name} latitude is outside San Pablo City.");
        $this->assertGreaterThanOrEqual(121.27, $longitude, "{$name} longitude is outside San Pablo City.");
        $this->assertLessThanOrEqual(121.35, $longitude, "{$name} longitude is outside San Pablo City.");
    }
}
