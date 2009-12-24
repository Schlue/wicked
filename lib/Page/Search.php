<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';
require_once WICKED_BASE . '/lib/Page/StandardPage/StdHistoryPage.php';

/**
 * Wicked SearchAll class.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Chavet <ben@horde.org>
 * @package Wicked
 */
class Search extends Page {

    /**
     * Display modes supported by this page.
     * @var array
     */
    var $supportedModes = array(
        WICKED_MODE_CONTENT => true,
        WICKED_MODE_DISPLAY => true);

    /**
     * Cached search results.
     * @var array
     */
    var $_results;

    /**
     * Render this page in Content mode.
     *
     * @param string $searchtext  The title to search for.
     *
     * @return string  The page content, or PEAR_Error.
     */
    function content($searchtext = '')
    {
        if (empty($searchtext)) {
            return array();
        }

        $titles = $GLOBALS['wicked']->searchTitles($searchtext);
        $pages = $GLOBALS['wicked']->searchText($searchtext, false);

        return array('titles' => $titles, 'pages' => $pages);
    }

    /**
     * Perform any pre-display checks for permissions, searches,
     * etc. Called before any output is sent so the page can do
     * redirects. If the page wants to take control of flow from here,
     * it can, and is entirely responsible for handling the user
     * (should call exit after redirecting, for example).
     *
     * $param integer $mode    The page render mode.
     * $param array   $params  Any page parameters.
     */
    function preDisplay($mode, $params)
    {
        $this->_results = $this->content($params);
    }

    /**
     * Render this page in Display mode.
     *
     * @param string $searchtext  The title to search for.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function display($searchtext)
    {
        global $notification;

        if (is_a($this->_results, 'PEAR_Error')) {
            $notification->push('Error retrieving search results: ' .
                                $this->_results->getMessage(), 'horde.error');
            return $this->_results;
        }

        if (!$searchtext) {
            require WICKED_TEMPLATES . '/pagelist/search.inc';
            require WICKED_TEMPLATES . '/pagelist/footer.inc';
            return true;
        }

        Horde::addScriptFile('tables.js', 'horde', true);

        require_once 'Horde/Template.php';
        $template = new Horde_Template();

        /* Prepare exact match section */
        $exact = array();
        $page = new StandardPage($searchtext);
        if ($GLOBALS['wicked']->pageExists($searchtext)) {
            $exact[] = array('author' => htmlspecialchars($page->author()),
                             'created' => $page->formatVersionCreated(),
                             'name' => htmlspecialchars($page->pageName()),
                             'context' => false,
                             'url' => $page->pageUrl(),
                             'version' => $page->version(),
                             'class' => '');
        } else {
            $exact[] = array('author' => '',
                             'created' => '',
                             'name' => htmlspecialchars($searchtext),
                             'context' => sprintf(_("%s does not exist. You can create it now."), '<strong>' . htmlspecialchars($searchtext) . '</strong>'),
                             'url' => Wicked::url($searchtext, false),
                             'version' => '',
                             'class' => 'newpage');
        }

        /* Prepare page title matches */
        $titles = array();
        foreach ($this->_results['titles'] as $page) {
            if (!empty($page['page_history'])) {
                $page = new StdHistoryPage($page);
            } else {
                $page = new StandardPage($page);
            }

            $titles[] = array('author' => $page->author(),
                              'created' => $page->formatVersionCreated(),
                              'name' => $page->pageName(),
                              'context' => false,
                              'url' => $page->pageUrl(),
                              'version' => $page->version(),
                              'class' => '');
        }

        /* Prepare page text matches */
        $pages = array();
        foreach ($this->_results['pages'] as $page) {
            if (!empty($page['page_history'])) {
                $page = new StdHistoryPage($page);
            } else {
                $page = new StandardPage($page);
            }

            $pages[] = array('author' => $page->author(),
                             'created' => $page->formatVersionCreated(),
                             'name' => $page->pageName(),
                             'context' => $this->getContext($page, $searchtext),
                             'url' => $page->pageUrl(),
                             'version' => $page->version(),
                             'class' => '');
        }

        $template->set('hits', false, true);

        $template->set('th_page', _("Page"), true);
        $template->set('th_version', _("Version"), true);
        $template->set('th_author', _("Author"), true);
        $template->set('th_created', _("Creation Date"), true);

        // Show search form and page header.
        require WICKED_TEMPLATES . '/pagelist/search.inc';

        // Show exact match.
        $template->set('title', _("Exact Match"), true);
        $template->set('pages', $exact, true);
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/results_header.html');
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/pagelist.html');
        require WICKED_TEMPLATES . '/pagelist/results_footer.inc';

        // Show page title matches.
        $template->set('title', _("Page Title Matches"), true);
        $template->set('pages', $titles, true);
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/results_header.html');
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/pagelist.html');
        require WICKED_TEMPLATES . '/pagelist/results_footer.inc';

        // Show page text matches.
        $template->set('title', _("Page Text Matches"), true);
        $template->set('pages', $pages, true);
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/results_header.html');
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/pagelist.html');
        require WICKED_TEMPLATES . '/pagelist/results_footer.inc';
        echo '</div>';

        return true;
    }

    function getContext($page, $searchtext)
    {
        if (preg_match('/.{0,100}' . preg_quote($searchtext, '/') . '.{0,100}/i', $page->getText(), $context)) {
            return preg_replace('/' . preg_quote($searchtext, '/') . '/i', '<span class="match">' . htmlspecialchars($searchtext) . '</span>', htmlspecialchars($context[0]));
        }
        return '';
    }

    function pageName()
    {
        return 'Search';
    }

    function pageTitle()
    {
        return _("Search");
    }

}
