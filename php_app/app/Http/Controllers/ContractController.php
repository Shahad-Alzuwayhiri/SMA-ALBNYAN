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
        $contract = Contract::find($id);
        if (!$contract) {
            return response('Contract not found', 404);
        }

        $pdfService = new \App\Services\PdfService();
        
        // Prepare contract data for PDF generation
        $contractData = [
            'partner2_name' => $contract->client_name,
            'partner_name' => $contract->client_name,
            'partner_id' => $contract->client_id_number,
            'partner_phone' => $contract->client_phone,
            'client_address' => $contract->client_address,
            'investment_amount' => $contract->investment_amount,
            'capital_amount' => $contract->capital_amount,
            'profit' => $contract->profit_percent,
            'profit_percent' => $contract->profit_percent,
            'profit_interval_months' => $contract->profit_interval_months,
            'withdrawal_notice_days' => $contract->withdrawal_notice_days,
            'start_date_h' => $contract->start_date_h,
            'end_date_h' => $contract->end_date_h,
            'commission_percent' => $contract->commission_percent,
            'exit_notice_days' => $contract->exit_notice_days,
            'penalty_amount' => $contract->penalty_amount,
            'contract_number' => $contract->client_contract_no,
        ];

        // Check for design template
        $designPath = storage_path('app/designs/contract_design.pdf');
        
        $pdfContent = $pdfService->generateContractPdf($contractData, $designPath);
        
        if (!$pdfContent) {
            return response('Failed to generate PDF', 500);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contract_' . $id . '.pdf"'
        ]);
    }
}
