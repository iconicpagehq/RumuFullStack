<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$output = [
    'controllers' => [],
    'services' => [],
    'models' => [],
    'routes' => [],
    'events' => [],
    'listeners' => [],
    'jobs' => [],
    'mail' => []
];

// 1. Routes
$routes = \Illuminate\Support\Facades\Route::getRoutes();
foreach ($routes as $route) {
    $action = $route->getActionName();
    $output['routes'][] = [
        'uri' => $route->uri(),
        'methods' => $route->methods(),
        'action' => $action,
        'middleware' => $route->middleware(),
        'name' => $route->getName()
    ];
}

function getMethodDetails(ReflectionClass $class) {
    $methods = [];
    foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->class !== $class->getName()) continue;
        if ($method->getName() === '__construct') continue;
        $methods[] = $method->getName();
    }
    return $methods;
}

function getConstructorDependencies(ReflectionClass $class) {
    $deps = [];
    if ($class->hasMethod('__construct')) {
        $params = $class->getMethod('__construct')->getParameters();
        foreach ($params as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $deps[] = $type->getName();
            }
        }
    }
    return $deps;
}

// 2. Controllers
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Http/Controllers')));
foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    $className = 'App\\Http\\Controllers\\' . str_replace('/', '\\', str_replace(app_path('Http/Controllers/'), '', $file->getPathname()));
    $className = str_replace('.php', '', $className);
    
    if (class_exists($className)) {
        $ref = new ReflectionClass($className);
        $output['controllers'][$className] = [
            'methods' => getMethodDetails($ref),
            'injected_services' => getConstructorDependencies($ref),
        ];
    }
}

// 3. Services
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Services')));
foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    $className = 'App\\Services\\' . str_replace('/', '\\', str_replace(app_path('Services/'), '', $file->getPathname()));
    $className = str_replace('.php', '', $className);
    
    if (class_exists($className)) {
        $ref = new ReflectionClass($className);
        $output['services'][$className] = [
            'methods' => getMethodDetails($ref),
            'dependencies' => getConstructorDependencies($ref),
        ];
    }
}

// 4. Models
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Models')));
foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    $className = 'App\\Models\\' . str_replace('/', '\\', str_replace(app_path('Models/'), '', $file->getPathname()));
    $className = str_replace('.php', '', $className);
    
    if (class_exists($className)) {
        $ref = new ReflectionClass($className);
        $model = new $className;
        
        $relationships = [];
        foreach ($ref->getMethods() as $method) {
            if ($method->class === $className && !empty($method->getReturnType())) {
                $retType = $method->getReturnType()->getName();
                if (str_contains($retType, 'Illuminate\Database\Eloquent\Relations')) {
                    $relationships[] = $method->getName();
                }
            }
        }
        
        $output['models'][$className] = [
            'table' => $model->getTable(),
            'fillable' => $model->getFillable(),
            'casts' => $model->getCasts(),
            'relationships' => $relationships
        ];
    }
}

file_put_contents('audit_data.json', json_encode($output, JSON_PRETTY_PRINT));
echo "Audit data generated.";
