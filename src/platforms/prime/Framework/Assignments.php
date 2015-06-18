<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Prime\Pages;

class Assignments
{
    protected $outline;
    protected $pages;

    public function __construct($outline)
    {
        $this->outline = $outline;
    }

    public function get()
    {
        if (!$this->pages) {
            $pages = new Pages();
            $this->pages = [];
            foreach ($pages as $page => $file) {
                $path = explode('/', $page);
                $this->pages[] = [
                    'name' => 'page[' . $page . ']',
                    'field' => ['id', 'link-' . preg_replace('|[^a-zA-Z0-9-]|', '-', $page)],
                    'value' => 0, // TODO
                    'label' => str_repeat('- ', count($path)) . ucwords(end($path))
                ];
            }
        }

        return [['label' => 'Pages', 'items' => $this->pages]];
    }

    public function set($data)
    {
    }

    public function types()
    {
        return ['pages'];
    }
}
