<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Query\Utilities;
use function strlen;
use function strpos;

/**
 * Shared code for server, database and table level pages.
 */
final class Common
{
    public static function database(): void
    {
        global $cfg, $db, $is_show_stats, $db_is_system_schema, $err_url;
        global $message, $dbi, $errno, $is_db, $err_url_0;

        Util::checkParameters(['db']);

        $response = Response::getInstance();
        $is_show_stats = $cfg['ShowStats'];

        $db_is_system_schema = Utilities::isSystemSchema($db);
        if ($db_is_system_schema) {
            $is_show_stats = false;
        }

        /**
         * Defines the urls to return to in case of error in a sql statement
         */
        $err_url_0 = Url::getFromRoute('/');

        $err_url = Util::getScriptNameForOption(
            $cfg['DefaultTabDatabase'],
            'database'
        );
        $err_url .= Url::getCommon(['db' => $db], strpos($err_url, '?') === false ? '?' : '&');

        /**
         * Ensures the database exists (else move to the "parent" script) and displays
         * headers
         */
        if (isset($is_db) && $is_db) {
            return;
        }

        if (strlen($db) > 0) {
            $is_db = $dbi->selectDb($db);
            // This "Command out of sync" 2014 error may happen, for example
            // after calling a MySQL procedure; at this point we can't select
            // the db but it's not necessarily wrong
            if ($dbi->getError() && $errno == 2014) {
                $is_db = true;
                unset($errno);
            }
        } else {
            $is_db = false;
        }
        // Not a valid db name -> back to the welcome page
        $params = ['reload' => '1'];
        if (isset($message)) {
            $params['message'] = $message;
        }
        $uri = './index.php?route=/' . Url::getCommonRaw($params, '&');
        if (strlen($db) === 0 || ! $is_db) {
            if ($response->isAjax()) {
                $response->setRequestStatus(false);
                $response->addJSON(
                    'message',
                    Message::error(__('No databases selected.'))
                );
            } else {
                Core::sendHeaderLocation($uri);
            }
            exit;
        }
    }
}
