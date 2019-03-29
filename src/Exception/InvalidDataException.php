<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 24.03.2018
 * Time: 06:32
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Template\Exception;

class InvalidDataException extends \InvalidArgumentException
{
    protected $message = 'Please provide the data for the template';
}
