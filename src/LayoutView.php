<?php

namespace Francerz\WebappRenderUtils;

class LayoutView
{
    private $layout;

    public function __construct(Layout $layout)
    {
        $this->layout = $layout;
    }

    public function section(string $sectionName)
    {
        $section = $this->layout->getSection($sectionName);
        if (is_null($section)) {
            return;
        }
        fseek($section, 0);
        while (!feof($section)) {
            echo fread($section, 4096);
        }
    }
}
