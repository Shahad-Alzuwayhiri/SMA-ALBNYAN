<?php

namespace App\Controllers;

/**
 * Home Controller
 * التحكم في الصفحة الرئيسية
 */
class HomeController extends BaseController
{
    public function index()
    {
        // Extra safety check to prevent redirect loops
        if (empty($this->user) || !is_array($this->user)) {
            // Guest user - redirect to welcome
            $this->redirect('/welcome');
            return;
        }
        
        // Check if user is logged in and redirect accordingly
        if ($this->user && isset($this->user['role'])) {
            if (in_array($this->user['role'], ['admin', 'manager'])) {
                $this->redirect('/manager-dashboard');
            } else {
                $this->redirect('/employee-dashboard');
            }
            return;
        }
        
        // Fallback: Show welcome page for guests
        $this->redirect('/welcome');
    }
    
    public function welcome()
    {
        return $this->view('welcome');
    }
    
    public function loginPages()
    {
        return $this->view('login_index');
    }
}