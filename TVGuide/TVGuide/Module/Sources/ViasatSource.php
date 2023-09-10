<?php
declare(strict_types=1);

namespace TVGuide\Module\Sources;

use DateInterval;
use DateTimeImmutable;
use TVGuide\Contract\Source;

final class ViasatSource implements Source
{
    private $channels;
    private $version;
    private $key;
    private $url;

    public function __construct(string $url, string $version, string $key)
    {
        $this->url = $url;
        $this->version = $version;
        $this->key = $key;
        $this->channels = [
            'jalkapallo.hd',
            'jaakiekko.hd',
            'urheilu.hd',
            'sport',
            'sport.premium',
            'fotboll',
            'hockey',
            'golf',
            'esport.tv',
            'ultra.hd',
            'premiere',
            'film.action',
            'film.hits',
            'film.family',
            'history',
            'explore',
            'nature',
            'extra.1',
            'extra.2',
            'extra.3',
            'extra.4',
            'extra.5'
        ];
    }

    public function sources(): array
    {
        $sources = [];
        for ($day = 0; $day <= 20; ++$day) {
            $date = (new DateTimeImmutable())->add(new DateInterval('P' . $day . 'D'))->format('Y-m-d');
            foreach ($this->channels as $channel) {
                $sources[] = $this->url . $this->version . '?key=' . $this->key . '&date=' . $date . '&channelId=fi.viasat.' . $channel;
            }
        }

        return $sources;
    }
}