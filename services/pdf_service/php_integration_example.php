<?php
// Simple PHP example using cURL to interact with the FastAPI PDF service
// 1) Upload original PDF and get positions.json
$service = 'http://127.0.0.1:8001';
$src_pdf = __DIR__ . '/../../contract_fixed_v1.pdf';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $service . '/extract_positions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$cfile = new CURLFile($src_pdf, 'application/pdf');
curl_setopt($ch, CURLOPT_POSTFIELDS, array('file' => $cfile));
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
if ($err) {
    echo "cURL Error: $err\n";
    exit(1);
}
file_put_contents(__DIR__ . '/positions.json', $res);
echo "Wrote positions.json\n";

// 2) Send positions.json to render_overlay
$payload = file_get_contents(__DIR__ . '/positions.json');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $service . '/render_overlay');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$pdf = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
if ($err) {
    echo "cURL Error render: $err\n";
    exit(1);
}
file_put_contents(__DIR__ . '/overlay.pdf', $pdf);
echo "Wrote overlay.pdf\n";

// 3) (Optional) merge overlay.pdf with design PDF using pdftk or PHP library (example using shell pdftk if available)
$design = __DIR__ . '/../../tmp_html_render.pdf';
$out = __DIR__ . '/../../final_merged.pdf';
$cmd = "pdftk " . escapeshellarg($design) . " multibackground " . escapeshellarg(__DIR__ . '/overlay.pdf') . " output " . escapeshellarg($out);
exec($cmd, $out_lines, $rc);
if ($rc === 0) {
    echo "Merged into final_merged.pdf\n";
} else {
    echo "Merge failed (rc=$rc). You can merge with FPDI/TCPDI in PHP instead.\n";
}
?>
