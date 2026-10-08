<?php

namespace Tests\Feature;

use App\Models\Mother;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductionAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dswd_responsive_report_assets_and_pagination(): void
    {
        $staff = \App\Models\DswdStaff::create(['name'=>'Sample Reviewer', 'email'=>'responsive@example.test', 'password'=>'sample-password', 'is_active'=>true]);
        foreach (range(1, 20) as $i) {
            Mother::create(['first_name'=>'Sample '.$i, 'last_name'=>'Dela Cruz', 'email'=>"responsive.$i@example.test", 'password'=>'unused', 'barangay'=>'San Jose', 'municipality_city'=>'San Pablo City', 'contact_number'=>'09170000000', 'pregnancy_status'=>'pregnant', 'is_4ps_beneficiary'=>true]);
        }
        foreach (['dashboard', 'reports'] as $page) {
            foreach (['http', 'https'] as $scheme) {
                $path = $page === 'dashboard' ? '/dswd/dashboard' : '/dswd/f1kd/reports';
                $html = $this->withSession(['auth_role'=>'dswd_staff', 'auth_id'=>$staff->id])
                    ->withServerVariables(['REMOTE_ADDR'=>'10.0.0.10', 'HTTP_X_FORWARDED_PROTO'=>$scheme])
                    ->get('http://portal.example.test'.$path)->assertOk()->getContent();
                $this->assertStringContainsString('/css/site-responsive.css', $html);
                if ($page === 'reports') $this->assertStringContainsString('site-pagination-controls', $html);
                if ($directory = getenv('INAY_ASSET_FIXTURES')) file_put_contents($directory.'/dswd-'.$page.'-'.$scheme.'.html', $html);
            }
        }
    }

    public static function portalPages(): array
    {
        $cases = [];
        foreach (['dashboard' => 'account-pages', 'consultation' => 'consultation', 'clinic-schedule' => 'mother-appointments'] as $page => $stylesheet) {
            foreach (['http', 'https'] as $scheme) {
                $cases[$page.' '.$scheme] = [$page, $stylesheet, $scheme];
            }
        }

        return $cases;
    }

    #[DataProvider('portalPages')]
    public function test_portal_assets_follow_the_browser_scheme_behind_a_proxy(string $page, string $stylesheet, string $scheme): void
    {
        $mother = Mother::create([
            'first_name' => 'Ana', 'last_name' => 'Cruz',
            'email' => 'assets@example.test', 'password' => bcrypt('password123'),
            'contact_number' => '09171111111', 'barangay' => 'Concepcion',
        ]);

        $server = ['REMOTE_ADDR' => '10.0.0.10'];
        if ($scheme === 'https') {
            // Railway terminates TLS; the PHP server itself receives HTTP.
            $server['HTTP_X_FORWARDED_PROTO'] = 'https';
        }

        $response = $this->withSession(['auth_role' => 'mother', 'auth_id' => $mother->id])
            ->withServerVariables($server)
            ->get('http://portal.example.test/mother/'.$page)
            ->assertOk();

        $html = $response->getContent();
        preg_match_all('/(?:href|src)="(https?:\/\/portal\.example\.test\/[^\"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $url) {
            $this->assertSame($scheme, parse_url(html_entity_decode($url), PHP_URL_SCHEME), $url);
        }
        $this->assertStringContainsString($scheme.'://portal.example.test/css/'.$stylesheet.'.css?v=', $html);
        $this->assertStringNotContainsString(':5173', $html);
        $this->assertStringContainsString('data-photo-crop-modal hidden', $html);

        // Export only synthetic in-memory test data for optional browser checks.
        if ($directory = getenv('INAY_ASSET_FIXTURES')) {
            file_put_contents($directory.'/'.$page.'-'.$scheme.'.html', $html);
        }
    }
}
