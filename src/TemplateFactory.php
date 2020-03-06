<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 12.08.2017
 * Time: 10:17
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Template;

final class TemplateFactory implements TemplateFactoryInterface
{
    public static function build(array $template, string $name = '', array $data = []) : TemplateInterface
    {
        return new Template($template, $name, $data);
    }
}
