<?php

class SimpleViewEngine {
    
    public static function render($viewName, $data = []) {
        // Extract data to variables
        extract($data);
        
        // Global variables
        global $errors;
        if (!$errors) {
            $errors = new class {
                public function any() { return !empty($_SESSION['errors']); }
                public function all() { return $_SESSION['errors'] ?? []; }
            };
        }
        
        // Convert view name to file path
        $viewFile = __DIR__ . '/../resources/views/' . str_replace('.', '/', $viewName) . '.blade.php';
        
        if (!file_exists($viewFile)) {
            return "<h1>View not found: $viewName</h1><p>Looking for: $viewFile</p>";
        }
        
        // Start output buffering
        ob_start();
        
        try {
            // Simple Blade-like processing
            $content = file_get_contents($viewFile);
            
            // Process @extends, @section, @yield
            $content = self::processBladeDirectives($content, $data);
            
            // Process {{ }} variables
            $content = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($matches) use ($data) {
                $var = trim($matches[1]);
                return self::evaluateExpression($var, $data);
            }, $content);
            
            // Process {!! !!} unescaped variables
            $content = preg_replace_callback('/\{!!\s*(.+?)\s*!!\}/', function($matches) use ($data) {
                $var = trim($matches[1]);
                return self::evaluateExpression($var, $data, false);
            }, $content);
            
            // Process @if, @foreach, @forelse
            $content = self::processControlStructures($content, $data);
            
            echo $content;
            
        } catch (Exception $e) {
            echo "<h1>Error rendering view: $viewName</h1>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
        
        return ob_get_clean();
    }
    
    private static function processBladeDirectives($content, $data) {
        // Handle @extends
        if (preg_match('/@extends\([\'"](.+?)[\'"]\)/', $content, $matches)) {
            $layout = $matches[1];
            $layoutFile = __DIR__ . '/../resources/views/' . str_replace('.', '/', $layout) . '.blade.php';
            if (file_exists($layoutFile)) {
                $layoutContent = file_get_contents($layoutFile);
                
                // Extract sections from content
                preg_match_all('/@section\([\'"](.+?)[\'"]\)(.*?)@endsection/s', $content, $sectionMatches);
                
                for ($i = 0; $i < count($sectionMatches[0]); $i++) {
                    $sectionName = $sectionMatches[1][$i];
                    $sectionContent = $sectionMatches[2][$i];
                    $layoutContent = str_replace("@yield('$sectionName')", $sectionContent, $layoutContent);
                }
                
                return $layoutContent;
            }
        }
        
        return $content;
    }
    
    private static function processControlStructures($content, $data) {
        // Process @if statements
        $content = preg_replace_callback('/@if\s*\((.+?)\)(.*?)@endif/s', function($matches) use ($data) {
            $condition = trim($matches[1]);
            $body = $matches[2];
            
            if (self::evaluateCondition($condition, $data)) {
                return $body;
            }
            return '';
        }, $content);
        
        // Process @foreach
        $content = preg_replace_callback('/@foreach\s*\((.+?)\s+as\s+(.+?)\)(.*?)@endforeach/s', function($matches) use ($data) {
            $arrayExpr = trim($matches[1]);
            $itemVar = trim($matches[2]);
            $body = $matches[3];
            
            $array = self::evaluateExpression($arrayExpr, $data);
            if (!is_array($array)) return '';
            
            $result = '';
            foreach ($array as $item) {
                $itemData = array_merge($data, [$itemVar => $item]);
                $itemBody = $body;
                
                // Replace variables in body
                $itemBody = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($m) use ($itemData) {
                    return self::evaluateExpression(trim($m[1]), $itemData);
                }, $itemBody);
                
                $result .= $itemBody;
            }
            return $result;
        }, $content);
        
        // Process @forelse
        $content = preg_replace_callback('/@forelse\s*\((.+?)\s+as\s+(.+?)\)(.*?)@empty(.*?)@endforelse/s', function($matches) use ($data) {
            $arrayExpr = trim($matches[1]);
            $itemVar = trim($matches[2]);
            $body = $matches[3];
            $emptyBody = $matches[4];
            
            $array = self::evaluateExpression($arrayExpr, $data);
            if (!is_array($array) || empty($array)) {
                return $emptyBody;
            }
            
            $result = '';
            foreach ($array as $item) {
                $itemData = array_merge($data, [$itemVar => $item]);
                $itemBody = $body;
                
                $itemBody = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($m) use ($itemData) {
                    return self::evaluateExpression(trim($m[1]), $itemData);
                }, $itemBody);
                
                $result .= $itemBody;
            }
            return $result;
        }, $content);
        
        return $content;
    }
    
    private static function evaluateExpression($expr, $data, $escape = true) {
        // Handle simple variable access
        if (isset($data[$expr])) {
            $value = $data[$expr];
        } else if (preg_match('/\$(\w+)/', $expr, $matches)) {
            $varName = $matches[1];
            $value = $data[$varName] ?? '';
        } else if (strpos($expr, '->') !== false || strpos($expr, '[') !== false) {
            // Handle object/array access
            $value = self::evaluateComplexExpression($expr, $data);
        } else {
            $value = $expr; // Return as-is if not found
        }
        
        if ($escape && is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return $value;
    }
    
    private static function evaluateComplexExpression($expr, $data) {
        // Simple implementation for common patterns
        if (preg_match('/(\w+)\[\'(.+?)\'\]/', $expr, $matches)) {
            $var = $matches[1];
            $key = $matches[2];
            return $data[$var][$key] ?? '';
        }
        
        if (preg_match('/(\w+)->(\w+)/', $expr, $matches)) {
            $var = $matches[1];
            $prop = $matches[2];
            $obj = $data[$var] ?? null;
            if (is_object($obj) && property_exists($obj, $prop)) {
                return $obj->$prop;
            }
            if (is_array($obj) && isset($obj[$prop])) {
                return $obj[$prop];
            }
        }
        
        return '';
    }
    
    private static function evaluateCondition($condition, $data) {
        // Simple condition evaluation
        if (preg_match('/(\w+)\s*===?\s*[\'"](.+?)[\'"]/', $condition, $matches)) {
            $var = $matches[1];
            $value = $matches[2];
            return ($data[$var] ?? '') == $value;
        }
        
        if (preg_match('/!(.+)/', $condition, $matches)) {
            $var = trim($matches[1]);
            return empty($data[$var]);
        }
        
        if (isset($data[$condition])) {
            return !empty($data[$condition]);
        }
        
        return false;
    }
}