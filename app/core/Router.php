<?php
declare(strict_types=1);

/**
 * Maps index.php?r=controller/action to {Controller}Controller::{action}().
 * Only public methods declared on the concrete controller are routable.
 */
final class Router
{
    public static function dispatch(string $route): void
    {
        $route = trim($route, '/') ?: 'home/index';
        $parts = explode('/', $route);
        $controllerSlug = $parts[0];
        $actionSlug = $parts[1] ?? 'index';

        if (!preg_match('/^[a-z][a-z0-9-]*$/', $controllerSlug) || !preg_match('/^[a-z][a-z0-9-]*$/', $actionSlug)) {
            throw new HttpException(404);
        }

        $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerSlug))) . 'Controller';
        $method = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $actionSlug))));

        if (!class_exists($class) || !is_subclass_of($class, Controller::class)) {
            throw new HttpException(404);
        }
        $reflection = new ReflectionClass($class);
        if (!$reflection->hasMethod($method)) {
            throw new HttpException(404);
        }
        $ref = $reflection->getMethod($method);
        if (!$ref->isPublic() || $ref->isStatic() || $ref->getDeclaringClass()->getName() !== $class
            || str_starts_with($method, '__') || $ref->getNumberOfRequiredParameters() > 0) {
            throw new HttpException(404);
        }

        $GLOBALS['__route'] = $controllerSlug . '/' . $actionSlug;
        $controller = new $class();
        $controller->$method();
    }

    public static function current(): string
    {
        return $GLOBALS['__route'] ?? 'home/index';
    }
}
