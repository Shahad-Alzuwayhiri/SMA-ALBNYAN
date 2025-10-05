<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    private Client $client;
    private string $serviceUrl;

    public function __construct()
    {
        $this->serviceUrl = env('PDF_SERVICE_URL', 'http://127.0.0.1:8001');
        $this->client = new Client([
            'base_uri' => $this->serviceUrl,
            'timeout' => 60,
        ]);
    }

    /**
     * Extract text positions from a PDF file
     */
    public function extractPositions(string $pdfPath): ?array
    {
        try {
            if (!file_exists($pdfPath)) {
                Log::error("PDF file not found: {$pdfPath}");
                return null;
            }

            $response = $this->client->request('POST', '/extract_positions', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($pdfPath, 'r'),
                        'filename' => basename($pdfPath),
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['positions'] ?? null;

        } catch (RequestException $e) {
            Log::error("PDF extraction failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Render overlay PDF from positions data
     */
    public function renderOverlay(array $positions): ?string
    {
        try {
            $response = $this->client->request('POST', '/render_overlay', [
                'json' => ['positions' => $positions],
            ]);

            return $response->getBody()->getContents();

        } catch (RequestException $e) {
            Log::error("PDF overlay render failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate contract PDF using hybrid approach
     */
    public function generateContractPdf(array $contractData, string $templatePath = null): ?string
    {
        // 1. If we have a design template, extract positions
        if ($templatePath && file_exists($templatePath)) {
            $positions = $this->extractPositions($templatePath);
            if (!$positions) {
                return null;
            }

            // 2. Modify positions with actual contract data (simplified)
            $this->populatePositionsWithData($positions, $contractData);

            // 3. Render overlay with populated data
            $overlayPdf = $this->renderOverlay($positions);
            if (!$overlayPdf) {
                return null;
            }

            // 4. Merge with design template (would need additional service endpoint or FPDI)
            return $this->mergeDesignWithOverlay($templatePath, $overlayPdf);
        }

        // Fallback: generate simple PDF from template (to be implemented)
        return $this->generateSimplePdf($contractData);
    }

    /**
     * Populate extracted positions with actual contract data
     */
    private function populatePositionsWithData(array &$positions, array $data): void
    {
        foreach ($positions as &$position) {
            $text = $position['text'] ?? '';
            
            // Replace placeholders with actual data
            foreach ($data as $key => $value) {
                $placeholder = "{{ data.{$key} }}";
                if (str_contains($text, $placeholder)) {
                    $position['text'] = str_replace($placeholder, $value ?? '', $text);
                }
            }
        }
    }

    /**
     * Merge design PDF with overlay PDF
     */
    private function mergeDesignWithOverlay(string $designPath, string $overlayPdf): ?string
    {
        // Save overlay temporarily
        $tempOverlay = storage_path('app/temp/overlay_' . uniqid() . '.pdf');
        @mkdir(dirname($tempOverlay), 0755, true);
        file_put_contents($tempOverlay, $overlayPdf);

        // Use pdftk for merging (if available)
        $output = storage_path('app/temp/merged_' . uniqid() . '.pdf');
        $command = sprintf(
            'pdftk %s multibackground %s output %s',
            escapeshellarg($designPath),
            escapeshellarg($tempOverlay),
            escapeshellarg($output)
        );

        exec($command, $cmdOutput, $returnCode);

        if ($returnCode === 0 && file_exists($output)) {
            $result = file_get_contents($output);
            @unlink($tempOverlay);
            @unlink($output);
            return $result;
        }

        // Fallback: return overlay only
        @unlink($tempOverlay);
        return $overlayPdf;
    }

    /**
     * Generate simple PDF without overlay (fallback)
     */
    private function generateSimplePdf(array $contractData): string
    {
        // This would use TCPDF or similar to generate a basic PDF
        // For now, return a placeholder
        return "Simple PDF generation not implemented yet";
    }

    /**
     * Health check for the Python service
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->client->request('GET', '/health', ['timeout' => 5]);
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            return false;
        }
    }
}