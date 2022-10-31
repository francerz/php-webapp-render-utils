<?php

namespace Francerz\WebappRenderUtils;

use RuntimeException;

class Layout
{
    private $view;
    private $path;
    private $vars;
    /** @var resource[] */
    private $sections = [];

    public function __construct(View $view, string $path, array $vars = [])
    {
        $this->view = $view;
        $this->path = $path;
        $this->vars = $vars;
    }

    public function getView()
    {
        return $this->view;
    }

    public function getSection(string $sectionName)
    {
        return $this->sections[$sectionName] ?? null;
    }

    public function startSection(string $sectionName)
    {
        $file = tmpfile();
        if ($file === false) {
            throw new RuntimeException('Failed to start section.');
        }
        $this->sections[$sectionName] = $file;
        ob_start(function ($buffer) use ($file) {
            fwrite($file, $buffer);
            return '';
        });
    }

    public function endSection()
    {
        ob_end_clean();
    }

    public function render()
    {
        $tmpfile = tmpfile();
        if ($tmpfile === false) {
            throw new RuntimeException("Failed to create temp file.");
        }

        ob_start(function (string $buffer) use ($tmpfile) {
            fwrite($tmpfile, $buffer);
            return '';
        }, 4096);

        $vars = array_merge(
            $this->view->getVars(),
            $this->vars,
            [
                'layout' => new LayoutView($this),
                'path' => $this->path,
                'view' => $this->view
            ]
        );
        (function () use ($vars) {
            extract($vars);
            include($path);
        })();

        ob_end_clean();
        fseek($tmpfile, 0);

        return $tmpfile;
    }
}
