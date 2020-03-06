<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 12.08.2017
 * Time: 08:47
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Template;

use Alpipego\AWP\Template\Exception\InvalidDataException;
use Alpipego\AWP\Template\Exception\TemplateNotFoundException;

final class Template implements TemplateInterface
{
    private $template;
    private $data;
    private $name;
    private $transpose;
    private $patterns = [];

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
    public function __construct(array $template, string $name = '', array $data = [], TransposeInterface $transpose = null)
    {
        $this->template  = $this->locateTemplate($template);
        $this->data      = $data;
        $this->name      = $this->resolveName($name);
        $this->transpose = $transpose ?? new Transpose();
    }

    private function locateTemplate(array $template) : string
    {
        $tmpl = locate_template($template);
        if ( ! $tmpl) {
            throw new TemplateNotFoundException($template);
        }

        return $tmpl;
    }

    private function resolveName(string $name) : string
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
        $method = 'render' . (is_null($data) ? 'Js' : 'Php');
        $this->$method($this->template, $data);
    }

    public function __toString()
    {
        return $this->return();
    }

    public function return(array $data = []) : string
    {
        // $data array is accessible in template
        $data = array_merge($this->data, $data);

        ob_start();
        require $this->template;

        return ob_get_clean();
    }

    public function getName() : string
    {
        return $this->name;
    }

    private function renderJs(string $tmpl, array $data = null)
    {
        $tmplString = file_get_contents($tmpl);
        $patterns   = $this->findPatterns($this->data);
        foreach ($patterns as $pattern) {
            $pattern->render();
        }
        wp_enqueue_script('wp-util');
        add_action('wp_footer', function () use ($tmplString) {
            ?>
            <script type="text/html" id="<?= $this->name; ?>">
                <?= $this->transpose->transpose($tmplString); ?>
            </script>
            <?php
        });
    }

    private function findPatterns(array $data)
    {
        array_walk_recursive($data, function ($value) {
            if (is_array($value)) {
                $this->patterns[] = $this->findPatterns($value);
            }

            if ($value instanceof self) {
                $this->patterns[$value->getTemplate()] = $value;
            }
        });

        return $this->patterns;
    }

    public function getTemplate() : string
    {
        return $this->template;
    }

    private function renderPhp(string $tmpl, array $data)
    {
        $data = array_merge($this->data, $data);
        if (is_null($data)) {
            throw new InvalidDataException;
        }
        require $tmpl;
    }
}
