<?php

namespace App\Commons;

class CommonEnv {
    public static function getListRoutes()
    {
        $controllersNamespace = 'App\Http\Controllers\Api\\';
        $controllersDirectory = app_path('Http/Controllers/Api/');

        $routes = [];

        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL;
        $parsedUrl = parse_url($path);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $segments = explode('/', $path);
        $segmentsPath = end($segments);
        
        foreach (glob($controllersDirectory . '*/*.php') as $filename) {
            $relativePath = str_replace([$controllersNamespace, '.php'], '', $filename);

            $controllerName = str_replace('/', '\\', $relativePath);
            var_dump($controllerName);
        }

        $routes = [
            'endPoint' => $segmentsPath,
            'method' => strtolower($method)
        ];
        
        var_dump($routes); die;

        return $routes;
    }
}




<?php

namespace App\Commons;

class CommonEnv {
    public static function getListRoutes()
    {
        $controllersNamespace = 'pp\Http\Controllers\Api\\';
        $controllersDirectory = app_path('Http/Controllers/Api/');

        $routes = [];

        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL;
        $parsedUrl = parse_url($path);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $segments = explode('/', $path);
        $segmentsPath = end($segments);

        $stringSegments = str_replace('-', ' ', $segmentsPath);

        $resultStringSegments = ucwords($stringSegments);

        $bindSegment = str_replace(' ', '', $resultStringSegments."Controller");
        $bindSegmentControllerPath = "Api\\Dashboard\\".$bindSegment;

        var_dump($bindSegmentControllerPath);

        foreach (glob($controllersDirectory . '*/*.php') as $filename) {
            $relativePath = str_replace([$controllersNamespace, '.php'], '', $filename);

            $controllerName = str_replace('/', '\\', $relativePath);

            $segments = explode('\\', $controllerName);

            $desiredString = implode('\\', array_slice($segments, -3));
            var_dump($desiredString);
        }

        $routes = [
            'endPoint' => $segmentsPath,
            'method' => strtolower($method)
        ];
        
        var_dump($routes); die;

        return $routes;
    }
}
