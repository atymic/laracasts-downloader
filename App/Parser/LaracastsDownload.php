<?php

namespace App\Parser;

use App\Html\Parser;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Ubench;

class LaracastsDownload
{
    /** @var Client */
    private $client;

    /** @var CookieJar */
    private $cookie;

    /** @var Ubench */
    private $bench;

    /** @var $html */
    private $html;

    public function __construct($html, Ubench $bench)
    {
        $this->client = new Client();
        $this->cookie = new CookieJar();
        $this->bench = $bench;
        $this->html = $html;
    }


    public function getDownloadUrl()
    {
        $downloadLink = Parser::getDownloadLink($this->html);
        $vimeoUrl = $this->getRedirectUrl($downloadLink);
        return $this->getRedirectUrl($vimeoUrl);
    }

    /**
     * Helper to get the Location header.
     *
     * @param $url
     *
     * @return string
     */
    private function getRedirectUrl($url)
    {
        $response = $this->client->get($url, [
            'cookies' => $this->cookie,
            'allow_redirects' => false,
            'verify' => false,
        ]);

        return $response->getHeader('Location');
    }
}
