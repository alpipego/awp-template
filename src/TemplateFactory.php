<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 12.08.2017
 * Time: 10:17
 */
declare(strict_types = 1);

namespace WPHibou\Template;

class TemplateFactory
{
    public function build(string $template, string $name, array $data = []) : Template
    {
        return new Template($template, $name, $data);
    }
}
