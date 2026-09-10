<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TataKelolaRouteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_tata_kelola_routes(): void
    {
        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $user = User::where('email', 'owner@akru.id')->first();
        $company = Company::first();

        $routes = [
            'workflow' => '/workflow',
            'audit' => '/audit',
            'companies' => '/companies',
            'branches' => '/branches',
            'settings' => '/settings',
        ];

        foreach ($routes as $name => $uri) {
            $resp = $this->actingAs($user)
                ->withSession([
                    'active_company_id' => $company->id,
                    'current_company_id' => $company->id,
                ])
                ->get($uri);

            $resp->assertStatus(200);
        }
    }
}
