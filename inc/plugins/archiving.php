<?php

if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

define('PLUGIN_VERSION', '21');

if (defined('THIS_SCRIPT'))
{
    global $templatelist;

    if (isset($templatelist))
    {
        $templatelist .= ',';
    }

    if (THIS_SCRIPT == 'forumdisplay.php')
    {
        $templatelist .= 'archiving_button';
    }
    elseif (THIS_SCRIPT == 'showthread.php')
    {
        $templatelist .= 'archiving_buttonThread';
    }
    elseif (THIS_SCRIPT == 'misc.php')
    {
        $templatelist .= 'archiving_submitSite';
    }
}

if (defined('IN_ADMINCP'))
{
    $plugins->add_hook('admin_formcontainer_output_row', 'archiving_editForumBox');
    $plugins->add_hook('admin_forum_management_edit_commit', 'archiving_commit');
    $plugins->add_hook('admin_tools_menu', 'archiving_tools_menu');
    $plugins->add_hook('admin_tools_action_handler', 'archiving_tools_action_handler');
}
else
{
    $plugins->add_hook('forumdisplay_thread', 'archiving_forumdisplay_thread');
    $plugins->add_hook('showthread_start', 'archiving_showthread_start');
    $plugins->add_hook('misc_start', 'archiving_misc');
}

function archiving_info()
{
    return array(
        'name'          => 'Automatische Archivierung',
        'description'   => 'Ermöglicht es Admins einzustellen in welchen Archivbereich das Thema standardmäßig verschoben wird',
        'website'       => 'https://github.com/aheartforspinach/Archivierung',
        'author'        => 'aheartforspinach',
        'authorsite'    => 'https://github.com/aheartforspinach',
        'version'       => '2.1',
        'codename'      => 'archiving',
        'compatibility' => '18*'
    );
}

