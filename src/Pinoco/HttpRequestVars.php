<?php
/**
 * Pinoco: makes existing static web site dynamic transparently.
 * Copyright 2010-2011, Hisateru Tanaka <tanakahisateru@gmail.com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP Version 5
 *
 * @category   Framework
 * @author     Hisateru Tanaka <tanakahisateru@gmail.com>
 * @copyright  Copyright 2010-2011, Hisateru Tanaka <tanakahisateru@gmail.com>
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version    0.5.2
 * @link       https://github.com/tanakahisateru/pinoco
 * @filesource
 * @package    Pinoco
 */

/**
 * Global variables wrapper for HTTP request.
 * @package Pinoco
 * @abstract
 */
class Pinoco_HttpRequestVars extends Pinoco_DynamicVars {
    public function get_server()
    {
        return empty($_SERVER) ? (new Pinoco_Vars()) : Pinoco_Vars::wrap($_SERVER);
    }
    public function get_get()
    {
        return empty($_GET) ? (new Pinoco_Vars()) : Pinoco_Vars::wrap($_GET);
    }
    public function get_post()
    {
        return empty($_POST) ? (new Pinoco_Vars()) : Pinoco_Vars::wrap($_POST);
    }
    public function get_cookie()
    {
        return empty($_COOKIE) ? (new Pinoco_Vars()) : Pinoco_Vars::wrap($_COOKIE);
    }
    public function get_files()
    {
        return empty($_FILES) ? (new Pinoco_Vars()) : Pinoco_Vars::wrap($_FILES);
    }
    public function get_session()
    {
        return empty($_SESSION) ? (new Pinoco_Vars()) : Pinoco_Vars::wrap($_SESSION);
    }
    public function get_env()
    {
        return empty($_ENV) ? (new Pinoco_Vars()) : Pinoco_Vars::wrap($_ENV);
    }
}
