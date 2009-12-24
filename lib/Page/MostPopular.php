<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';

/**
 * Wicked MostPopular class.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class MostPopular extends Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        WICKED_MODE_CONTENT => true,
        WICKED_MODE_DISPLAY => true);

    /**
     * Render this page in Content mode.
     *
     * @param integer $numPages  How many (at most) pages should we return?
     *
     * @return string  The page content, or PEAR_Error.
     */
    function content($numPages = 10)
    {
        global $wicked;

        return $wicked->mostPopular($numPages);
    }

    /**
     * Render this page in Display mode.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function displayContents($isBlock)
    {
        global $notification;

        $summaries = $this->content(10);
        if (is_a($summaries, 'PEAR_Error')) {
            $notification->push('Error retrieving MostPopular: ' . $summaries->getMessage(), 'horde.error');
            return $summaries;
        }

        require_once 'Horde/Template.php';
        $template = new Horde_Template();
        $pages = array();
        foreach ($summaries as $page) {
            $page = new StandardPage($page);
            $pages[] = array('author' => $page->author(),
                             'created' => $page->formatVersionCreated(),
                             'name' => $page->pageName(),
                             'context' => false,
                             'hits' => $page->hits(),
                             'url' => $page->pageUrl(),
                             'version' => $page->version());
        }
        $template->set('pages', $pages, true);
        $template->set('hits', true, true);
        $hits = true;

        Horde::addScriptFile('tables.js', 'horde', true);

        ob_start();
        require WICKED_TEMPLATES . '/pagelist/header.inc';
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/pagelist.html');
        require WICKED_TEMPLATES . '/pagelist/footer.inc';
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    function pageName()
    {
        return 'MostPopular';
    }

    function pageTitle()
    {
        return _("MostPopular");
    }

}
