<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class FilamentPermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't apply to unauthenticated users
        if (!Auth::guard('tenant')->check()) {
            return $next($request);
        }

        // Get the currently authenticated user
        $user = Auth::guard('tenant')->user();

        // Get the current Filament page class if available
        $resource = $this->getCurrentResource($request);

        // If we have a resource, check if the user has permission to access it
        if ($resource) {
            // Extract the resource name from the class name
            $resourceName = $this->getResourceModelName($resource);
            
            // Check if the user has permission to view this resource
            if ($resourceName && !$user->can("view {$resourceName}")) {
                abort(403, 'You do not have permission to access this resource.');
            }
            
            // If this is a create/edit/delete operation, check those permissions too
            $path = $request->path();
            
            if (str_contains($path, '/create') && !$user->can("create {$resourceName}")) {
                abort(403, 'You do not have permission to create this resource.');
            }
            
            if (str_contains($path, '/edit') && !$user->can("update {$resourceName}")) {
                abort(403, 'You do not have permission to edit this resource.');
            }
            
            if (($request->isMethod('delete') || str_contains($path, '/delete')) && !$user->can("delete {$resourceName}")) {
                abort(403, 'You do not have permission to delete this resource.');
            }
        }

        // Continue with the request
        return $next($request);
    }
    
    /**
     * Get the current Filament resource from the request
     */
    private function getCurrentResource(Request $request): ?string
    {
        $uri = $request->route()->uri();
        $segments = explode('/', $uri);
        
        // Check if we're in the admin panel
        if (isset($segments[0]) && $segments[0] === 'admin' && isset($segments[1])) {
            // Try to determine the resource from the URI
            $resourceSegment = $segments[1];
            
            // Convert kebab-case to StudlyCase for the resource name
            $resourceStudly = collect(explode('-', $resourceSegment))
                ->map(fn ($segment) => ucfirst($segment))
                ->join('');
            
            // Build the potential resource class
            $resourceClass = "App\\Filament\\Resources\\Tenant\\{$resourceStudly}Resource";
            
            if (class_exists($resourceClass)) {
                return $resourceClass;
            }
        }
        
        return null;
    }
    
    /**
     * Extract the model name from a resource class name
     */
    private function getResourceModelName(string $resourceClass): ?string
    {
        if (class_exists($resourceClass)) {
            try {
                // Get the model from the resource
                $reflectionClass = new ReflectionClass($resourceClass);
                
                if ($reflectionClass->hasMethod('getModelLabel')) {
                    $modelLabel = $resourceClass::getModelLabel();
                    return strtolower($modelLabel);
                }
                
                // Alternative: try to get the model directly
                if ($reflectionClass->hasProperty('model')) {
                    $modelProperty = $reflectionClass::$model;
                    $modelParts = explode('\\', $modelProperty);
                    return strtolower(end($modelParts));
                }
            } catch (\Exception $e) {
                // If we can't determine the model, default to checking by resource name
                $resourceParts = explode('\\', $resourceClass);
                $resourceName = end($resourceParts);
                $modelName = str_replace('Resource', '', $resourceName);
                return strtolower($modelName);
            }
        }
        
        return null;
    }
}