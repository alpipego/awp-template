<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 24.03.2018
 * Time: 10:54
 */

namespace Alpipego\AWP\Template;

function pattern(array $patterns, $context = 'php')
{
    return implode(' ', $patterns);
}
