<?php
/**
 * Directory Access Protection
 * حماية المجلد من الوصول المباشر
 */
http_response_code(403);
exit('Access Denied');
?>