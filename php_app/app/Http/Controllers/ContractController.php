<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Routing\Controller;

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

    public function store(Request $request)
    {
        // validate and store contract, simplified example
        $data = $request->validate([
            'partner2_name' => 'required|string',
            // ... other fields
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
}
