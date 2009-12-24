<?php
/**
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Wicked
 *
 * $Horde: wicked/lib/Sync/wicked.php,v 1.2 2009/01/06 18:02:40 jan Exp $
 */

/** Horde_RPC */
require_once 'Horde/RPC.php';

/**
 * Wicked_Driver:: defines an API for implementing storage backends for
 * Wicked.
 *
 * @author  Duck <duck@obala.net>
 * @package Wicked
 */
class Wicked_Sync_wicked extends Wicked_Sync {

    /**
     * Returns a list of available pages.
     *
     * @return array  An array of all available pages.
     */
    var $_client;

    /**
     * Returns a list of available pages.
     *
     * @return array  An array of all available pages.
     */
    function listPages()
    {
        return $this->_getData('list');
    }

    /**
     * Get the wiki source of a page specified by its name.
     *
     * @param string $name  The name of the page to fetch
     *
     * @return mixed        String of page data on success; PEAR_Error on fail
     */
    function getPageSource($pageName)
    {
        return $this->_getData('getPageSource', array($pageName));
    }

    /**
     * Return basic page information.
     *
     * @param string $pageName Page name
     *
     * @return mixed        Array of page data on success; PEAR_Error on failure
     */
    function getPageInfo($pageName)
    {
        return $this->_getData('getPageInfo', array($pageName));
    }

    /**
     * Return basic pages information.
     *
     * @param array $pages Page names to get info for
     *
     * @return mixed        Array of pages data on success; PEAR_Error on failure
     */
    function getMultiplePageInfo($pages = array())
    {
        return $this->_getData('getMultiplePageInfo', array($pages));
    }

    /**
     * Return page history.
     *
     * @param string $pagename Page name
     *
     * @return array  An array of page parameters.
     */
    function getPageHistory($pagename)
    {
        return $this->_getData('getPageHistory', array($pagename));
    }

    /**
     * Updates content of a wiki page. If the page does not exist it is
     * created.
     *
     * @param string $pagename Page to edit
     * @param string $text Page content
     * @param string $changelog Description of the change
     * @param boolean $minorchange True if this is a minor change
     *
     * @return boolean | PEAR_Error True on success, PEAR_Error on failure.
     */
    function editPage($pagename, $text, $changelog = '', $minorchange = false)
    {
        return $this->_getData('edit', array($pagename, $text, $changelog, $minorchange));
    }

    /**
     * Process remote call
     *
     * @param string $method Method name to call
     * @param array $params Array of parameters
     *
     * @return mixed        Array of pages data on success; PEAR_Error on failure
     */
    function _getData($method, $params = array())
    {
        return Horde_RPC::request(
            'xmlrpc',
            $this->_params['url'],
            $this->_params['prefix'] . '.' . $method,
            $params,
            array('user' => $this->_params['user'],
                  'pass' => $this->_params['password']));
    }

}
