<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Layout\Layout;
use Gantry\Framework\Base\Gantry;
use Gantry\Joomla\Manifest;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;

class EventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'admin.styles.save' => ['onStylesSave', 0],
            'admin.settings.save' => ['onSettingsSave', 0],
            'admin.layout.save' => ['onLayoutSave', 0],
            'admin.assignments.save' => ['onAssignmentsSave', 0]
        ];
    }

    public function onStylesSave(Event $event)
    {
        \JPluginHelper::importPlugin('gantry5');

        // Trigger the onGantryThemeUpdateCss event.
        $dispatcher = \JEventDispatcher::getInstance();
        $dispatcher->trigger('onGantry5UpdateCss', ['theme' => $event->theme]);
    }

    public function onSettingsSave(Event $event)
    {
    }

    public function onLayoutSave(Event $event)
    {
        $positions = $event->gantry['outlines']->positions();

        $manifest = new Manifest($event->gantry['theme.name']);
        $manifest->setPositions(array_keys($positions));
        $manifest->save();
    }

    public function onAssignmentsSave(Event $event)
    {
    }
}
