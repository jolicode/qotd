<?php

namespace App\Form\Model;

class QotdFilters
{
    /** @var list<string> */
    public array $authors = [];
    public ?bool $withImage = null;
    public ?bool $withVideo = null;
}
