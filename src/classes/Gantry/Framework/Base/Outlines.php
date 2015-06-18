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

namespace Gantry\Framework\Base;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Outline\AbstractOutlineCollection;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Outlines extends AbstractOutlineCollection
{
    /**
     * @param string $path
     * @return $this
     * @throws \RuntimeException
     */
    public function load($path = 'gantry-config://')
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        /** @var UniformResourceIterator $iterator */
        $iterator = $locator->getIterator(
            $path,
            UniformResourceIterator::CURRENT_AS_SELF | UniformResourceIterator::KEY_AS_FILENAME |
            UniformResourceIterator::UNIX_PATHS | UniformResourceIterator::SKIP_DOTS
        );

        $files = [];
        /** @var UniformResourceIterator $info */
        foreach ($iterator as $name => $info) {
            if (!$info->isDir() || $name[0] == '.') {
                continue;
            }
            $files[$name] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
        }

        if (!isset($files['default'])) {
            throw new \RuntimeException('Fatal error: Theme does not have default outline');
        }

        unset($files['default']);
        unset($files['menu']);

        asort($files);

        $this->items = $this->addDefaults($files);

        return $this;
    }

    /**
     * Returns list of all positions defined in outsets.
     *
     * @return array
     */
    public function positions()
    {
        $list = [];
        foreach ($this->items as $name => $title) {
            $index = Layout::index($name);

            $list += $index['positions'];
        }

        return $list;
    }

    /**
     * @param int|string $id
     * @return int|string
     */
    public function preset($id)
    {
        return $id;
    }

    /**
     * @param int|string $id
     * @return Layout
     */
    public function layout($id)
    {
        return Layout::load($id);
    }

    /**
     * @param int|string $id
     * @return Layout
     */
    public function layoutPreset($id)
    {
        $layout = Layout::load($id);
        $preset = $layout->preset;

        unset($layout);

        return $preset;
    }

    /**
     * @param string $id
     * @throws \RuntimeException
     */
    public function duplicate($id)
    {
        throw new \RuntimeException('Not Implemented', 501);
    }

    /**
     * @param string $id
     * @param string $title
     * @throws \RuntimeException
     */
    public function rename($id, $title)
    {
        throw new \RuntimeException('Not Implemented', 501);
    }

    /**
     * @param string $id
     * @throws \RuntimeException
     */
    public function delete($id)
    {
        throw new \RuntimeException('Not Implemented', 501);
    }

    /**
     * @param string $id
     * @return boolean
     */
    public function canDelete($id)
    {
        return true;
    }

    /**
     * @param array $outlines
     * @return array
     */
    protected function addDefaults(array $outlines)
    {
        return [
            'default' => 'Base Outline',
            '_body_only' => 'Body Only',
            '_error' => 'Error',
            '_offline' => 'Offline'
        ] + $outlines;
    }
}
