<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ServiceRegistry;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gateway:services', function () {
    $services = ServiceRegistry::all();
    
    if (empty($services)) {
        $this->warn('No services registered.');
        return;
    }
    
    $this->info('Registered Microservices:');
    $this->newLine();
    
    $headers = ['Service', 'URL', 'Status', 'Description'];
    $rows = [];
    
    foreach ($services as $name => $config) {
        $url = $config['url'] ?? 'Not configured';
        $description = $config['description'] ?? 'No description';
        $routeCount = count($config['routes'] ?? []);
        
        // Try to check service status
        try {
            $healthUrl = rtrim($url, '/') . '/' . ltrim(ServiceRegistry::getHealthEndpoint($name), '/');
            $response = \Illuminate\Support\Facades\Http::timeout(2)->get($healthUrl);
            $status = $response->successful() ? '<fg=green>UP</>' : '<fg=red>DOWN</>';
        } catch (\Exception $e) {
            $status = '<fg=yellow>UNKNOWN</>';
        }
        
        $rows[] = [
            $name,
            $url,
            $status,
            $description . " ({$routeCount} routes)"
        ];
    }
    
    $this->table($headers, $rows);
    $this->newLine();
    $this->info('Total services: ' . count($services));
})->purpose('List all registered microservices');

Artisan::command('gateway:service {name}', function (string $name) {
    $service = ServiceRegistry::get($name);
    
    if (!$service) {
        $this->error("Service '{$name}' not found.");
        $this->info('Available services: ' . implode(', ', ServiceRegistry::getServiceNames()));
        return;
    }
    
    $this->info("Service: {$name}");
    $this->newLine();
    $this->line("URL: " . ($service['url'] ?? 'Not configured'));
    $this->line("Health Endpoint: " . ServiceRegistry::getHealthEndpoint($name));
    $this->line("Description: " . ($service['description'] ?? 'No description'));
    $this->newLine();
    
    $routes = $service['routes'] ?? [];
    if (empty($routes)) {
        $this->warn('No routes configured for this service.');
    } else {
        $this->info('Routes:');
        $this->newLine();
        
        $headers = ['Gateway Path', 'Service Path', 'Methods', 'Middleware'];
        $rows = [];
        
        foreach ($routes as $route) {
            $rows[] = [
                $route['path'] ?? 'N/A',
                $route['service_path'] ?? 'N/A',
                implode(', ', $route['methods'] ?? []),
                implode(', ', $route['middleware'] ?? [])
            ];
        }
        
        $this->table($headers, $rows);
    }
})->purpose('Show details for a specific service');
