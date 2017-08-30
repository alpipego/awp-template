# Template
WordPress template loader for reusable templates for `wp.template` and `locate_template`

## Usage

Obtain instance of `Template`, either by instantiating it directly or using `TemplateFactory`'s `build` method.

```
/**
 * Template constructor.
 *
 * @param array $template Uses `locate_template` to prioritize templates @see locate_template()
 * @param string $name Id passed to `wp.template`
 * @param array $data optional array of unchanging data
 */
 public function __construct(array $template, string $name, array $data = [])
 ```

Pass an array (may be empty) to the `render` method on the `Template` to render php template. Don't pass anything to render a `wp.template`.
