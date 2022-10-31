<?php

namespace Francerz\WebappRenderUtils;

class Header
{
    private $header;
    /** @var string[] */
    private $content = [];

    /**
     * @param string $header
     * @param string|string[] $content
     */
    public function __construct(string $header, $content = [])
    {
        $this->setHeader($header);
        $this->setContentFromHeader($header);
        $this->addContent($content);
    }

    private function setHeader(string $header)
    {
        $colonPos = strpos($header, ':');
        if ($colonPos === false) {
            $this->header = $header;
            return;
        }
        $this->header = substr($header, 0, $colonPos);
    }

    private function setContentFromHeader(string $header)
    {
        $colonPos = strpos($header, ':');
        if ($colonPos === false) {
            return;
        }
        $content = explode(',', substr($header, $colonPos + 1));
        if ($content === false) {
            return;
        }
        $this->content = array_map('trim', $content);
    }

    /**
     * @param string|string[] $content
     * @return void
     */
    private function addContent($content)
    {
        if (is_array($content)) {
            foreach ($content as $v) {
                $this->addContent($v);
            }
            return;
        }
        array_push($this->content, ...array_map('trim', explode(',', $content)));
    }

    public function getName()
    {
        return $this->header;
    }

    public function getContent()
    {
        return $this->content;
    }
}
