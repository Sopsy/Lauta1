<?php
declare(strict_types=1);

namespace TVGuide\Module\Sources;

use DateInterval;
use DateTimeImmutable;
use TVGuide\Contract\Source;

final class ViacomSource implements Source
{
    private $channels;
    private $url;

    public function __construct(string $url){
        $this->url = $url;
        $this->channels = [
            #'nick_jr_nordics' => 'fin',
            'vh1_euro_classic' => 'fin',
            'vh1_euro' => 'fin',
            'paramount_network_finland' => 'fin',
            'mtv_rocks_uk' => 'uk',
            'mtv_live_uk' => 'uk',
            'mtv_hits_uk' => 'uk',
            'mtv_finland' => 'fin',
            'mtv_dance_uk' => 'uk',
            'nick_jr_global' => 'fin',
            #'nicktoons_commercial' => 'fin',
            #'nick_jr_netherlands' => 'fin',
            #'nicktoons_global' => 'fin',
            #'nick_jr_commercial_poland' => 'fin',

        ];
    }

    public function sources(): array
    {
        $sources = [];
        for ($day = 0; $day <= 20; ++$day){
            $date = (new DateTimeImmutable())->add(new DateInterval('P' . $day . 'D'))->format('Ymd');
            foreach ($this->channels as $channel => $language) {
                $sources[] = $this->url . $channel . '/xmltvlegal/' . $language . '/' . $date . '.xml';
            }
        }
        return $sources;
    }
}