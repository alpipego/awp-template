<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 24.03.2018
 * Time: 07:05
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Template;

class PatternFactory implements PatternFactoryInterface
{
    private $paths;
    private $assets;

    public function __construct(array $paths = [], Assets $assets = null)
    {
        $this->paths  = array_merge([
            'atoms'     => apply_filters('awp/template/pattern/path/atoms', '_atoms'),
            'molecules' => apply_filters('awp/template/pattern/path/molecules', '_molecules'),
            'organisms' => apply_filters('awp/template/pattern/path/organisms', '_organisms'),
            'templates' => apply_filters('awp/template/pattern/path/templates', '_templates'),
            'pages'     => apply_filters('awp/template/pattern/path/pages', '_pages'),
            'data'      => apply_filters('awp/template/pattern/data', '_data'),
        ], $paths);

        $assets->setPaths($this->paths);
        $this->assets = $assets;
    }

    public function buildAtom(string $name, array $data = [], array $templates = []) : TemplateInterface
    {
        return $this->build('atom', $templates, $name, $data);
    }

    private function build(string $type, array $templates, string $name, array $data = []) : TemplateInterface
    {
        if (apply_filters('awp/template/pattern/styles', !is_null($this->assets))) {
            $this->assets->addStyle($type, $name);
        }

        if (apply_filters('awp/template/pattern/scripts', !is_null($this->assets))) {
            $this->assets->addScript($type, $name);
        }

        array_unshift($templates, sprintf('%s/%s.php', $this->paths[$type . 's'], $name));
        $templates = array_unique($templates);
        $data      = $this->getData($type, $name, $data);
        $template  = new Template($templates, $name, $data);

        return $template;
    }

    private function getData(string $type, string $name, array $data) : array
    {
        $dataFile = locate_template([sprintf('%s/%s/%s.php', $this->paths['data'], $this->paths[$type . 's'], $name)]);
        if (!empty($dataFile)) {
            $data = array_merge($data, (array)require $dataFile);
        }

        return apply_filters('alpipego/awp/pattern/data', $data, $name);
    }

    public function buildMolecule(string $name, array $data = [], array $templates = []) : TemplateInterface
    {
        return $this->build('molecule', $templates, $name, $data);
    }

    public function buildOrganism(string $name, array $data = [], array $templates = []) : TemplateInterface
    {
        return $this->build('organism', $templates, $name, $data);
    }

    public function buildTemplate(string $name, array $data = [], array $templates = []) : TemplateInterface
    {
        return $this->build('template', $templates, $name, $data);
    }

    public function buildPage(string $name, array $data = [], array $templates = []) : TemplateInterface
    {
        return $this->build('page', $templates, $name, $data);
    }

    public function clone(string $template, array $data = []) : TemplateInterface
    {
        if (substr($template, -4, 4) !== '.php') {
            $template .= '.php';
        }

        return new Template([$template], $template, $data);
    }
}
