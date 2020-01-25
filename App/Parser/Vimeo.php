<?php

namespace App\Parser;

use App\Exceptions\NoDownloadLinkException;
use App\Utils\Utils;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Ubench;

class Vimeo
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
        $this->bench = $bench;
        $this->html = $html;
    }


    public function getDownloadUrl()
    {
        $vimeoId = $this->getVimeoVideoId();

        $vimeoPage = $this->client->get(sprintf('https://player.vimeo.com/video/%s', $vimeoId), [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36',
                'Referer' => 'https://laracasts.com/series/build-a-laravel-app-with-tdd/episodes/1',
            ],
        ]);

        $versions = $this->getVideoOptions((string) $vimeoPage->getBody());

        if (count($versions)) {
            Utils::writeln(sprintf('Found %d versions, using %dp', count($versions), array_keys($versions)[0]));
            return reset($versions);
        }

        throw new NoDownloadLinkException('Found no versions in vimeo embed');
    }

    private function getVimeoVideoId()
    {
        if (preg_match('/video-player.*?vimeo-id="(\d+)"/', $this->html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getVideoOptions($playerHtml)
    {
        $versions = [];

        if (!preg_match('/var config\s*=\s*(?:({.+?})|_extend\([^,]+,\s+({.+?})\));/', $playerHtml, $matches)) {
            Utils::writeln('Failed to fetch vimeo versions');
            return [];
        }

        $config = json_decode($matches[1], true);

        $files = $config['request']['files']['progressive'] ?? [];

        foreach ($files as $file) {
            $versions[$file['height']] = $file['url'];
        }

        krsort($versions);

        return $versions;
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
