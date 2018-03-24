<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 24.03.2018
 * Time: 07:04
 */
declare(strict_types=1);

namespace Alpipego\AWP\Template;

interface PatternFactoryInterface
{
    public function buildAtom(string $name, array $data = [], array $templates = []): TemplateInterface;

    public function buildMolecule(string $name, array $data = [], array $templates = []): TemplateInterface;

    public function buildOrganism(string $name, array $data = [], array $templates = []): TemplateInterface;

    public function buildTemplate(string $name, array $data = [], array $templates = []): TemplateInterface;

    public function buildPage(string $name, array $data = [], array $templates = []): TemplateInterface;
}
