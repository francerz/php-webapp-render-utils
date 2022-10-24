<?php

namespace Francerz\WebappRenderUtils;

class ServerState
{
    private $code;
    private $headers;

    private static function currentHeaderLines()
    {
        if (PHP_SAPI !== 'cli') {
            return \headers_list();
        }

        if (function_exists('xdebug_get_headers')) {
            return \xdebug_get_headers();
        }
        return [];
    }

    /**
     * @param string[] $headerLines
     * @return string[][]
     */
    private static function parseHeaders(array $headerLines)
    {
        $headers = [];
        foreach ($headerLines as $header) {
            [$hname, $hcontent] = explode(':', $header, 2);
            $hcontent = array_map(function ($v) {
                return trim($v);
            }, explode(',', $hcontent));
            $headers[$hname] = $hcontent;
        }
        return $headers;
    }

    public function __construct()
    {
        $this->backup();
    }

    public function backup()
    {
        $this->code = http_response_code();
        $this->headers = static::currentHeaderLines();
    }

    public function clear()
    {
        if (headers_sent()) {
            return false;
        }
        http_response_code(200);
        header_remove();
        return true;
    }

    public function restore()
    {
        if (headers_sent()) {
            return false;
        }
        if (isset($this->code)) {
            \http_response_code($this->code);
        }
        if (isset($this->headers)) {
            header_remove();
            foreach ($this->headers as $header) {
                header($header);
            }
        }
        return true;
    }

    public function getBackCode()
    {
        return $this->code;
    }

    public function getBackHeaders()
    {
        return static::parseHeaders($this->headers ?? []);
    }

    public function getNewCode()
    {
        return http_response_code();
    }

    public function getNewHeaders()
    {
        // $backHeaders = static::parseHeaders($this->headers ?? []);
        $currentHeaders = static::parseHeaders(static::currentHeaderLines());

        return $currentHeaders;
    }
}
