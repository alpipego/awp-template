<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 12.08.2017
 * Time: 08:47
 */
declare(strict_types = 1);

namespace WPHibou\Template;

class Template
{
    private $template;
    private $data;
    private $name;

    public function __construct(string $template, string $name, array $data = [])
    {
        $this->template = $template;
        $this->data     = $data;
        $this->name     = $this->resolveName($name);
    }

    private function resolveName(string $name) : string
    {
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
    public function render(string $context, array $data = [])
    {
        $tmpl = locate_template($this->template);
        if (! $tmpl) {
            $this->exception(sprintf('Template %s cannot be found', $this->template));
        }

        if (! in_array($context, ['php', 'js'], true)) {
            $this->exception(sprintf('%s is not a recognized language. Please supply eiter "php" or "js".', $context));
        }

        $method = 'render' . ucfirst(strtolower($context));
        $this->$method($tmpl, $data);
    }

    private function exception(string $msg)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            throw new \Exception($msg);
        }
    }

    private function renderJs(string $tmpl, array $data)
    {
        $tmplString = file_get_contents($tmpl);
        ob_start();
        // if there is a condition eval php code
        if (preg_match('/<\?php\h+if/', $tmplString)) {
            $php = $this->eval($tmplString);
            eval('?>' . $php);
        } else {
            // $data array is used in template
            $data = $this->parseData($tmplString);
            include $tmpl;
        }
        $tmplOutput = ob_get_clean();

        wp_enqueue_script('wp-util');
        add_action('wp_footer', function () use ($tmplOutput) {
            ?>
            <script type="text/html" id="<?= $this->name; ?>">
                <?= $tmplOutput; ?>
            </script>
            <?php
        });
    }

    private function eval($tmplString) : string
    {
        // remove multiline php tags
        $tmplString = preg_replace('/<\?php\v.+?\?>/s', '', $tmplString);
        // replace php $data[] calls with data.
        $tmplString = preg_replace('/\$data\[\h*[\'"]([^\'"]+)[\'"]\h*\]/', 'data.$1', $tmplString);
        // replace <?php echo and <?= with {{ and close them respectively
        $tmplString = preg_replace('/<\?(?:=|php\h+echo)\h+(data[^;]+);\h*\?>/', '{{$1}}', $tmplString);
        // replace php if with template ifs
        $tmplString = preg_replace(
            '/<\?php\h+if\h*\(\h*(.+?)h*\)\h*(?:{|:)\h*\?>(.+?)<\?php\h*(?:}|endif;)\h*\?>/s',
            '<# if ($1) { #>$2<# } #>',
            $tmplString
        );

        return trim($tmplString);
    }

    private function parseData($tmplString) : array
    {
        preg_match_all('/<\?(?:=|php)\h+\$data\[[\'"]([^\'"]+)[\'"]\]\h*;\h*\?>/', $tmplString, $strings);
        $indexes = $strings[1] ?? [];
        $data    = array_combine($indexes, $indexes);
        array_walk($data, function (&$value) {
            $value = '{{data.' . $value . '}}';
        });

        return $data;
    }

    private function renderPhp(string $tmpl, array $data)
    {
        $data = array_merge($this->data, $data);
        if (empty($data)) {
            $this->exception('Please provide the data for the template');
        }
        include $tmpl;
    }
}
