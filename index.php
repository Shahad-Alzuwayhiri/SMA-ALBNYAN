<?php
// Redirect root access to the public front controller.
// This is a safe convenience for development when the webserver isn't
// configured to use the `public/` directory as the document root.

// No output before PHP tag: ensure DOCTYPE etc. are emitted by public/index.php
$target = './public/';

if (!headers_sent()) {
	header('Location: ' . $target, true, 302);
}
http_response_code(302);
exit('Redirecting to ' . $target);