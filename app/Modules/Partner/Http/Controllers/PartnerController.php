<?php

namespace App\Modules\Partner\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Core\Models\Company;
use App\Modules\Partner\Models\PartnerClient;
use App\Modules\Partner\Models\PartnerWorkspace;
use App\Modules\Workflow\Models\ApprovalRequest;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        // Seed default partner workspace if none exists
        $workspace = PartnerWorkspace::firstOrCreate([
            'name' => 'KKP & Rekan Tax & Accounting Advisory',
            'contact_email' => 'advisory@akrupartner.id',
        ], [
            'license_number' => 'SIUP-KKP-2026/0981',
            'contact_phone' => '021-5558899',
        ]);

        // Connect current company as a managed client
        $companies = Company::all();
        foreach ($companies as $comp) {
            PartnerClient::firstOrCreate([
                'partner_workspace_id' => $workspace->id,
                'company_id' => $comp->id,
            ], [
                'access_level' => 'full_review',
                'consent_granted_at' => now(),
                'is_active' => true,
            ]);
        }

        // Portfolio Dashboard metrics across managed clients
        $clients = PartnerClient::with('company')->where('partner_workspace_id', $workspace->id)->get();
        $clientOverview = [];

        foreach ($clients as $client) {
            $unclosedPeriods = FiscalPeriod::where('company_id', $client->company_id)->where('is_closed', false)->count();
            $pendingExceptions = ApprovalRequest::where('company_id', $client->company_id)->where('status', 'pending')->count();

            $clientOverview[] = [
                'client' => $client,
                'unclosed_periods' => $unclosedPeriods,
                'pending_exceptions' => $pendingExceptions,
                'status' => $unclosedPeriods > 0 ? 'Review Needed' : 'Compliant',
            ];
        }

        return view('partner.workspace.index', compact('workspace', 'clientOverview'));
    }
}
