<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 05.11.2017
 * Time: 20:01
 */
declare(strict_types=1);

namespace Alpipego\Template;

interface TransposeInterface
{
    public function transpose(string $tmplString, bool $complex = false) : string;
}
