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

    private function addAsset(string $pattern, string $name, string $type)
    {
        $assets = $this->getAsset($pattern, $name, $type);
        if (empty($assets) || ! file_exists($assets)) {
            return;
        }

        $handle = sanitize_key(sprintf('%s/%s', $this->paths[$pattern . 's'], $name));
        $action = (string)apply_filters('awp/template/pattern/' . $type . '/action', 'add', $name, $pattern);
        if ( ! in_array($action, ['add', 'inline', 'update', 'remove'], true)) {
            $action = 'add';
        }
        $this->collection->$action(
            ($type === 'scripts' ? new Script($handle) : new Style($handle))
                ->src(str_replace($this->paths[$type], $this->paths[$type . '_uri'], $assets))
                ->deps((array)apply_filters('awp/template/pattern/' . $type . '/dep', [], $name, $pattern))
                ->ver((string)filemtime($assets))
                ->in_footer((bool)apply_filters('awp/template/pattern/' . $type . '/in_footer', $type === 'scripts', $name, $pattern))
                ->prio((string)apply_filters('awp/template/pattern/' . $type . '/prio', 'defer', $name, $pattern))
        );

        $this->registered[$type][sprintf('%s/%s.php', $this->paths[$pattern . 's'], $name)] = $handle;
    }

    public function addScript(string $pattern, string $name)
    {
        $this->addAsset($pattern, $name, 'scripts');
    }

    public function addStyle(string $pattern, string $name)
    {
        $this->addAsset($pattern, $name, 'styles');
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

    public function setPaths($paths) : array
    {
        return $this->paths = array_merge($paths, $this->paths);
    }
}
