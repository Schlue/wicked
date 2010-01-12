<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jason Felice <jason.m.felice@gmail.com>
 */

require_once dirname(__FILE__) . '/lib/base.php';

$page = Horde_Util::getFormData('page', 'WikiHome');
$file = Horde_Util::getFormData('file');
$mime = Horde_Util::getFormData('mime');

$id = $wicked->getPageId($page);
if ($id !== false) {
    $page_id = $id;
} else {
    $page_id = $page;
}

$version = Horde_Util::getFormData('version');
if (empty($version)) {
    $attachments = $wicked->getAttachedFiles($page_id);
    if (is_a($attachments, 'PEAR_Error')) {
        // If we redirect here, we cause an infinite loop with inline
        // attachments.
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    foreach ($attachments as $attachment) {
        if ($attachment['attachment_name'] == $file) {
            $version = $attachment['attachment_majorversion'] . '.' .
                       $attachment['attachment_minorversion'];
        }
    }

    if (empty($version)) {
        // If we redirect here, we cause an infinite loop with inline
        // attachments.
        header('HTTP/1.1 404 Not Found');
        exit;
    }
}

$data = $wicked->getAttachmentContents($page_id, $file, $version);
if (is_a($data, 'PEAR_Error')) {
    // If we redirect here, we cause an infinite loop with inline
    // attachments.
    header('HTTP/1.1 404 Not Found');
    echo $data->getMessage();
    exit;
}

$type = Horde_Mime_Magic::analyzeData($data, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
if ($type === false) {
    $type = Horde_Mime_Magic::filenameToMime($file, false);
}

$browser->downloadHeaders($file, $type, !empty($mime), strlen($data));
echo $data;
