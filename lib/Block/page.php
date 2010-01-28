<?php

$block_name = _("Wiki page");

/**
 * This class extends Horde_Block:: to display a Wiki page.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jason Felice <jason.m.felice@gmail.com>
 * @package Horde_Block
 */
class Horde_Block_Wicked_page extends Horde_Block {

    var $_app = 'wicked';

    function _title()
    {
        $page = Page::getPage($this->_params['page']);
        return htmlspecialchars($page->pageName());
    }

    function _content()
    {
        $page = Page::getPage($this->_params['page']);
        return $page->render(WICKED_MODE_BLOCK);
    }

    function _params()
    {
        return array('page' => array('type' => 'text',
                                     'name' => _("Name of wiki page to display"),
                                     'default' => 'WikiHome'));
    }

}
