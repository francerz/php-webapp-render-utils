<?php

namespace Francerz\WebappRenderUtils;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class View
{
    private $path;
    private $vars;

    public function __construct(string $path, array $vars = [])
    {
        $this->path = $path;
        $this->vars = $vars;
    }

    public function include(string $path, array $vars = [])
    {
        $view = $this;
        $vars = array_merge($this->vars, $vars);
        (function () use ($view, $path, $vars) {
            extract($vars);
            include($path);
        })();
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

        return $streamFactory->createStreamFromResource($tmpfile);
    }
}
