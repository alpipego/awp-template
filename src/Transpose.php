<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 05.11.2017
 * Time: 20:02
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Template;

final class Transpose implements TransposeInterface
{
    private $string = '';

    public function transpose(string $templateString, bool $complex = false) : string
    {
        $this->string = $templateString;
        $this->housekeeping()->parsePatterns()->parseConditions()->parseForeach()->parseVariables();

        return $this->string;
    }

    private function parseVariables() : self
    {
        $this->string = preg_replace_callback(
            '/
                (?<opening_tag><\?(?:=|php\h+echo)\h+)?
                \$(?<variable>\w+)
                (?:
                    (?:\[\h*[\'"](?<index>[^\'"]+)[\'"]\h*\])?
                    (?<complex_index>\[.+?\])?
                )
                (?<closing_tag>;?\h*\?>)?
                /ix',
            function (array $matches) {
                $opening = $closing = '';

                if (!empty($matches['opening_tag']) && !empty($matches['closing_tag'])) {
                    $opening = '{{{ ';
                    $closing = ' }}}';
                }

                $variable = empty($matches['index']) ? 'data' : $matches['variable'];
                $index    = '.' . (empty($matches['index']) ? $matches['variable'] : $matches['index']);

                if (!empty($matches['complex_index'])) {
                    $complex = preg_replace_callback(
                        '/\[\h*(?:\$(?<before>\w+?)\h*\.)?\h*(?<string>.+?)\h*(?:\.\h*\$(?<after>\w+?)\h*)?\]/i',
                        function (array $matches) {
                            $before = isset($matches['before']) && !empty($matches['before'])
                                ? $matches['before'] . ' + '
                                : '';
                            $after  = isset($matches['after']) && !empty($matches['after'])
                                ? ' + ' . $matches['after']
                                : '';

                            return sprintf('[%s%s%s]', $before, $matches['string'], $after);
                        },
                        $matches['complex_index']
                    );

                }

                return sprintf('%s%s%s%s%s', $opening, $variable, $index, $complex ?? '', $closing);
            },
            $this->string
        );

        return $this;
    }

    private function parseForeach() : self
    {
        $this->string = preg_replace_callback(
            '/
                    <\?php\h+foreach\h*\(
                        (?<array>[^\h]+?)\h+as\h*
                        (?:\$(?<key>[^\h]+?)\h+=>)?\h+
                        \$(?<value>[^\h)]+?)\h*\)\h*
                        (?:{|:)\h*\?>
                    /ixs',
            function (array $matches) : string {
                $key = !empty($matches['key']) ? $matches['key'] : 'index';

                return "<# _.each({$matches['array']}, function({$matches['value']}, {$key}, {$matches['array']}) { #>";
            },
            $this->string
        );

        $this->string = preg_replace('/<\?php\h+(?:endforeach;|})\h*\?>/s', '<# } #>', $this->string);

        return $this;
    }

    private function parseConditions() : self
    {
        $this->string = preg_replace(
            '/<\?php\h+if\h*\(\h*([^\h]+?)h*\)\h*(?:{|:)\h*\?>/is',
            '<# if ($1) { #>',
            $this->string
        );

        $this->string = preg_replace(
            '/<\?php\h+(?:}\h*)?else\h*(?:{|:)\h+\?>/is',
            '<# } else { #>',
            $this->string
        );

        $this->string = preg_replace(
            '/<\?php\h+(?:endif;|})\h*\?>/is',
            '<# } #>',
            $this->string
        );

        return $this;
    }

    private function parsePatterns() : self
    {
        $this->string = preg_replace(
            '/<\?(?:=|php\h+echo)\h+?pattern\(\h*?(?:\.{3})?\$[^\'"]+[\'"]([^\'"]+)[\'"]]\h*?\);\h*\?>/',
            '<# $1() #>',
            $this->string
        );

        return $this;
    }

    private function housekeeping() : self
    {
        $this->string = preg_replace('/<\?php\v.+?\?>/s', '', $this->string);

        return $this;
    }
}
