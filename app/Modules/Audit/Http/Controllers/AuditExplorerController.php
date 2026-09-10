<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditExplorerController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = session('active_company_id');

        $userId = $request->query('user_id');
        $action = $request->query('action');
        $entityType = $request->query('entity_type');
        $dateFrom = $request->query('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $query = AuditLog::with('user')
            ->where('company_id', $companyId)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($entityType) {
            $query->where('entity_type', 'like', "%{$entityType}%");
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        $users = User::all();

        return view('audit.index', compact(
            'logs',
            'users',
            'userId',
            'action',
            'entityType',
            'dateFrom',
            'dateTo'
        ));
    }
}
