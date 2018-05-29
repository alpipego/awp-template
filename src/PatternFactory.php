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

    public function __construct(array $paths = [])
    {
        $this->paths = array_merge([
            'atoms'       => apply_filters('awp/template/pattern/path/atoms', '_atoms'),
            'molecules'   => apply_filters('awp/template/pattern/path/molecules', '_molecules'),
            'organisms'   => apply_filters('awp/template/pattern/path/organisms', '_organisms'),
            'templates'   => apply_filters('awp/template/pattern/path/templates', '_templates'),
            'pages'       => apply_filters('awp/template/pattern/path/pages', '_pages'),
            'data'        => apply_filters('awp/template/pattern/data', '_data'),
            'styles'      => apply_filters('awp/template/pattern/path/styles', get_stylesheet_directory()),
            'styles_uri'  => apply_filters('awp/template/pattern/path/styles_uri', get_stylesheet_directory_uri()),
            'scripts'     => apply_filters('awp/template/pattern/path/scripts', get_stylesheet_directory()),
            'scripts_uri' => apply_filters('awp/template/pattern/path/scripts_uri', get_stylesheet_directory_uri()),
        ], $paths);
    }

    public function buildAtom(string $name, array $data = [], array $templates = []) : TemplateInterface
    {
        return $this->build('atom', $templates, $name, $data);
    }

    private function build(string $type, array $templates, string $name, array $data = []) : TemplateInterface
    {
        if (apply_filters('awp/template/pattern/styles', true)) {
            $this->enqueueStyles($type, $name);
        }

        if (apply_filters('awp/template/pattern/scripts', true)) {
            $this->enqueueScripts($type, $name);
        }

        array_unshift($templates, sprintf('%s/%s.php', $this->paths[$type . 's'], $name));
        $templates = array_unique($templates);
        $data      = $this->getData($type, $name, $data);
        $template  = new Template($templates, $name, $data);

        return $template;
    }

    private function enqueueStyles(string $type, string $name)
    {
        $style = sprintf('%s/%s/%s.css', $this->paths['styles'], $this->paths[$type . 's'], $name);
        //                echo $style . '<br>';
        if (file_exists($style)) {
            add_action('wp_enqueue_scripts', function () use ($style, $type, $name) {
                wp_enqueue_style(
                    sprintf('%s/%s', $this->paths[$type . 's'], $name),
                    str_replace($this->paths['styles'], $this->paths['styles_uri'], $style),
                    apply_filters('awp/template/pattern/styles/dep', [], $name, $type)
                );
            });
        }
    }

    private function enqueueScripts(string $type, string $name)
    {
        $scripts = [
            'min'     => sprintf('%s/%s/%s.min.js', $this->paths['scripts'], $this->paths[$type . 's'], $name),
            'default' => sprintf('%s/%s/%s.js', $this->paths['scripts'], $this->paths[$type . 's'], $name),
        ];
        $scripts = array_filter($scripts, 'file_exists');

        if (empty($scripts)) {
            return;
        }

        if (count($scripts) > 1) {
            $script = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? $scripts['default'] : $scripts['min'];
        } else {
            $script = array_shift($scripts);
        }

        add_action('wp_enqueue_scripts', function () use ($script, $type, $name) {
            wp_enqueue_script(
                sprintf('%s/%s', $this->paths[$type . 's'], $name),
                str_replace($this->paths['scripts'], $this->paths['scripts_uri'], $script),
                apply_filters('awp/template/pattern/scripts/dep', [], $name, $type),
                filemtime($script),
                true
            );
        });
    }

    private function getData(string $type, string $name, array $data) : array
    {
        $dataFile = locate_template([sprintf('%s/%s/%s.php', $this->paths['data'], $this->paths[$type . 's'], $name)]);
        if ( ! empty($dataFile)) {
            $data = array_merge((array)require $dataFile, $data);
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
