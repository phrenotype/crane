# Crane
![github stars](https://img.shields.io/github/stars/phrenotype/crane?style=social)
![packagist stars](https://img.shields.io/packagist/stars/crane/crane)
![license](https://img.shields.io/github/license/phrenotype/crane)
![contributors](https://img.shields.io/github/contributors/phrenotype/crane)
![contributors](https://img.shields.io/github/languages/code-size/phrenotype/crane)
![downloads](https://img.shields.io/packagist/dm/crane/crane)

# Quick Start Guide: Routing HTTP Requests in Crane PHP Framework

## Introduction

Crane is a lightweight and secure PHP framework designed for minimalists who prioritize security. It can run in any hosting environment and provides a simple way to route HTTP requests. This guide will walk you through the basics of setting up routes, handling requests and responses, and using middleware.

## Installation

First, install Crane via Composer:

```bash
composer require crane/crane
```

## Configuration

Create a `config.php` file in your project root to store environment variables (optional but recommended):

```php
<?php
return [
    'app_name' => 'My Crane App',
    'debug' => true,
    // Add your configuration here
];
```

## Basic Setup

Create an `index.php` file in your project root. This will be your entry point.

```php
<?php

require_once 'vendor/autoload.php';

use Crane\Router\App;

// Create a new App instance (parameters are for custom request/server data, but createFromGlobals() is used internally)
$app = new App([], []);

// Define your routes here (see examples below)

// Run the app with the current request URI
$app->run($_SERVER['REQUEST_URI']);
```

## Defining Routes

Routes are defined using methods like `get()`, `post()`, and `all()`. Each route takes a path and a handler function or class method.

Handlers can be:
- Anonymous functions: `function($request, $response) { ... }`
- Class methods: `[MyClass::class, 'methodName']`

### Simple GET Route

```php
$app->get('/', function($request, $response) {
    return $response->respond('<h1>Hello, World!</h1>');
});
```

This creates a route for the root path that responds with HTML.

### Route with Parameters

```php
$app->get('/user/(?<id>\d+)', function($request, $response) {
    $userId = $request->params->get('id');
    return $response->respond("<h1>User ID: $userId</h1>");
});
```

Parameters are defined using named regex groups (?<name>pattern) and are captured and available via `$request->params`.

### POST Route

```php
$app->post('/submit', function($request, $response) {
    $data = $request->request->all(); // Get POST data
    // Process data...
    return $response->json(['status' => 'success']);
});
```

### Handling All Methods

```php
$app->all('/api/data', function($request, $response) {
    // Handle any HTTP method
    return $response->json(['method' => $request->getMethod()]);
});
```

## Chaining Routes

You can chain methods for the same path:

```php
$app->route('/user')
    ->get(function($request, $response) {
        // Handle GET /user
    })
    ->post(function($request, $response) {
        // Handle POST /user
    });
```

## Using Middleware

Middleware runs before your route handlers. Use `middleware()` to add them.

### Global Middleware (runs on all routes)

```php
$app->middleware(function($request, $response) {
    // Global middleware logic, e.g., authentication
    if (!$request->session('user')) {
        return $response->redirect('/login');
    }
});
```

### Route-Specific Middleware

```php
$app->middleware('/admin/*', function($request, $response) {
    // Only for /admin/* paths
    if (!$request->session('admin')) {
        return $response->redirect('/login');
    }
});
```

## Response Methods

Crane provides convenient methods for different response types:

- `$response->respond($html)`: Send HTML
- `$response->json($data)`: Send JSON
- `$response->render($template, $context)`: Render a template
- `$response->redirect($url)`: Redirect
- `$response->download($file)`: Download a file

## Request Handling

Access request data easily:

- `$request->params`: Route parameters
- `$request->query`: Query string parameters
- `$request->request`: POST data
- `$request->session($key)`: Session data
- `$request->cookie($key)`: Cookies

## Serving Static Files

Create a `public/` directory in your project root for static assets. Configure static file serving:

```php
$app->static('/assets', 'public/assets');
```

This maps `/assets` URLs to the `public/assets` directory.

## Complete Example

```php
<?php

require_once 'vendor/autoload.php';

use Crane\Router\App;

$app = new App([], []);

// Global middleware
$app->middleware(function($request, $response) {
    // Add security headers
    $response->headers->set('X-Frame-Options', 'DENY');
});

// Routes
$app->get('/', function($request, $response) {
    return $response->respond('<h1>Welcome to Crane!</h1>');
});

$app->get('/user/(?<id>\d+)', function($request, $response) {
    $id = $request->params->get('id');
    return $response->json(['user_id' => $id]);
});

$app->post('/login', function($request, $response) {
    $username = $request->request->get('username');
    $password = $request->request->get('password');
    // Authenticate...
    $request->session('user', $username);
    return $response->redirect('/dashboard');
});

$app->get('/admin/users/(?<id>\d+)', [AdminController::class, 'showUser']);

// Static files (ensure public/css and public/js directories exist)
$app->static('/css', 'public/css');
$app->static('/js', 'public/js');

// Run the app
$app->run($_SERVER['REQUEST_URI']);
```

## Running Your App

1. Ensure your web server (Apache/Nginx) points to `index.php` for all requests.
2. For Apache, add a `.htaccess` file:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

3. Access your app in the browser!

## Template Rendering

Crane provides a powerful template engine for rendering dynamic HTML. Create a `views/` directory in your project root to store templates. Templates use a syntax similar to other templating engines.

### Basic Usage

Use `$response->render($template, $context)` to render a template:

```php
$app->get('/profile', function($request, $response) {
    $user = ['name' => 'John', 'age' => 30];
    return $response->render('profile.html', ['user' => $user]);
});
```

### Template Syntax

#### Variables

Use `{{ variable }}` to output variables:

```html
<h1>Hello, {{ user.name }}!</h1>
<p>Age: {{ user.age }}</p>
```

#### Paths

Access nested properties with dot notation:

```html
{{ user.address.city }}
```

#### Functions

Call functions or static methods with `{% = function %}`:

```html
{% = strtoupper user.name %}
{% = MyClass::helper user.age %}
```

#### Template Inheritance

Extend base templates:

Base template (base.html):

```html
<html>
<head><title>{% block title %}Default Title{% endblock %}</title></head>
<body>
    {% block content %}{% endblock %}
</body>
</html>
```

Child template:

```html
{% extends 'base.html' %}

{% block title %}My Page{% endblock %}

{% block content %}
<h1>Welcome</h1>
<p>This is my page.</p>
{% endblock %}
```

#### Includes

Include other templates:

```html
{% include 'header.html' %}
```

### Template Class

You can also use the Template class directly:

```php
use Crane\Template\Template;

$template = new Template('views/profile.html');
$content = $template->template(['user' => $user]);
```

## Sessions

Crane handles sessions using PHP's built-in session management.

### Starting Sessions

Sessions are automatically started when accessing session data.

### Setting Session Data

Use `$response->session($key, $value)` to set session data:

```php
$app->post('/login', function($request, $response) {
    // Authenticate user
    $response->session('user_id', 123);
    $response->session('username', 'john');
    return $response->redirect('/dashboard');
});
```

### Getting Session Data

Use `$request->session($key)` to get session data:

```php
$app->get('/dashboard', function($request, $response) {
    $userId = $request->session('user_id');
    if (!$userId) {
        return $response->redirect('/login');
    }
    return $response->render('dashboard.html', ['user_id' => $userId]);
});
```

### Getting All Session Data

Use `$request->session()` without parameters:

```php
$sessions = $request->session();
```

### Destroying Sessions

To destroy a session, use PHP's session_destroy():

```php
session_destroy();
```

## Cookies

Crane provides methods to set and get cookies.

### Setting Cookies

Use `$response->cookie($name, $value, $options)` to set a cookie:

```php
$app->post('/set-preference', function($request, $response) {
    $response->cookie('theme', 'dark', time() + 3600, '/', '', false, true);
    return $response->json(['status' => 'preference set']);
});
```

Parameters:

- $name: Cookie name
- $value: Cookie value
- $expires_or_options: Expiration time or options array
- $path: Path
- $domain: Domain
- $secure: HTTPS only
- $httponly: HTTP only

### Getting Cookies

Use `$request->cookie($key)` to get a cookie:

```php
$app->get('/profile', function($request, $response) {
    $theme = $request->cookie('theme') ?? 'light';
    return $response->render('profile.html', ['theme' => $theme]);
});
```

### Getting All Cookies

Use `$request->cookie()` without parameters:

```php
$cookies = $request->cookie();
```





## Contact  
**Email** : paul.contrib@gmail.com