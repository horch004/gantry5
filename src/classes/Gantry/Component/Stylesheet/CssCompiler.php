<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Stylesheet;

use Gantry\Component\Config\Config;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Framework\Gantry;
use Leafo\ScssPhp\Colors;
use RocketTheme\Toolbox\File\PhpFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

abstract class CssCompiler implements CssCompilerInterface
{
    use GantryTrait;

    protected $type;

    protected $name;

    protected $debug = false;

    /**
     * @var array
     */
    protected $fonts;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var string
     */
    protected $target = 'gantry-theme://css-compiled';

    /**
     * @var string
     */
    protected $outline = 'default';

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var array
     */
    protected $files;

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @return $this
     */
    public function setTarget($target = null)
    {
        if ($target !== null) {
            $this->target = (string) $target;
        }

        return $this;
    }

    /**
     * @param string $outline
     * @return $this
     */
    public function setConfiguration($outline = null)
    {
        if ($outline !== null) {
            $this->outline = $outline;
        }

        return $this;
    }

    /**
     * @param array $fonts
     * @return $this
     */
    public function setFonts(array $fonts = null)
    {
        if ($fonts !== null) {
            // Normalize font data.
            $list = [];
            foreach ($fonts as $family => $data) {
                $family = strtolower($family);

                if (is_array($data)) {
                    // font: [400: url1, 500: url2, 700: url3]
                    $list[$family] = $data;
                } else {
                    // font: url
                    $list[$family] = [400 => (string) $data];
                }
            }
            $this->compiler->setFonts($list);
        }

        return $this;
    }

    /**
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths = null)
    {
        if ($paths !== null) {
            $this->paths = $paths;
        }

        return $this;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function setFiles(array $files = null)
    {
        if ($files !== null) {
            $this->files = $files;
        }

         return $this;
    }


    /**
     * @param string $name
     * @return string
     */
    public function getCssUrl($name)
    {
        $out = $name . ($this->outline !== 'default' ? '_'. $this->outline : '');

        return "{$this->target}/{$out}.css";
    }

    /**
     * @return $this
     */
    public function compileAll()
    {
        foreach ($this->files as $file) {
            $this->compileFile($file);
        }

        return $this;
    }

    public function needsCompile($in, $variables)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $out = $this->getCssUrl($in);
        $path = $locator->findResource($out);

        if (!$path) {
            $this->setVariables($variables());
            return true;
        }

        /** @var Config $global */
        $global = $gantry['global'];

        if ($global->get('production')) {
            return false;
        }

        $uri = basename($out);
        $metaFile = PhpFile::instance($locator->findResource("gantry-cache://scss/{$uri}.php", true, true));

        if (!$metaFile->exists()) {
            $this->setVariables($variables());
            return true;
        }

        $content = $metaFile->content();

        if (empty($content['file']) || $content['file'] != $out) {
            $this->setVariables($variables());
            return true;
        }

        if (filemtime($path) != $content['timestamp']) {
            $this->setVariables($variables());
            return true;
        }

        $this->setVariables($variables());

        $oldVariables = isset($content['variables']) ? $content['variables'] : [];
        if ($oldVariables != $this->getVariables()) {
            return true;
        }

        $imports = isset($content['imports']) ? $content['imports'] : [];
        foreach ($imports as $resource => $timestamp) {
            $import = $locator->findResource($resource);
            if (!$import || filemtime($import) != $timestamp) {
                return true;
            }
        }

        return false;
    }

    public function setVariables(array $variables)
    {
        $this->variables = array_filter($variables);

        foreach($this->variables as &$value) {
            // Check variable against colors and units.
            /* Test regex against these:
             * Should only match the ones marked as +
             *      - family=Aguafina+Script
             *      - #zzzzzz
             *      - #fff
             *      + #ffaaff
             *      + 33em
             *      + 0.5px
             *      - 50 rem
             *      - rgba(323,323,2323)
             *      + rgba(125,200,100,0.3)
             *      - rgb(120,12,12)
             */
            if (preg_match("/(^(#([a-fA-F0-9]{6})|(rgba\(\s*(0|[1-9]\d?|1\d\d?|2[0-4]\d|25[0-5])\s*,\s*(0|[1-9]\d?|1\d\d?|2[0-4]\d|25[0-5])\s*,\s*(0|[1-9]\d?|1\d\d?|2[0-4]\d|25[0-5])\s*,\s*((0.[0-9]+)|[01])\s*\)))|(\d+(\.\d+){0,1}(rem|em|ex|ch|vw|vh|vmin|vmax|%|px|cm|mm|in|pt|pc))$)/i", $value)) {
                continue;
            }

            // Check variable against predefined color names (we use Leafo SCSS Color class to do that).
            if (isset(Colors::$cssColors[strtolower($value)])) {
                continue;
            }

            // All the unknown values need to be quoted.
            $value = "'{$value}'";
        }

        return $this;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function reset()
    {
        $this->compiler->reset();

        return $this;
    }

    protected function createMeta($out, $md5)
    {
        $gantry = Gantry::instance();

        /** @var Config $global */
        $global = $gantry['global'];

        if ($global->get('production')) {
            return;
        }

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $uri = basename($out);
        $metaFile = PhpFile::instance($locator->findResource("gantry-cache://scss/{$uri}.php", true, true));
        $metaFile->save([
            'file' => $out,
            'timestamp' => filemtime($locator->findResource($out)),
            'md5' => $md5,
            'variables' => $this->getVariables(),
            'imports' => $this->compiler->getParsedFiles()
        ]);
        $metaFile->free();
    }
}
