<?php

namespace Francerz\WebappRenderUtils;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class View
{
    private $path;
    private $vars;
    /** @var Header[] */
    private $headers = [];
    /** @var Layout */
    private $layout = null;

    public function __construct(string $path, array $vars = [])
    {
        $this->path = $path;
        $this->vars = $vars;
    }

    public function getVars()
    {
        return $this->vars;
    }

    public function include(string $path, array $vars = [])
    {
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
