<?php

namespace App\Controllers;

use Exception;

/**
 * Link Checker Controller
 * فحص الروابط والتنقل
 */
class LinkCheckerController extends BaseController
{
    public function index()
    {
        if (!$this->isAdminOrManager()) {
            return $this->view('errors/403', ['message' => 'Unauthorized access']);
        }
        
        $links = $this->getAllSystemLinks();
        $results = [];
        
        foreach ($links as $link) {
            $results[] = [
                'url' => $link,
                'status' => $this->checkLink($link),
                'response_time' => $this->getResponseTime($link)
            ];
        }
        
        return $this->view('admin/link_checker', compact('results'));
    }
    
    private function getAllSystemLinks()
    {
        return [
            '/',
            '/login',
            '/signup',
            '/logout',
            '/contracts',
            '/contracts/create',
            '/notifications',
            '/manager-dashboard',
            '/employee-dashboard',
            '/welcome',
            '/login-pages'
        ];
    }
    
    private function checkLink($url)
    {
        try {
            $fullUrl = $this->getBaseUrl() . $url;
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $headers = get_headers($fullUrl, 1, $context);
            
            if ($headers && isset($headers[0])) {
                $statusCode = $this->extractStatusCode($headers[0]);
                return [
                    'code' => $statusCode,
                    'status' => $statusCode < 400 ? 'success' : 'error',
                    'message' => $headers[0]
                ];
            }
            
            return [
                'code' => 0,
                'status' => 'error',
                'message' => 'No response'
            ];
            
        } catch (Exception $e) {
            return [
                'code' => 0,
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function getResponseTime($url)
    {
        $start = microtime(true);
        $this->checkLink($url);
        $end = microtime(true);
        
        return round(($end - $start) * 1000, 2); // milliseconds
    }
    
    private function extractStatusCode($header)
    {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches);
        return isset($matches[1]) ? (int)$matches[1] : 0;
    }
    
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    private function isAdminOrManager()
    {
        return $this->user && in_array($this->user['role'], ['admin', 'manager']);
    }
}