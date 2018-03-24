<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 12.08.2017
 * Time: 08:47
 */
declare(strict_types=1);

namespace Alpipego\AWP\Template;

final class Template implements TemplateInterface
{
    private $template;
    private $data;
    private $name;
    private $transpose;

    /**
     * Template constructor.
     *
     * @param array $template Uses `locate_template` to prioritize templates @see locate_template()
     * @param string $name Id passed to `wp.template`
     * @param array $data optional array of global data
     * @param TransposeInterface|null $transpose
     *
     * @internal param string $varName the name of the replaced variable
     */
    public function __construct(array $template, string $name, array $data = [], TransposeInterface $transpose = null)
    {
        $this->template  = $template;
        $this->data      = $data;
        $this->name      = $this->resolveName($name);
        $this->transpose = $transpose ?? new Transpose();
    }

    private function resolveName(string $name): string
    {
        $name = str_replace(DIRECTORY_SEPARATOR, '-', $name);
        if (strpos($name, 'tmpl-') === 0) {
            return $name;
        }
        if (strpos($name, '-') === 0) {
            return 'tmpl' . $name;
        }

        return 'tmpl-' . $name;
    }

    /**
     * @uses renderJs()
     * @uses renderPhp()
     */
    public function render(array $data = null)
    {
        $tmpl = $this->locateTemplate();

        $method = 'render' . (is_null($data) ? 'Js' : 'Php');
        $this->$method($tmpl, $data);
    }

    private function locateTemplate(): string
    {
        $tmpl = locate_template($this->template);
        if (! $tmpl) {
            $this->exception('TemplateNotFoundException', $this->template);
        }

        return $tmpl;
    }

    private function exception(string $type, ... $data)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            throw new $type(...$data);
        }
    }

    public function return(array $data = []): string
    {
        // $data array is accessible in template
        $data = array_merge($this->data, $data);

        ob_start();
        require $this->locateTemplate();

        return ob_get_clean();
    }

    private function renderJs(string $tmpl, array $data = null)
    {
        $tmplString = file_get_contents($tmpl);
        ob_start();
        wp_enqueue_script('wp-util');
        add_action('wp_footer', function () use ($tmplString) {
            ?>
            <script type="text/html" id="<?= $this->name; ?>">
                <?= $this->transpose->transpose($tmplString); ?>
            </script>
            <?php
        });
    }

    private function renderPhp(string $tmpl, array $data)
    {
        $data = array_merge($this->data, $data);
        if (is_null($data)) {
            $this->exception('InvalidDataException');
        }
        require $tmpl;
    }

    public function __toString()
    {
        return $this->return();
    }
}
