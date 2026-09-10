<?php

namespace App\Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Models\AppNotification;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Workflow\Models\ApprovalRequest;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        // Dynamically ensure real alerts exist if there are pending approvals or overdue invoices
        $pendingApprovals = ApprovalRequest::where('company_id', $companyId)->where('status', 'pending')->count();
        if ($pendingApprovals > 0 && !AppNotification::where('company_id', $companyId)->where('type', 'approval')->exists()) {
            AppNotification::create([
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'type' => 'approval',
                'title' => "{$pendingApprovals} Dokumen Memerlukan Persetujuan",
                'message' => "Terdapat {$pendingApprovals} transaksi pada antrean kerja yang menunggu verifikasi Anda.",
                'action_url' => route('workflow.index'),
                'is_read' => false,
            ]);
        }

        $overdueInvoices = SalesInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->where('due_date', '<', now()->toDateString())
            ->count();

        if ($overdueInvoices > 0 && !AppNotification::where('company_id', $companyId)->where('type', 'due_date')->exists()) {
            AppNotification::create([
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'type' => 'due_date',
                'title' => "Peringatan: {$overdueInvoices} Faktur Penjualan Jatuh Tempo",
                'message' => "Segera lakukan penagihan kepada pelanggan untuk faktur yang telah melewati tanggal jatuh tempo.",
                'action_url' => route('sales.index'),
                'is_read' => false,
            ]);
        }

        $notifications = AppNotification::where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $companyId = session('current_company_id');
        $notif = AppNotification::where('company_id', $companyId)->findOrFail($id);
        $notif->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }
}
