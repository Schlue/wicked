<?php
/**
 * Wicked Page class for old versions of pages.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class StdHistoryPage extends StandardPage {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        WICKED_MODE_DISPLAY => true,
        WICKED_MODE_EDIT => false,
        WICKED_MODE_REMOVE => true,
        WICKED_MODE_HISTORY => true,
        WICKED_MODE_DIFF => true,
        WICKED_MODE_LOCKING => false,
        WICKED_MODE_UNLOCKING => false);

    /**
     * Construct a standard history page class to represent an old
     * version of a wiki page.
     *
     * @param string  $pagename    The name of the page to load.
     * @param integer $version     The version of the page to load.
     */
    function StdHistoryPage($pagename, $version = null)
    {
        if (empty($version)) {
            parent::StandardPage($pagename);
            return;
        }

        // Retrieve the version.
        $pages = $GLOBALS['wicked']->retrieveHistory($pagename, $version);

        // If it didnt find one, return an error.
        if (is_a($pages, 'PEAR_Error')) {
            $GLOBALS['notification']->push($pages);
        } elseif (empty($pages[0])) {
            $GLOBALS['notification']->push(_("History page not found"));
        } else {
            $this->_page = $pages[0];
        }
    }

    function isOld()
    {
        return true;
    }

    function pageUrl($linkpage = null, $actionId = null)
    {
        return Horde_Util::addParameter(parent::pageUrl($linkpage, $actionId), 'version', $this->version());
    }

}
