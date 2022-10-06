<?php

namespace Francerz\WebappRenderUtils;

class CsvOptions
{
    private $fieldSeparator;
    private $rowSeparator;
    private $withHeaders;

    public function __construct(string $rowSeparator = "\n", string $fieldSeparator = ',', $withHeaders = true)
    {
        $this->fieldSeparator = $fieldSeparator;
        $this->rowSeparator = $rowSeparator;
        $this->withHeaders = $withHeaders;
    }

    public function setRowSeparator(string $rowSeparator)
    {
        $this->rowSeparator = $rowSeparator;
    }

    public function getRowSeparator()
    {
        return $this->rowSeparator;
    }

    public function setFieldSeparator(string $fieldSeparator)
    {
        $this->fieldSeparator = $fieldSeparator;
    }

    public function getFieldSeparator()
    {
        return $this->fieldSeparator;
    }

    public function setWithHeaders($withHeaders = true)
    {
        $this->withHeaders = $withHeaders;
    }

    public function getWithHeaders()
    {
        return $this->withHeaders;
    }
}
