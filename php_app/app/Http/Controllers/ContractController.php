<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use function view;
use function redirect;
class ContractController extends BaseController
{

class ContractController extends Controller
{
    public function index()
    {
        return view('contracts.index');
    }

    public function create()
    {
        return view('contracts.create');
    }

        $request->validate([
            'partner2_name' => 'required|string',
            // ... other fields
        ]);
        // store to DB (to implement)
        return redirect()->route('contracts.index');
        ]);
        // store to DB (to implement)
        return redirect()->route('contracts.index');
    }

    public function show($id)
    {
        // fetch contract from DB
        return view('contracts.show', ['id'=>$id]);
    }

    public function pdf($id)
    {
        // Sample contract data for testing - later fetch from database
        $contractData = [
            'contract_number' => 'CT-' . str_pad($id, 4, '0', STR_PAD_LEFT),
            'partner2_name' => 'أحمد محمد العلي',
            'partner_name' => 'أحمد محمد العلي', 
            'partner_id' => '1234567890',
            'partner_phone' => '+966501234567',
            'client_address' => 'الرياض، المملكة العربية السعودية',
            'investment_amount' => 100000,
            'capital_amount' => 90000,
            'profit_percent' => 15,
            'profit_interval_months' => 3,
            'withdrawal_notice_days' => 30,
            'start_date_h' => date('Y-m-d'),
            'end_date_h' => date('Y-m-d', strtotime('+1 year')),
            'commission_percent' => 2,
            'exit_notice_days' => 30,
            'penalty_amount' => 5000,
        ];

        $pdfService = new \App\Services\PdfService();
        
        // Try PDF generation first
        $pdfContent = $pdfService->generateContractPdf($contractData);
        
        if ($pdfContent !== false) {
            // Success - return PDF
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="contract_' . $contractData['contract_number'] . '.pdf"'
            ]);
        }
        
        // Fallback to HTML version
        $htmlContent = $pdfService->generateHtmlContract($contractData);
        return response($htmlContent, 200, [
            'Content-Type' => 'text/html; charset=utf-8'
        ]);
    }

    public function inProgress()
    {
        // Sample data - replace with real database query
        $contracts_in_progress = [
            (object)[
                'id' => 1,
                'serial' => 'CT-0001',
                'client_name' => 'أحمد محمد',
                'status' => 'in_progress',
                'status_display' => 'قيد التنفيذ',
                'created_at' => now()->subDays(5),
            ],
            (object)[
                'id' => 2,
                'serial' => 'CT-0002',
                'client_name' => 'فاطمة علي',
                'status' => 'pending',
                'status_display' => 'بانتظار الموافقة',
                'created_at' => now()->subDays(2),
            ],
        ];

        return view('contracts.in_progress', compact('contracts_in_progress'));
    }

    public function closed()
    {
        // Sample data - replace with real database query
        $contracts_closed = [
            (object)[
                'id' => 3,
                'serial' => 'CT-0003',
                'client_name' => 'محمد سالم',
                'status' => 'completed',
                'status_display' => 'مكتمل',
                'created_at' => now()->subMonths(2),
            ],
            (object)[
                'id' => 4,
                'serial' => 'CT-0004',
                'client_name' => 'نورا أحمد',
                'status' => 'rejected',
                'status_display' => 'مرفوض',
                'created_at' => now()->subMonths(1),
            ],
        ];

        return view('contracts.closed', compact('contracts_closed'));
    }

    public function approve($id)
    {
        // Logic to approve contract
        // Contract::find($id)->update(['status' => 'approved']);
        return back()->with('success', 'تم اعتماد العقد بنجاح');
    }

    public function reject($id)
    {
        // Logic to reject contract
        // Contract::find($id)->update(['status' => 'rejected']);
        return back()->with('success', 'تم رفض العقد');
    }

    public function archive($id)
    {
        // Logic to archive contract
        // Contract::find($id)->update(['archived' => true]);
        return back()->with('success', 'تم أرشفة العقد');
    }
}