function archiving_install()
{
    archiving_check_requirements();

    global $db;

    // add extra db columns
    $forumsExtraColumns = array(
        'archiving_active' => 'tinyint(1) not null default 0',
        'archiving_defaultArchive' => 'int(32) not null default 0',
        'archiving_isVisibleForUser' => 'tinyint(1) not null default 0',
        'archiving_inplay' => 'tinyint(1) not null default 0'
    );

    foreach ($forumsExtraColumns as $name => $definition)
    {
        if (!$db->field_exists($name, 'forums'))
        {
            $db->add_column('forums', $name, $definition);
        }
    }

    // create templates
    $templatearray = array(
        'archiving_button' => '<a href="misc.php?action=archiving&fid={$fid}&tid={$tid}" title="{$lang->archiving_submitpage_title}"><i class="fas fa-archive"></i></a>',
        'archiving_buttonThread' => '<a href="misc.php?action=archiving&fid={$fid}&tid={$tid}" class="button" title="{$lang->archiving_submitpage_title}"><i class="fas fa-archive"></i> {$lang->archiving_submitpage_title}</a>',
        'archiving_submitSite' => '<html>
    <head>
        <title>{$mybb->settings[\'bbname\']} - {$lang->archiving_submitpage_title}</title>
        {$headerinclude}
    </head>
    <body>
        {$header}
        <form action="misc.php?action=archiving" method="post">
        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
        <tr>
        <td class="thead" colspan="2"><strong>{$lang->archiving_submitpage_title}</strong></td>
        </tr>
            <tr><td>
                <center>{$infoText}</center></td>
            </tr>
        </table>
        <br />
        <div align="center"><input type="submit" class="button" name="submit" value="{$lang->archiving_submitpage_submit}" /></div>
        <input type="hidden" name="action" value="archiving" />
        <input type="hidden" name="tid" value="{$tid}" />
        <input type="hidden" name="old_fid" value="{$old_fid}" />
        <input type="hidden" name="new_fid" value="{$new_fid}" />
        </form>
        {$footer}
    </body>
</html>'
    );

    $group = array(
        'prefix' => $db->escape_string('archiving'),
        'title' => $db->escape_string('Archivierung')
    );

    $query = $db->simple_select('templategroups', 'prefix', "prefix='{$group['prefix']}'");

    if ($db->fetch_field($query, 'prefix'))
    {
        $db->update_query('templategroups', $group, "prefix='{$group['prefix']}'");
    }
    else
    {
        $db->insert_query('templategroups', $group);
    }

    foreach ($templatearray as $name => $template)
    {
        $addtemplate = array(
            'title' => $db->escape_string($name),
            'template' => $db->escape_string($template),
            'version' => PLUGIN_VERSION,
            'sid' => -2,
            'dateline' => TIME_NOW
        );

        $db->insert_query('templates', $addtemplate);
        unset($addtemplate);
    }
}

function archiving_is_installed()
{
    global $db;
    return $db->field_exists('archiving_defaultArchive', 'forums');
}

function archiving_uninstall()
{
    global $db;

    $forumsExtraColumns = array(
        'archiving_active',
        'archiving_defaultArchive',
        'archiving_isVisibleForUser',
        'archiving_inplay'
    );

    foreach ($forumsExtraColumns as $name)
    {
        if ($db->field_exists($name, 'forums'))
        {
            $db->drop_column('forums', $name);
        }
    }

    $db->delete_query("templategroups", 'prefix = "archiving"');
    $db->delete_query("templates", "title like 'archiving%'");
}

function archiving_activate()
{
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("forumdisplay_thread", "#" . preg_quote('{$thread[\'multipage\']}') . "#i", '{$thread[\'multipage\']} {$archivingButton}');
    find_replace_templatesets("showthread", "#" . preg_quote('{$newreply}') . "#i", '{$archivingButton} {$newreply}');
}

function archiving_deactivate()
{
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("forumdisplay_thread", "#" . preg_quote('{$archivingButton}') . "#i", '', 0);
    find_replace_templatesets("showthread", "#" . preg_quote('{$archivingButton}') . "#i", '', 0);
}

function archiving_editForumBox(&$args)
{
    global $lang, $form_container, $form, $mybb, $db;

    $lang->load('archiving');
    if ($args['title'] == $lang->misc_options && $lang->misc_options)
    {
        if ($mybb->get_input('action') == 'add')
        {
            $data = array(
                'archiving_active' => '0',
                'archiving_defaultArchive' => 0,
                'archiving_isVisibleForUser' => '0',
                'archiving_inplay' => '0'
            );
        }
        else
        {
            $data = $db->fetch_array($db->simple_select('forums', 'archiving_active, archiving_isVisibleForUser, archiving_inplay, archiving_defaultArchive', 'fid = ' . $mybb->get_input('fid')));
        }
        $formcontent = array(
            $lang->archiving_active . ':<br/>' . $form->generate_yes_no_radio('archive_active', $data['archiving_active']),
            '<br><br><br>' . $lang->archiving_standardArchive . '<br />' . $form->generate_forum_select('archive_forum', (int) $data['archiving_defaultArchive'], array('id' => 'archive_forum')),
            '<br>' . $lang->archiving_editableUser . ':<br/>' . $form->generate_yes_no_radio('archive_editableUser', $data['archiving_isVisibleForUser'],  array('id' => 'archive_editableUser')),
            '<br><br><br>' . $lang->archive_inplayArchive . ':<br />' . $form->generate_yes_no_radio('archive_inplayArchive', $data['archiving_inplay']),
        );

        $args['content'] .= $form_container->output_row('Archivierungsoptionen', '', "<div class=\"forum_archiving_bit\">" . implode("</div><div class=\"forum_archiving_bit\">", $formcontent) . "</div>");
    }
}

function archiving_commit()
{
    global $mybb, $cache, $db, $fid;
    $update_array = array(
        'archiving_active' => $mybb->get_input('archive_active'),
        'archiving_defaultArchive' => $mybb->get_input('archive_forum'),
        'archiving_isVisibleForUser' => $mybb->get_input('archive_editableUser'),
        'archiving_inplay' => $mybb->get_input('archive_inplayArchive')
    );

    $db->update_query('forums', $update_array, 'fid = ' . $fid);
    $cache->update_forums();
}

// show button in forumdisplay and thread
function archiving_forumdisplay_thread()
{
    global $archivingButton, $thread, $mybb;
    if ($mybb->user['uid'] == 0) return;
    $archivingButton = archiving_setArchivingButton($thread, 'archiving_button');
}

function archiving_showthread_start()
{
    global $archivingButton, $thread, $mybb;
    if ($mybb->user['uid'] == 0) return;
    $archivingButton = archiving_setArchivingButton($thread, 'archiving_buttonThread');
}

function archiving_misc()
{
    global $lang, $db, $mybb, $templates, $theme, $headerinclude, $header, $footer, $cache;

    if ($mybb->input['action'] != 'archiving') return;

    $lang->load('archiving');

    // archive thread
    if (isset($_POST['submit']))
    {
        $old_fid = $db->escape_string($_POST['old_fid']);
        $new_fid = $db->escape_string($_POST['new_fid']);
        $tid = $db->escape_string($_POST['tid']);

        $update_array = ['fid' => $new_fid];
        $db->update_query('posts', $update_array, 'tid = ' . $tid);
        $db->update_query('threads', $update_array, 'tid = ' . $tid);

        require_once MYBB_ROOT . "inc/functions_rebuild.php";
        rebuild_forum_counters($old_fid);
        rebuild_forum_counters($new_fid);

        redirect('forumdisplay.php?fid=' . $new_fid, $lang->archiving_submitpage_success);
    }

    $tid = $mybb->get_input('tid');
    if ($mybb->get_input('fid') == 0 || $tid == 0) error_no_permission();
    $thread = get_thread($tid);
    if (!archiving_isAllowedToArchive($thread)) error_no_permission();

    $old_fid = $mybb->get_input('fid');
    $settings = archiving_getArchiveSettings($old_fid);

    if ($settings['archiving_inplay'])
    { // inplay -> search correct category
        $ipdate = $db->fetch_field($db->simple_select('ipt_scenes', 'date', 'tid = ' . $tid), 'date');
        setlocale(LC_TIME, 'de_DE.utf8');
        $archiveName = strftime("%B %G", $ipdate);
        $new_fid = $db->fetch_array($db->simple_select('forums', 'fid', 'name = "' . $archiveName . '"'))['fid'];

        if ($new_fid == null)
        {
            $new_fid = $settings['archiving_defaultArchive'];
            $archiveName = $db->fetch_field($db->simple_select('forums', 'name', 'fid = ' . $new_fid), 'name');
        }
    }
    else
    {
        $archiveName = $db->fetch_array($db->simple_select('forums', 'name', 'fid = ' . $settings['archiving_defaultArchive']))['name'];
        $new_fid = $settings['archiving_defaultArchive'];
    }

    $infoText = $lang->sprintf($lang->archiving_submitpage_text, $thread['subject'], $archiveName);

    eval("\$page = \"" . $templates->get('archiving_submitSite') . "\";");
    output_page($page);
}


// helper functions
function archiving_isUserThreadOwner($threadUid)
{
    $uids = archiving_getUidArray();
    return in_array($threadUid, $uids);
}

function archiving_isUserParticipantInInplayScene($partners)
{
    $uids = archiving_getUidArray();
    foreach ($uids as $uid)
    {
        if (in_array($uid, $partners)) return true;
    }
    return false;
}

function archiving_getUidArray()
{
    global $db, $mybb;
    $uid = $mybb->user['uid'];
    $user = get_user($uid);
    $mainUid = $user['as_uid'] != 0 ? $user['as_uid'] : $uid;

    $query = $db->simple_select('users', 'uid', 'uid = ' . $mainUid . ' or as_uid = ' . $mainUid);
    $uids = [];
    while ($row = $db->fetch_array($query))
    {
        if (!in_array($row['uid'], $uids)) $uids[] = $row['uid'];
    }
    return $uids;
}

function archiving_setArchivingButton($thread, $templateName)
{
    global $templates;
    if (!archiving_isAllowedToArchive($thread))
    {
        return '';
    }

    $fid = $thread['fid'];
    $tid = $thread['tid'];
    return eval($templates->render($templateName));
}

function archiving_getArchiveSettings($fid)
{
    global $db;
    $settings = $db->fetch_array($db->simple_select('forums', 'archiving_active, archiving_isVisibleForUser, archiving_inplay, archiving_defaultArchive, parentlist', 'fid = ' . $fid));

    // when inactive -> search in parent
    if ($settings['archiving_active'] == 0)
    {
        $parentFids = explode(',', $settings['parentlist']);
        $parentFid = 0;
        for (end($parentFids); key($parentFids) !== null; prev($parentFids))
        {
            if ($fid != current($parentFids))
            {
                $parentFid = current($parentFids);
            }
        }
        if ($parentFid === 0) return [];
        $settings = archiving_getArchiveSettings($parentFid);
    }

    return $settings;
}

function archiving_isAllowedToArchive($thread)
{
    global $mybb, $db;
    if ($mybb->user['uid'] == 0) return false;
    $settings = archiving_getArchiveSettings($thread['fid']);

    $query = $db->simple_select('ipt_scenes_partners', 'uid', 'tid = ' . $thread['tid']);
    $partners = [];
    while ($row = $db->fetch_array($query))
    {
        $partners[] = $row['uid'];
    }

    if ($settings['archiving_active'])
    {
        if ($mybb->usergroup['canmodcp'] == 1)
        {
            return true;
        }
        if ($settings['archiving_isVisibleForUser'])
        {
            if ($settings['archiving_inplay'])
            { // is participant in inplay scene
                if (archiving_isUserParticipantInInplayScene($partners))
                {
                    return true;
                }
            }
            elseif (archiving_isUserThreadOwner($thread['uid']))
            { // own thread
                return true;
            }
        }
    }
    return false;
}

// admin
function archiving_tools_menu(&$sub_menu)
{
    end($sub_menu);
    $key = (key($sub_menu)) + 10;
    $sub_menu[$key] = array(
        'id'    =>  'archiving',
        'title' =>  'Archivierung',
        'link'  =>  'index.php?module=tools-archiving'
    );
}

function archiving_tools_action_handler(&$actions)
{
    $actions['archiving'] = array(
        'active' => 'archiving',
        'file' => 'archiving.php'
    );
}

function archiving_check_requirements()
{
    global $plugins_cache, $lang;
    $lang->load('archiving');

    if (is_array($plugins_cache) && is_array($plugins_cache['active']))
    {
        $errors = array();

        if (!array_key_exists('accountswitcher', $plugins_cache['active']))
        {
            $errors[] = 'accountswitcher_plugin_missing';
        }

        if (!array_key_exists('ipt', $plugins_cache['active']))
        {
            $errors[] = 'inplaytracker_plugin_missing';
        }

        if (!empty($errors))
        {
            $plugin_errors = $lang->plugin_errors;
            $plugin_errors .= "<ul>";

            foreach ($errors as $error)
            {
                $plugin_errors .= "<li>" . $lang->$error . "</li>";
            }

            $plugin_errors .= "</ul>";

            flash_message($plugin_errors, "error");
            admin_redirect("index.php?module=config-plugins");
        }
    }
}
