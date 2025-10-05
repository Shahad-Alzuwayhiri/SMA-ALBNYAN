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
        // simplified: fetch contract data from DB (pseudo)
        $contract = [
            'id' => $id,
            'content' => '...contract content...'
        ];

        $pdfService = env('PDF_SERVICE_URL', 'http://127.0.0.1:8001');
        $client = new Client(['base_uri' => $pdfService]);

        // 1) If we had an original PDF design, we'd upload it. Here we send the HTML/text.
        // For demo we'll assume there's a design file at storage path 'designs/contract_design.pdf'
        $designPath = storage_path('app/designs/contract_design.pdf');
        if (!file_exists($designPath)) {
            return response('Design PDF missing', 500);
        }

        // 2) Upload design and get positions
        try {
            $res = $client->request('POST', '/extract_positions', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($designPath, 'r'),
                        'filename' => basename($designPath),
                    ],
                ],
                'timeout' => 30
            ]);
        } catch (\Exception $e) {
            return response('PDF service error: ' . $e->getMessage(), 500);
        }
        $positions = json_decode($res->getBody()->getContents(), true);

        // 3) Send positions to render_overlay and get overlay PDF
        try {
            $res2 = $client->request('POST', '/render_overlay', [
                'json' => $positions,
                'timeout' => 30
            ]);
        } catch (\Exception $e) {
            return response('PDF render error: ' . $e->getMessage(), 500);
        }
        $overlayPdf = $res2->getBody()->getContents();

        // 4) Save overlay temporarily and attempt merge via shell pdftk (or use FPDI/TCPDI in PHP)
        $tmpOverlay = storage_path('app/tmp/overlay_' . $id . '.pdf');
        @mkdir(dirname($tmpOverlay), 0755, true);
        file_put_contents($tmpOverlay, $overlayPdf);

        $design = $designPath;
        $out = storage_path('app/tmp/final_' . $id . '.pdf');
        $cmd = "pdftk " . escapeshellarg($design) . " multibackground " . escapeshellarg($tmpOverlay) . " output " . escapeshellarg($out);
        exec($cmd, $outLines, $rc);
        if ($rc !== 0) {
            // fallback: return overlay directly
            return response($overlayPdf, 200, ['Content-Type' => 'application/pdf']);
        }

        return response()->file($out, ['Content-Type' => 'application/pdf']);
    }
}
