<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 30.08.2017
 * Time: 23:31
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Template;

interface TemplateInterface
{
    public function render(array $data = null);
}
