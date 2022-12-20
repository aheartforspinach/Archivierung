<?php
if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item('Archivierung', 'index.php?module=tools-archiving');
$page->output_header('Archivierung Administration');

if ($mybb->request_method == "post" && isset($mybb->input['archiving_update']))
{
    verify_post_check($mybb->get_input('my_post_key'));

    // delete old settings
    $db->delete_query('settings', "name IN('archiving_type')");
    $db->delete_query('settinggroups', "name = 'archiving'");

    // create template group
    $templategroup = array(
        'prefix' => 'archiving',
        'title' => $db->escape_string('Archivierung'),
    );

    $db->insert_query("templategroups", $templategroup);

    // move templates from global to each style
    $db->update_query('templates', array('title' => 'archiving_button', 'sid' => -2), 'title = "archivingButton"');
    $db->update_query('templates', array('title' => 'archiving_buttonThread', 'sid' => -2), 'title = "archivingButtonThread"');
    $db->update_query('templates', array('title' => 'archiving_submitSite', 'sid' => -2), 'title = "archivingSubmitSite"');

    flash_message('Das Update wurde erfolgreich durchgeführt!', 'success');
    admin_redirect("index.php?module=tools-archiving");
}


$uptodate = false;
$query = $db->simple_select('templates', 'title', 'title = "archivingButton"');
if ($db->num_rows($query) == 0)
{
    $uptodate = true;
}

// format Table
$form = new Form('index.php?module=tools-archiving', 'post');
$form_container = new FormContainer('Plugin aktualisieren');
$form_container->output_row_header('Plugin');
$form_container->output_row_header('Update');
$form_container->output_cell('Archivierung Plugin');
if ($uptodate)
{
    $form_container->output_cell('Du bist bereits auf den aktuellen Stand');
}
else
{
    $form_container->output_cell($form->generate_submit_button('Update', array('name' => 'archiving_update')));
}
$form_container->construct_row();
$form_container->end();
$form->end();
$page->output_footer();
