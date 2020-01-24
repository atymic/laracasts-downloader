<?php

namespace App\Parser;

use Ubench;

interface ParserInterface
{
    /**
     * @param        $html
     * @param Ubench $bench
     */
    public function __construct($html, Ubench $bench);

    /**
     * @return string|null
     */
    public function getDownloadUrl();
}
