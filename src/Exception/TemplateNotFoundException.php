<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 24.03.2018
 * Time: 06:17
 */
declare(strict_types=1);

namespace Alpipego\AWP\Template;

class TemplateNotFoundException extends \InvalidArgumentException
{
    public function __construct(array $template)
    {
        parent::__construct(sprintf('Templates %s cannot be found', implode(', ', $template[0])));
    }
}
