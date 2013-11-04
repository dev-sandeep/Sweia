<?php

    /**
     * @author Joshua Kissoon
     * @date 20121219
     * @description Handles all URL events within the site
     */
    class JPath
    {

        public static function baseURL()
        {
            /* Returns the Site Base URL */
            return BASE_URL;
        }

        public static function basePath()
        {
            /* Returns the Site Base Path */
            return BASE_PATH;
        }

        public static function requestUrl()
        {
            /*
             *  @return The relative URL from which the request came from
             */
            $url = $_SERVER["REQUEST_URI"];
            if (valid(@SITE_FOLDER))
            {
                /* If the Site is within a subfolder, remove it from the URL arguments */
                $folder = rtrim(SITE_FOLDER, '/') . '/';
                $url = str_replace($folder, "", $url);
            }
            return rtrim(ltrim($url, '/'), "/");
        }

        public static function fullRequestUrl()
        {
            /*
             * @return The full URL of the page which the user is on
             */
            return BASE_URL . self::requestUrl();
        }

        public static function getUrlQ()
        {
            $url = @$_GET['urlq'];
            return rtrim(ltrim($url, "/"), "/");
        }

        public static function urlArgs($index = null)
        {
            /*
             * @return An array of arguments within the URL currently being viewed
             */
            $url = self::getUrlQ();
            $eurl = explode('/', $url);
            return ($index) ? $eurl[$index] : $eurl;
        }

        public static function getUrlHandlers($url = null)
        {
            /*
             * Returns the modules that handles this URL
             * If the URL argument is passed, return the handler for this url, otherwise, return the handler for the current url
             */
            if (!valid(@$url))
                $url = self::getUrlQ();
            if (!valid(@$url))
                $url = HOME_URL;

            $url_parts = explode("/", $url);
            $num_parts = count($url_parts);

            $sql = "SELECT uh.module, uh.permission, md.status FROM url_handlers uh LEFT JOIN modules md ON (uh.module = md.name) 
             WHERE (num_parts='$num_parts' OR num_parts='0') AND md.status = 1";
            $c = 0;
            $args = array();
            foreach ($url_parts as $part)
            {
                $sql .= " AND (p$c = '::p$c' OR p$c = '%')";
                $args["::p$c"] = $part;
                $c++;
            }
            $sql .= " ORDER BY num_parts DESC";
            global $DB;
            $rs = $DB->query($sql, $args);
            $handlers = array();
            $module = "";
            while ($handler = $DB->fetchObject($rs))
            {
                /* Only add the module to be loaded if the user has the permission to access this module for this path */
                if ($handler->module != $module)
                {
                    /* Only add one handler per module */
                    $handlers[] = array(
                        "module" => $handler->module,
                        "permission" => $handler->permission
                    );
                    $module = $handler->module;
                }
            }
            return $handlers;
        }

        public static function parseMenu($menu, $base_url = -1, $uid = null)
        {
            /*
             * @params An array with $url => $title
             * @description This function parses the menu and:
             *      -> removes those items the specified user don't have premission to access
             *      -> Append the Site Base URL to each of the menu items if they don't already contain the base url
             */

            /* If no user was specified, parse the menu for the current user */
            global $USER;
            $uid = $USER->uid; //hprint($menu);hprint($USER);
            foreach ($menu as $url => $menuItem)
            {
                /* Remove the site base URL from the front of the menu if it exists there */
                $url1 = str_replace(BASE_URL, "", $url);
                $url = ltrim(rtrim($url1));

                /* Remove this URL from the menu */
                unset($menu[$url]);

                if (self::userHasURLAccessPermission($uid, $url))
                {
                    /*
                     * If the user has the necessary permission to access the URL
                     *   add the URL back to the menu, with the SITE_URL prepended to the URL
                     */
                    if ($base_url == -1)
                        $base_url = self::baseURL();

                    $url = $base_url . $url;
                    $menu[$url] = $menuItem;
                }
            }
            return $menu;
        }

        public static function userHasURLAccessPermission($uid, $url = "")
        {
            /* Checks if the user has permission to access this URL */
            global $DB;

            $tmp = $DB->query("SELECT permission FROM url_handlers WHERE url='::url'", array("::url" => $url));
            $tmp = $DB->fetchObject($tmp);
            if (!@$tmp->permission)
            {
                /* If the URL has no permission, return true that the user has the permission to access the URL */
                return true;
            }

            /* If the URL has some permission, check if the user has the necessary permission to access the URL */
            $args = array("::url" => $url, "::uid" => $uid);
            $sql = "SELECT u.uid, ur.rid, rp.permission, uh.url FROM users u
                    LEFT JOIN user_roles ur ON (u.uid = ur.uid) LEFT JOIN role_permissions rp ON (rp.rid = ur.rid)
                    LEFT JOIN url_handlers uh ON (uh.permission = rp.permission)
                    WHERE uh.url='::url' AND u.uid='::uid' GROUP BY u.uid";
            $tmp = $DB->query($sql, $args);
            $tmp = $DB->fetchObject($tmp);
            return valid(@$tmp->uid) ? true : false;
        }

        public static function fullUrl($url)
        {
            /* Returns the full site URL for a given URL string */
            return self::baseURL() . "?urlq=" . ltrim($url, "/");
        }

    }
    