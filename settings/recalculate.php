<?php

if (!defined('PHORUM_ADMIN')) return;

// Handle a posted form.
if (!empty($_POST['recalculate']) && $_POST['recalculate'] == 2)
{
    $userdata = array();

    // Calculate the global and per forum post counts.
    $res = phorum_db_interact(
        DB_RETURN_RES,
        "SELECT m.user_id  AS user_id,
                m.forum_id AS forum_id,
                f.vroot    AS vroot,
                count(*)   AS count
         FROM   {$PHORUM['message_table']} AS m,
                {$PHORUM['forums_table']}  AS f
         WHERE  m.forum_id = f.forum_id AND
                status = ".PHORUM_STATUS_APPROVED." AND
                user_id != 0
         GROUP  BY user_id, forum_id",
         NULL,
         DB_MASTERQUERY
    );

    while ($row = phorum_db_fetch_row($res, DB_RETURN_ASSOC))
    {
        // Initialize user data structure.
        if (!isset($userdata[$row['user_id']]['global'])) {
            $userdata[$row['user_id']] = array(
                'global' => 0,
                'vroot'  => array(),
                'forum'  => array(),
            );
        }

        // Update user counters.
        $u =& $userdata[$row['user_id']];
        $u['global'] += $row['count'];
        $u['forum'][$row['forum_id']] = $row['count'];
        if (!isset($u['vroot'][$row['vroot']])) {
            $u['vroot'][$row['vroot']] = $row['count'];
        } else {
            $u['vroot'][$row['vroot']] += $row['count'];
        }
    }

    // First, delete all existing post counter data from the system.
    require_once('./include/api/custom_profile_fields.php');
    $field = phorum_api_custom_profile_field_byname('mod_user_tagging');
    if (empty($field['id'])) trigger_error(
        "Cannot find custom profile field \"mod_user_tagging\"!",
        E_USER_ERROR
    );
    $table = isset($PHORUM['user_custom_fields_table'])
           ? $PHORUM['user_custom_fields_table'] // 5.2
           : $PHORUM['custom_fields_table'];     // 5.3
    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM $table
         WHERE  type = " . (int)$field['id'],
         NULL,
         DB_MASTERQUERY
    );

    // Now we can add the recalculated post counts.
    $update_count = 0;
    foreach ($userdata as $user_id => $data) {
        if (phorum_api_user_get($user_id)) {
            $update_count ++;
            phorum_api_user_save(array(
                "user_id"          => $user_id,
                "mod_user_tagging" => $data
            ));
        }
    }

    phorum_admin_okmsg("Recalculated post counters for $update_count users.");
}

// Build the form.
require_once('./include/admin/PhorumInputForm.php');
$frm = new PhorumInputForm ("", "post", "Recalculate post counters");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "user_tagging");
$frm->hidden("recalculate", 2);

$frm->addbreak(
    "Recalculate post counts"
);

$frm->addmessage(
    "This module makes use of its own post counters.
     Using this page, you can recalculate all of these counters.
     This is especially useful after installing the module on
     an already running Phorum system or after deleting a lot of
     messages."
);

$frm->show();

?>
