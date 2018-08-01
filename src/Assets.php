<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 23.07.18
 * Time: 16:00
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Template;

use Alpipego\AWP\Assets\AssetsCollectionInterface;
use Alpipego\AWP\Assets\Script;
use Alpipego\AWP\Assets\Style;

class Assets
{
    private $paths;
    private $ext;
    private $collection;
    private $registered = ['styles' => [], 'scripts' => []];

    public function __construct(AssetsCollectionInterface $collection)
    {
        $this->collection = $collection;
        $this->paths      = [
            'styles'      => apply_filters(
                'awp/template/pattern/path/styles',
                get_stylesheet_directory() . '/css/patterns'
            ),
            'styles_uri'  => apply_filters(
                'awp/template/pattern/path/styles_uri',
                get_stylesheet_directory_uri() . '/css/patterns'
            ),
            'scripts'     => apply_filters(
                'awp/template/pattern/path/scripts',
                get_stylesheet_directory() . '/js/patterns'
            ),
            'scripts_uri' => apply_filters(
                'awp/template/pattern/path/scripts_uri',
                get_stylesheet_directory_uri() . '/js/patterns'
            ),
        ];
        $this->ext        = [
            'styles'  => 'css',
            'scripts' => 'js',
        ];

        add_action('wp_enqueue_scripts', [$this->collection, 'run']);
    }

    public function addScript(string $pattern, string $name)
    {
        $script = $this->getAsset($pattern, $name, 'scripts');
        if (empty($script) || !file_exists($script)) {
            return;
        }

        $handle = sanitize_key(sprintf('%s/%s', $this->paths[$pattern . 's'], $name));
        $this->collection->add(
            (new Script($handle))
                ->src(str_replace($this->paths['scripts'], $this->paths['scripts_uri'], $script))
                ->deps((array)apply_filters('awp/template/pattern/scripts/dep', [], $name, $pattern))
                ->ver((string)filemtime($script))
                ->in_footer(true)
                ->prio((string)apply_filters('awp/template/pattern/scripts/prio', 'defer', $name, $pattern))
        );

        $this->registered['scripts'][sprintf('%s/%s.php', $this->paths[$pattern . 's'], $name)] = $handle;
    }

    private function getAsset(string $pattern, string $name, string $type) : string
    {
        $assets = [
            'min'     => sprintf(
                '%s/%s/%s.min.%s',
                $this->paths[$type],
                $this->paths[$pattern . 's'],
                $name,
                $this->ext[$type]
            ),
            'default' => sprintf(
                '%s/%s/%s.%s',
                $this->paths[$type],
                $this->paths[$pattern . 's'],
                $name,
                $this->ext[$type]
            ),
        ];
        $assets = array_filter($assets, 'file_exists');

        if (empty($assets)) {
            return '';
        }

        if (count($assets) === 1) {
            return array_shift($assets);
        }

        return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? $assets['default'] : $assets['min'];
    }

    public function addStyle(string $pattern, string $name)
    {
        $style = $this->getAsset($pattern, $name, 'styles');
        if (empty($style) || !file_exists($style)) {
            return;
        }

        $handle = sanitize_key(sprintf('%s/%s', $this->paths[$pattern . 's'], $name));
        $this->collection->add(
            (new Style($handle))
                ->src(str_replace($this->paths['styles'], $this->paths['styles_uri'], $style))
                ->deps(apply_filters('awp/template/pattern/styles/dep', [], $name, $pattern))
                ->prio((string)apply_filters('awp/template/pattern/scripts/prio', 'defer', $name, $pattern))
        );

        $this->registered['styles'][sprintf('%s/%s.php', $this->paths[$pattern . 's'], $name)] = $handle;
    }

    public function setPaths($paths) : array
    {
        return $this->paths = array_merge($paths, $this->paths);
    }
}
