<?php
/**
 * Default event listeners
 */

namespace Minds\Core\Events;

use Minds\Core;
use Minds\Entities;
use Minds\Helpers;

class Defaults
{
    private static $_;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {

        //Channel object reserializer
        Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();
            $export = $event->response() ?: [];
            if ($params['entity']->fullExport && $params['entity']->ownerObj && is_array($params['entity']->ownerObj)) {
                $export['ownerObj'] = Entities\Factory::build($params['entity']->ownerObj)->export();
                //$export['ownerObj'] = \Minds\Helpers\Export::sanitize($params['entity']->ownerObj);
              //  $export['ownerObj']['guid'] = (string) $params['entity']->ownerObj['guid'];
                $event->setResponse($export);
            }
        });

        //Comments count export extender
        Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();
            $export = $event->response() ?: [];

            if ($params['entity']->type != 'activity' && $params['entity']->type != 'group') {
                return false;
            }

            $cacher = Core\Data\cache\factory::build();
            $db = new Core\Data\Call('entities_by_time');

            if (($params['entity']->type == 'activity') && $params['entity']->entity_guid) {
                $guid = $params['entity']->entity_guid;
            } else {
                $guid = $params['entity']->guid;
            }

            $cached = $cacher->get("comments:count:$guid");
            if ($cached !== false) {
                $count = $cached;
            } else {
                $count = $db->countRow("comments:$guid");
                $cacher->set("comments:count:$guid", $count);
            }

            $export['comments:count'] = $count;

            $event->setResponse($export);
        });

        // Notifications events
        Core\Notification\Events::registerEvents();

        // Search events
        (new Core\Search\Events())->init();
        (new Core\Events\Hooks\Register())->init();

        // Third-Party Networks events
        (new Core\ThirdPartyNetworks\Events())->register();

        // Subscription Queue events
        Helpers\Subscriptions::registerEvents();

        // Payments events
        (new Core\Payments\Events())->register();

        // Media events
        (new Core\Media\Events())->register();

        // Wire Events
        (new Core\Wire\Events())->register();

        // Report Events
        (new Core\Reports\Events())->register();

        // Group Events
        (new Core\Groups\Events())->register();
    }

    public static function _()
    {
        if (!self::$_) {
            self::$_ = new Defaults();
        }
        return self::$_;
    }
}
