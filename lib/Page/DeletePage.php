<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
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
 * Wicked DeletePage class (for confirming deletion).
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Wicked
 */
class DeletePage extends Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(WICKED_MODE_DISPLAY => true);

    /**
     * The page that we're confirming deletion for.
     *
     * @var string
     */
    var $_referrer = null;

    function DeletePage($referrer)
    {
        $this->_referrer = $referrer;
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
     * Send them back whence they came if they aren't allowed to
     * delete this page.
     */
    function preDisplay()
    {
        $page = Page::getPage($this->referrer());
        if (!$page->allows(WICKED_MODE_REMOVE)) {
            header('Location: ' . Wicked::url($this->referrer(), true));
            exit;
        }
    }

    /**
     * Render this page in Display mode.
     *
     * @return mixed True or PEAR_Error.
     */
    function display()
    {
        $version = Horde_Util::getFormData('version');
        $page = Page::getPage($this->referrer(), $version);
        if (!$page->isValid()) {
            header('Location: ' . Wicked::url('WikiHome', true));
            exit;
        }

        if (empty($version)) {
            $msg = _("Are you sure you want to delete this page? All versions will be permanently removed.");
        } else {
            $msg = sprintf(_("Are you sure you want to delete version %s of this page?"),
                           $page->version());
        }
?>
<form method="post" name="deleteform" action="<?php echo Wicked::url('DeletePage') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="page" value="DeletePage" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="version" value="<?php echo htmlspecialchars($version) ?>" />
<input type="hidden" name="referrer" value="<?php echo htmlspecialchars($page->pageName()) ?>" />

<h1 class="header">
 <?php echo _("Delete Page") . ': ' . Horde::link($page->pageUrl()) . htmlspecialchars($page->pageName()) . '</a>'; if ($page->isLocked()) echo Horde::img('locked.png', _("Locked")) ?>
</h1>

<div class="headerbox" style="padding:4px">
 <p><?php echo $msg ?></p>
 <p>
  <input type="submit" value="<?php echo _("Delete") ?>" class="button" />
  <a class="button" href="<?php echo Wicked::url($page->pageName()) ?>"><?php echo _("Cancel") ?></a>
 </p>
</div>

</form>
<?php
        return true;
    }

    function pageName()
    {
        return 'DeletePage';
    }

    function pageTitle()
    {
        return _("Delete Page");
    }

    function referrer()
    {
        return $this->_referrer;
    }

    function handleAction()
    {
        $pagename = $this->referrer();
        $page = Page::getPage($pagename);
        if ($page->allows(WICKED_MODE_REMOVE)) {
            $version = Horde_Util::getFormData('version');
            if (empty($version)) {
                $GLOBALS['wicked']->removeAllVersions($pagename);
                $GLOBALS['notification']->push(sprintf(_("Successfully deleted \"%s\"."), $pagename), 'horde.success');
                Wicked::mail("Deleted page: $pagename\n",
                             array('Subject' => '[' . $GLOBALS['registry']->get('name') . '] deleted: ' . $pagename));
                header('Location: ' . Wicked::url('WikiHome', true));
            } else {
                $GLOBALS['wicked']->removeVersion($pagename, $version);
                $GLOBALS['notification']->push(sprintf(_("Deleted version %s of \"%s\"."), $version, $pagename), 'horde.success');
                Wicked::mail("Deleted version: $version of $pagename\n",
                             array('Subject' => '[' . $GLOBALS['registry']->get('name') . '] deleted: ' . $pagename . ' [' . $version . ']'));
                header('Location: ' . Wicked::url($pagename, true));
            }
            exit;
        }

        $GLOBALS['notification']->push(sprintf(_("You don't have permission to delete \"%s\"."), $pagename), 'horde.warning');
        header('Location: ' . Wicked::url($this->referrer(), true));
        exit;
    }

}
