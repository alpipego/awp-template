<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 30.08.2017
 * Time: 23:33
 */
declare(strict_types = 1);

namespace WPHibou\Template;

interface TemplateFactoryInterface
{
    /**
     * @param array $template Uses `locate_template` to prioritize templates @see locate_template()
     * @param string $name Id passed to `wp.template`
     * @param array $data Optional array of unchanging data
     *
     * @return TemplateInterface
     */
    public function build(array $template, string $name, array $data = []) : TemplateInterface;
}
