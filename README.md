Webapp Renderer
=======================================

This library allows to create PSR-7 ResponseInterface objects for given
content.

Supported renderers are:
- Redirect
- String
- JSON
- CSV
- File
- cURL response
- View

Contents
---------------------------------------
- [Webapp Renderer](#webapp-renderer)
  - [Contents](#contents)
  - [Rendering with a callback](#rendering-with-a-callback)
  - [View Renderer](#view-renderer)
    - [Rendering a view](#rendering-a-view)
    - [Including subviews](#including-subviews)
    - [Using layouts](#using-layouts)
  - [Library API](#library-api)
    - [Class `Renderer`](#class-renderer)
    - [Class `View`](#class-view)
    - [Class `Layout`](#class-layout)
    - [Class `LayoutView`](#class-layoutview)

## Rendering with a callback

The `renderCallback` method allows executing arbitrary PHP code inside a 
callback function. All output generated (both body content and headers sent
with `header()`) will be captured into a PSR-7 `ResponseInterface` object.

This is useful for cases where third-party code writes directly to `stdout`
or sets headers without returning a `ResponseInterface`.

```php
$response = $renderer->renderCallback(function () {
    header('X-Test-Header: Value1', false);
    header('X-Test-Header: Value2', false);
    echo "Hello world!";
});

$response->getHeaders();
// ['X-Test-Header' => ['Value1', 'Value2']]

(string) $response->getBody();
// "Hello world!"
```

View Renderer
---------------------------------------

The most complex and powerful renderer is `View`, which allows to catch outputs
from a PHP file into a PSR-7 ResponseInterface object.

Catches any type of output like generated PDFs, Spreadsheets or HTML documents.

### Rendering a view

```php
$renderer->renderView('/home/index', [
    'title' => 'Hello World!',
    'paragraph' => 'Hello everyone.'
]);
```

View file `/home/index.php`:
```php
<html>
    <head>
        <title><?=$title?></title>
    </head>
    <body>
        <h1><?=$title?></h1>
        <p><?=$paragraph?></p>
    </body>
</html>
```

Renderer result:
```html
<html>
    <head>
        <title>Hello World!</title>
    </head>
    <body>
        <h1>Hello World!</h1>
        <p>Hello everyone.</p>
    </body>
</html>
```

### Including subviews

Include file `/includes/head.php`.
```php
<head>
    <title><?=$title?></title>
</head>
```

View file `/home/index.php`
```php
<html>
    <?=$view->include('includes/head')=?>
    <body>
        <h1><?=$title?></h1>
        <p><?=$paragraph?></p>
    </body>
</html>
```

### Using layouts

Layout file `/layouts/main.php`:
```php
<html>
    <head>
        <title><?=$title?></title>
    </head>
    <body>
        <?=$layout->section('content')?>
    </body>
</html>
```

View file `/home/index.php`:
```php
<?=$layout = $view->loadLayout('/layouts/main')?>
<?=$layout->startSection('content')?>
<h1><?=$title?></h1>
<p><?=$paragraph?</p>
<?=$layout->endSection()?>
```

> **NOTE**  
> When using Layouts, everything outside a section will be ignored, therefore
> will not be renderered.

Library API
---------------------------------------

### Class `Renderer`
```php
class Renderer
{
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ?StreamFactoryInterface $streamFactory = null
    );

    public function renderRedirect(
        $location,
        int $code = StatusCodeInterface::STATUS_FOUND,
        ?ResponseInterface $response = null
    ): ResponseInterface;

    public function render(
        $content,
        $contentType = 'text/plain',
        ?ResponseInterface $response = null
    ): ResponseInterface;

    public function renderJson(
        $data,
        ?ResponseInterface $response = null
    ): ResponseInterface;

    public function renderCsv(
        array $data,
        $filename = 'file.csv',
        ?CsvOptions $options = null,
        ?ResponseInterface $response = null
    ): ResponseInterface;

    public function renderFile(
        string $filepath,
        ?string $filename = null,
        bool $attachment = false,
        ?ResponseInterface $response = null
    ): ResponseInterface;

    public function renderCurlResponse(
        CurlHandle $curl,
        string $responseBody,
        ?ResponseInterface $response = null
    ): ResponseInterface;

    public function setViewsBasePath(?string $basepath);

    public function renderView(
        string $viewpath,
        array $data = [],
        ?ResponseInterface $response = null
    ): ResponseInterface;
}
```

### Class `View`

An instance of this class is automatically created when `$renderer->renderView()`
method is invoked. Instance is passed into views with name `$view`.

```php
class View
{
    /**
     * Renders a header directly into the view.
     * 
     * @param string $header Defines the header name or name and content.
     * @param string|string[] $content Defines the content of given header.
     */
    public function header(string $header, string|string[] $content = []);

    /**
     * Embeds another view inside the current view.
     * 
     * All `$view` variables are passed automatically into included view.
     * 
     * @param string $path Path to embed view.
     * @param mixed[] $vars Additional vars to send to embed view.
     */
    public function include(string $path, array $vars = []);

    /**
     * Loads a layout to current view.
     * 
     * By loading a layout only content inside a section will be renderer in
     * final output.
     * 
     * All `$view` variables are passsed automatically into layout.
     * 
     * @param string $path Path to layout view.
     * @param mixed[] $vars Additional vars to send to layout.
     * 
     * @return Layout
     */
    public function loadLayout(string $path, array $vars = []);
}
```

### Class `Layout`

An object of this class is created when `$view->loadLayout()` method is invoked.

This class contains methods to allow main view file to define sections.

```php
class Layout
{
    /**
     * This method starts a section block.
     */
    public function startSection(string $sectionName);

    /**
     * This method ends a section block.
     * 
     * This methods ends section by the last started.
     */
    public function endSection();
}
```

### Class `LayoutView`

An object of this classe is passed into Layout Views as `$layout`.

```php
class LayoutView
{
    /**
     * Embeds section into the layout.
     */
    public function section(string $sectionName);
}
```
