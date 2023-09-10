<?php
declare(strict_types=1);

namespace TVGuide\Contract;

interface Identifiable
{
    /**
     * @return mixed - usually the primary key from database
     */
    public function id();
}