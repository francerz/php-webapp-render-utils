<?php

namespace Francerz\WebappRenderUtils;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class View
{
    private $path;
    private $vars;
    /** @var string|null */
    private $viewsBasePath = null;
    /** @var Header[] */
    private $headers = [];
    /** @var Layout */
    private $layout = null;

    public function __construct(string $path, array $vars = [], ?string $viewsBasePath = null)
    {
        $this->viewsBasePath = $viewsBasePath;
        $this->path = $this->getViewPath($path);
        $this->vars = $vars;
    }

    public function getVars()
    {
        return $this->vars;
    }

    private function getViewPath(string $path)
    {
        if (isset($this->viewsBasePath)) {
            $path = $this->viewsBasePath . '/' . ltrim($path, '/');
        }
        $extPos = strpos($path, '.php', -4);
        if ($extPos === false) {
            $path .= '.php';
        }
        return $path;
    }

    public function include(string $path, array $vars = [])
    {
        $path = $this->getViewPath($path);
        $vars = array_merge($this->vars, $vars, ['view' => $this]);
        (function () use ($path, $vars) {
            extract($vars);
            include($path);
        })();
    }

    public function loadLayout(string $path)
    {
        if (isset($this->layout)) {
            throw new LayoutAlreadyLoadedException("This view already has a loaded layout.");
        }
        $path = $this->getViewPath($path);
        return ($this->layout = new Layout($this, $path));
    }

    /**
     * @param string $header
     * @param string|string[] $content
     */
    public function header(string $header, $content = [])
    {
        $this->headers[] = new Header($header, $content);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return StreamInterface
     */
    public function render(StreamFactoryInterface $streamFactory): StreamInterface
    {
        $view = $this;

        // Starts output buffering to tmpfile
        $tmpfile = tmpfile();
        if ($tmpfile === false) {
            throw new RuntimeException("Failed to create temp file.");
        }

        // Handle buffering and saves into tmpfile
        ob_start(function (string $buffer) use ($tmpfile) {
            fwrite($tmpfile, $buffer);
            return '';
        }, 4096);

        // Starts loading content.
        (function () use ($view) {
            extract($view->vars);
            include($view->path);
        })();

        // Ends Output buffering and restores resource.
        ob_end_clean();
        fseek($tmpfile, 0);

        if (isset($this->layout)) {
            $tmpfile = $this->layout->render();
        }

        return $streamFactory->createStreamFromResource($tmpfile);
    }
}
