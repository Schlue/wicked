<?php
/**
 * $Horde: wicked/lib/Page/EditPage.php,v 1.36 2009/09/28 22:43:59 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Wicked
 */

/**
 * Page
 */
require_once WICKED_BASE . '/lib/Page.php';

/**
 * Wicked EditPage class.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Wicked
 */
class EditPage extends Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        WICKED_MODE_DISPLAY => true,
        WICKED_MODE_EDIT => true);

    /**
     * The page that we're editing.
     *
     * @var string
     */
    var $_referrer = null;

    function EditPage($referrer)
    {
        $this->_referrer = $referrer;
        if ($GLOBALS['conf']['lock']['driver'] != 'none') {
            $this->supportedModes[WICKED_MODE_LOCKING] = $this->supportedModes[WICKED_MODE_UNLOCKING] = true;
        }
    }

    /**
     * Returns if the page allows a mode. Access rights and user state
     * are taken into consideration.
     *
     * @see $supportedModes
     *
     * @param integer $mode  The mode to check for.
     *
     * @return boolean  True if the mode is allowed.
     */
    function allows($mode)
    {
        if ($mode == WICKED_MODE_EDIT) {
            $page = Page::getPage($this->referrer());
            if ($page->isLocked(Horde_Auth::getAuth() ? Horde_Auth::getAuth() : $GLOBALS['browser']->getIPAddress())) {
                return false;
            }
        }
        return parent::allows($mode);
    }

    /**
     * Retrieve this user's permissions for the referring page.
     *
     * @return integer  The permissions bitmask.
     */
    function getPermissions()
    {
        return parent::getPermissions($this->referrer());
    }

    /**
     * Send them back whence they came if they aren't allowed to edit
     * this page.
     */
    function preDisplay()
    {
        if (!$this->allows(WICKED_MODE_EDIT)) {
            header('Location: ' . Wicked::url($this->referrer(), true));
            exit;
        }
        if ($this->allows(WICKED_MODE_LOCKING)) {
            $page = Page::getPage($this->referrer());
            $result = $page->lock();
            if (is_a($result, 'PEAR_Error')) {
                $GLOBALS['notification']->push(sprintf(_("Page failed to lock: %s"), $result->getMessage()), 'horde.error');
            }
        }
    }

    /**
     * Render this page in Display mode.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function display()
    {
        $page = Page::getPage($this->referrer());
        $page_text = Horde_Util::getFormData('page_text');
        if (is_null($page_text)) {
            $page_text = $page->getText();
        }

        require WICKED_TEMPLATES . '/edit/standard.inc';
        return true;
    }

    function pageName()
    {
        return 'EditPage';
    }

    function pageTitle()
    {
        return _("EditPage");
    }

    function referrer()
    {
        return $this->_referrer;
    }

    function isLocked()
    {
        $page = Page::getPage($this->referrer());
        return $page->isLocked();
    }

    function getLockRequestor()
    {
        $page = Page::getPage($this->referrer());
        return $page->getLockRequestor();
    }

    function getLockTime()
    {
        $page = Page::getPage($this->referrer());
        return $page->getLockTime();
    }

    function handleAction()
    {
        global $notification, $conf;

        $page = Page::getPage($this->referrer());
        if (!$this->allows(WICKED_MODE_EDIT)) {
            $notification->push(sprintf(_("You don't have permission to edit \"%s\"."), $page->pageName()));
        } else {
            if (!empty($GLOBALS['conf']['wicked']['captcha']) &&
                !Horde_Auth::getAuth() &&
                (Horde_String::lower(Horde_Util::getFormData('wicked_captcha')) != Horde_String::lower(Wicked::getCAPTCHA()))) {
                $notification->push(_("Random string did not match."), 'horde.error');
                return;
            } 
            $text = Horde_Util::getFormData('page_text');
            $changelog = Horde_Util::getFormData('changelog');
            if ($conf['wicked']['require_change_log'] && empty($changelog)) {
                $notification->push(_("You must provide a change log."), 'horde.error');
                $notification->push('if (document.editform && document.editform.changelog) document.editform.changelog.focus();', 'javascript');
                return;
            }
            $minorchange = Horde_Util::getFormData('minor');
            if (trim($text) == trim($page->getText())) {
                $notification->push(_("No changes made"), 'horde.warning');
            } else {
                $result = $page->updateText($text, $changelog, $minorchange);
                if (is_a($result, 'PEAR_Error')) {
                    $notification->push(sprintf(_("Save Failed: %s"),
                                                $result->getMessage()), 'horde.error');
                } else {
                    $notification->push(_("Page Saved"), 'horde.success');
                }
            }

            if ($this->allows(WICKED_MODE_UNLOCKING)) {
                $result = $page->unlock();
                if (is_a($result, 'PEAR_Error')) {
                    $GLOBALS['notification']->push(sprintf(_("Page failed to unlock: %s"), $result->getMessage()), 'horde.error');
                }
            }
        }

        // Show the newly saved page.
        header('Location: ' . Wicked::url($this->referrer(), true));
        exit;
    }

}
