<?php
/**
 * Global Helper Functions for backward compatibility
 * دوال مساعدة شاملة للتوافق مع الكود القديم
 * 
 * This file provides global function wrappers for the Functions class
 */

// Status functions
if (!function_exists('getStatusText')) {
    function getStatusText($status) {
        return \App\Helpers\Functions::getStatusText($status);
    }
}

if (!function_exists('getStatusClass')) {
    function getStatusClass($status) {
        return \App\Helpers\Functions::getStatusClass($status);
    }
}

if (!function_exists('getStatusLabel')) {
    function getStatusLabel($status) {
        return \App\Helpers\Functions::getStatusLabel($status);
    }
}

if (!function_exists('getStatusInfo')) {
    function getStatusInfo($status) {
        return \App\Helpers\Functions::getStatusInfo($status);
    }
}

if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        return \App\Helpers\Functions::getStatusBadge($status);
    }
}

// Authentication functions
if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return \App\Helpers\Functions::isAuthenticated();
    }
}

if (!function_exists('hasRole')) {
    function hasRole($role) {
        return \App\Helpers\Functions::hasRole($role);
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return \App\Helpers\Functions::getCurrentUser();
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth($redirectUrl = '/login') {
        return \App\Helpers\Functions::requireAuth($redirectUrl);
    }
}

if (!function_exists('requireRole')) {
    function requireRole($role, $redirectUrl = '/login') {
        return \App\Helpers\Functions::requireRole($role, $redirectUrl);
    }
}

// Configuration functions
if (!function_exists('config')) {
    function config($key, $default = null) {
        return \App\Helpers\Functions::config($key, $default);
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        return \App\Helpers\Functions::env($key, $default);
    }
}

if (!function_exists('app_name')) {
    function app_name() {
        return \App\Helpers\Functions::appName();
    }
}

if (!function_exists('app_version')) {
    function app_version() {
        return \App\Helpers\Functions::appVersion();
    }
}

if (!function_exists('is_debug_mode')) {
    function is_debug_mode() {
        return \App\Helpers\Functions::isDebugMode();
    }
}

// Database functions
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        return \App\Helpers\Functions::getDatabaseConnection();
    }
}

// Utility functions
if (!function_exists('formatArabicDate')) {
    function formatArabicDate($date) {
        return \App\Helpers\Functions::formatArabicDate($date);
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        return \App\Helpers\Functions::formatFileSize($bytes);
    }
}