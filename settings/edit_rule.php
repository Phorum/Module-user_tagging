<?php

if (!defined('PHORUM_ADMIN')) return;

// Handle a posted form.
if (!empty($_POST['edit_rule']) && $_POST['edit_rule'] == 2)
{
    $errors = array();

    $rule['id']             = (int) $_POST['id'];
    $rule['name']           = trim($_POST['name']);
    $rule['vroot']          = (int) $_POST['vroot'];
    $rule['enable_profile'] = (int) $_POST['vroot'];

    $rule = array(
        "id"              => (int) $_POST['id'],
        "name"            => trim($_POST['name']),
        "vroot"           => (int) $_POST['vroot'],
        "forum"           => (int) $_POST['forum'],
        "enable_profile"  => isset($_POST['enable_profile']) ? 1 : 0,
        "enable_read"     => isset($_POST['enable_read']) ? 1 : 0,
        "enable_user"     => isset($_POST['enable_user']) ? 1 : 0,
        "enable_list"     => isset($_POST['enable_list']) ? 1 : 0,
        "scope"           => $_POST['scope'],
        "postsgte"        => trim($_POST['postsgte'])  == ''
                             ? '' : (int) $_POST['postsgte'],
        "postslt"         => trim($_POST['postslt'])  == ''
                             ? '' : (int) $_POST['postslt'],
        "reggte"          => trim($_POST['reggte'])  == ''
                             ? '' : (int) $_POST['reggte'],
        "reglt"           => trim($_POST['reglt'])  == ''
                             ? '' : (int) $_POST['reglt'],
        "activegte"       => trim($_POST['activegte'])  == ''
                             ? '' : (int) $_POST['activegte'],
        "activelt"        => trim($_POST['activelt'])  == ''
                             ? '' : (int) $_POST['activelt'],
        "user_type"       => $_POST['user_type'],
        "user_active"     => $_POST['user_active'],
        "user_match_type" => $_POST['user_match_type'],
        "ban_list"        => $_POST['ban_list'],
        "user_match"      => trim($_POST['user_match']),
        "user_group"      => isset($_POST['user_group'])
                             ? (int)$_POST['user_group'] : 0,
        "user_ids"        => NULL,
        "tpl_var"         => trim($_POST['tpl_var']),
        "tpl_html"        => trim($_POST['tpl_html'])
    );

    // Special care for the user_match field.
    $user_ids = array();
    if ($rule['user_match_type'] == 'user_id')
    {
        $list = preg_split('/[\s;,]+/', $rule['user_match']);
        foreach ($list as $item)
        {
            if ($item == '') continue;
            if (is_numeric($item)) {
                settype($item, 'int');
                $user = phorum_api_user_get($item);
                if (!$user) {
                    $errors[] = "User ID $item is unknown.";
                }
                $user_ids[$item] = $item;
            } else {
                $errors[] = 'User ID "'.htmlspecialchars($item).'" ' .
                            'is not numeric.';
            }
        }
    }
    else
    {
        $list = preg_split('/[\s;,]+/', $rule['user_match']);
        foreach ($list as $item)
        {
            if ($item == '') continue;
            $user_id = phorum_api_user_search('username', $item);
            if ($user_id) {
                $user_ids[$user_id] = $user_id;
            } else {
                $errors[] = 'Username "'.htmlspecialchars($item).'" ' .
                            'is unknown.';
            }
        }
    }
    $rule['user_ids'] = $user_ids;

    if ($rule['name'] == '') {
        $errors[] = 'The name / description for the rule is not set.';
    }

    if ($rule['postslt']  . $rule['postsgte'] .
        $rule['reglt']    . $rule['reggte'] .
        $rule['activelt'] . $rule['activegte'] .
        $rule['user_type']   == '' &&
        $rule['user_active'] == -1 &&
        $rule['user_group']  ==  0 &&
        $rule['ban_list']    == -1 &&
        empty($rule['user_ids'])) {
        $errors[] = 'No criterium is set. Configure at least one.';
    }

    if (($rule['activelt'] != '' || $rule['activegte'] != '') &&
        empty($PHORUM['track_user_activity'])) {
        $errors[] = "You have configured a criterium for the user's last " .
                    "activity, but you have tracking of user activity " .
                    "disabled. If you want to use this criterium, then " .
                    "enable activity tracking on the General Settings " .
                    "page.";
    }

    if ($rule["tpl_var"] == '') {
        $errors[] = 'The template variable name is not set.';
    } elseif (!preg_match('/^[\w_]+$/', $rule["tpl_var"])) {
        $errors[] = 'The template variable name can only contain letters, ' .
                    'numbers and underscore "_" characters.';
    }

    if ($rule['tpl_html'] == '') {
        $errors[] = 'The HTML code to put in the template variable is not set.';
    }

    // Errors in the input?
    if (!empty($errors)) {
        phorum_admin_error(
            'One or more problems were found. Please correct them ' .
            'and try again:<ul><li>' .
            implode('</li><li>', $errors) . '</li></ul>'
        );
    }
    // Everything okay? Then save the rule.
    else
    {
        user_tagging_store_rule($rule);

        phorum_admin_okmsg("The rule \"" . htmlspecialchars($rule['name']) . "\" was successfully stored");

        include("./mods/user_tagging/settings/list_rules.php");
        return;
    }
}

// Handle initial form.
else
{
    // Edit an existing rule.
    if (isset($_GET['id'])) {
        if (empty($PHORUM['mod_user_tagging']['rules'][$_GET['id']])) {
            phorum_admin_error("Cannot edit rule: rule id " .
                               htmlspecialchars($_GET['id']) .
                               " not found");
            include("./mods/user_tagging/settings/list_rules.php");
            return;
        }
        $rule = $PHORUM['mod_user_tagging']['rules'][$_GET['id']];
    }

    // The default setup for an empty new rule.
    else {
        $rule = array(
            'id'              => 0,
            'name'            => '',
            'vroot'           => -1,
            'forum'           => -1,
            'enable_profile'  => 0,
            'enable_read'     => 0,
            'enable_user'     => 0,
            'enable_list'     => 0,
            'scope'           => 'GLOBAL',
            'postslt'         => '',
            'postsgte'        => '',
            'reglt'           => '',
            'reggte'          => '',
            'activelt'        => '',
            'activegte'       => '',
            'tpl_var'         => '',
            'tpl_html'        => '',
            'user_type'       => '',
            'ban_list'        => -1,
            'user_active'     => -1,
            'user_match_type' => 'user_id',
            'user_match'      => ''
        );
    }
}

$title = $rule['id']
       ? "Edit user tagging rule"
       : "Add a new user tagging rule";
print '<div class="PhorumAdminTitle">'.$title.'</div>';

// Build the form.
require_once('./include/admin/PhorumInputForm.php');
$frm = new PhorumInputForm ("", "post", "Save rule");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "user_tagging");
$frm->hidden("edit_rule", 2);
$frm->hidden("id", $rule['id']);

// A name for the rule. This one is purely meant as a reference to the user.
$frm->addrow(
    "Rule name / description (only for your reference)",
    $frm->text_box("name", $rule['name'], 45)
);

// Build a list of vroot folders and a javascript structure, describing
// the forums inside the vroot.
$forums = phorum_api_forums_get();
$vroot_folders = array();
$vroot2forums = array();
foreach ($forums as $forum) {
    if ($forum['forum_id'] == $forum['vroot']) {
        $vroot_folders[$forum['forum_id']] =
            addslashes(strip_tags($forum['name'])) .
            " (vroot {$forum['forum_id']})";
    } else {
        if (empty($forum['folder_flag'])) {
            $vroot2forums[$forum['vroot']][$forum['forum_id']] = $forum;
        }
    }
}
$forum_js_parts = array();
foreach ($vroot2forums as $vroot => $forums) {
    foreach ($forums as $fid => $forum) {
        if (!empty($forum['folder_flag'])) continue;
        $path = $forum['forum_path'];
        array_shift($path);
        $name = strip_tags(implode("::", $path));
        $vroot2forums[$vroot][$fid]["strpath"] = $name;
        $forum_js_parts[$vroot][] =
            "'{$forum['forum_id']}':'".addslashes($name)."'";
    }
}
$vroot_js_parts = array();
foreach($forum_js_parts as $vroot => $parts) {
    $vroot_js_parts[] = "'$vroot': {" . implode(', ', $parts) . "}";
}
$vroot_js = "{".implode(', ', $vroot_js_parts)."}";

// Create a vroot drop down menu if there are vroots available.
if (!empty($vroot_folders))
{
    $vroots = array(
        -1 => "Any vroot",
         0 => "Top level forum folder"
    );
    foreach ($vroot_folders as $vroot_folder_id => $vroot_path) {
        $vroots[$vroot_folder_id] = $vroot_path;
    }

    $frm->addrow(
        "Activate this rule for what vroot?",
        $frm->select_tag(
            "vroot", $vroots, (int)$rule['vroot'],
            'id="vroot_select" onchange="changeVroot()"'
        )
    );
}
// Otherwise, just put in a hidden vroot variable.
else {
    $frm->hidden("vroot", 0);
    $rule['vroot'] = 0;
}

// Create a forum drop down menu to allow selecting a specific forum.
$initforums = array(-1 => "Any forum");
if (!empty($vroot2forums[$rule['vroot']])) {
    foreach ($vroot2forums[$rule['vroot']] as $f) {
        $initforums[$f['forum_id']] = $f['strpath'];
    }
}
$frm->addrow(
    "Activate this rule for what forum?",
    $frm->select_tag(
        "forum", $initforums, (int)$rule['forum'],
        'id="forum_select"'
    )
);

// Selection to specify for what situation this rule should be active.
$frm->addrow(
    'Activate this rule for what user occurrences?',
    $frm->checkbox(
        'enable_profile', 1,
        'for the displayed user on the user profile pages',
        $rule['enable_profile']
    ) . '<br/>' .
    $frm->checkbox(
        'enable_read', 1,
        'for message authors on the read pages',
        $rule['enable_read']
    ) . '<br/>' .
    $frm->checkbox(
        'enable_list', 1,
        'for the authors and recent authors on the list page',
        $rule['enable_list']
    ) . '<br/>' .
    $frm->checkbox(
        'enable_user', 1,
        'for the authenticated user on every page',
        $rule['enable_user']
    )
);

$frm->addsubbreak(
    'Matching criteria for this rule (leave the field empty
     to ignore a criterium)'
);

// Selection so specify what "post count" means.
if (empty($vroot_folders)) {
    $frm->addrow("The post count for a user is defined as",
        $frm->select_tag("scope", array(
            "GLOBAL" => "Total number of posts in all forums",
            "FORUM"  => "Number of posts in the active forum"
        ), $rule['scope'])
    );
} else {
    $frm->addrow("The post count for a user is defined as",
        $frm->select_tag("scope", array(
            "GLOBAL" => "Total number of posts in all vroots",
            "VROOT"  => "Number of posts in the active vroot",
            "FORUM"  => "Number of posts in the active forum"
        ), $rule['scope'])
    );
}

$frm->addrow("The post count is equal to or more than (>=)",
             $frm->text_box("postsgte", $rule['postsgte'], 8) . ' posts');
$frm->addrow("The post count is less than (<)",
             $frm->text_box("postslt", $rule['postslt'], 8) . ' posts');

$frm->addrow("The user registration date is equal to or more than (>=)",
             $frm->text_box('reggte', $rule['reggte'], 8) . ' days ago');
$frm->addrow("The user registered less than (<)",
             $frm->text_box('reglt', $rule['reglt'], 8) . ' days ago');

if (empty($PHORUM['track_user_activity'])) {

}
$frm->addrow("The user's last activity is equal to or more than (>=)",
             $frm->text_box('activegte', $rule['activegte'], 8) . ' days ago');
$frm->addrow("The user's last activity is less than (<)",
             $frm->text_box('activelt', $rule['activelt'], 8) . ' days ago');

$frm->addrow(
    "The user type is",
    $frm->select_tag('user_type', array(
        ''      => 'Any type of user',
        'mod'   => 'A moderator or administrator',
        'admin' => 'An administrator'
    ),
    $rule['user_type'])
);

$frm->addrow(
    "The user status is",
    $frm->select_tag('user_active', array(
        -1                   => 'Any status',
        PHORUM_USER_ACTIVE   => 'Active',
        PHORUM_USER_INACTIVE => 'Deactivated'
    ),
    $rule['user_active'])
);

$row = $frm->addrow(
    "The user is identified by",
    $frm->select_tag('user_match_type', array(
        'user_id'  => 'User ID',
        'username' => 'Username'
    ),
    $rule['user_match_type']) . ' ' .
    $frm->text_box('user_match', $rule['user_match'], 25)
);
$frm->addhelp(
    $row, "Indentify a specific user",
    "This criterium can be used to define one or more specific users for
     the tagging rule, based on their user_id or username field. If you
     want to enter more than one user_id or username, then you can
     add multiple, separated by a comma."
);

$groups = phorum_db_get_groups(0, TRUE);
if (! empty($groups))
{
    $group_options = array(0 => 'Any group');
    foreach ($groups as $id => $group) {
        $group_options[$id] = $group['name'];
    }
    $row = $frm->addrow(
        "The user group is",
        $frm->select_tag('user_group', $group_options, $rule['user_group'])
    );
} else {
    $row = $frm->addrow(
        'The user group is', 'no groups defined yet'
    );
}

$frm->addrow(
    "The User-Id ban list status",
    $frm->select_tag('ban_list', array(
            -1 => 'Any status',
             1 => 'Banned',
             2 => 'Not banned'
        ),
        $rule['ban_list']
    )
);

$frm->addsubbreak(
    "The HTML code to put in a template variable if the criteria are met"
);

$row = $frm->addrow(
    "The template variable name to fill",
    $frm->text_box("tpl_var", $rule['tpl_var'], 20)
);
$frm->addhelp(
    $row, "Template variable to fill",
    "If this rule matches its criteria, then a variable will be added to
     the template data. This option is used to configure the name of this
     template variable that will be added. <i>We recommend to always use
     an upper case variable name to prevent collissions with existing
     fields in the user data.</i><br/>
     <br/>
     In the examples below, we show you what template variables will be
     filled if you configure \"FOOBAR\" as the variable name to use here.<br/>
     <br/>
     <b>for the user profile page</b><br/>
     <br/>
     {PROFILE->FOOBAR}<br/>
     <br/>
     <b>for messages that are read</b><br/>
     <br/>
     all messages in a flat view read page<br/>
     {MESSAGES->user->FOOBAR}<br/>
     <br/>
     currently viewed message in a threaded view read page<br/>
     {MESSAGE->user->FOOBAR}<br/>
     <br/>
     the topic starting message in every read page <br/>
     {TOPIC->user->FOOBAR}<br/>
     <br/>
     <b>for the author and recent author in the list page</b><br/>
     <br/>
     the message author:<br/>
     {MESSAGES->user->FOOBAR}<br/>
     <br/>
     The most recent author:<br/>
     {MESSAGES->recent_user->FOOBAR}<br/>
     <br/>
     <small>(no tags are added for anonymous authors)</small><br/>
     <br/>
     <b>for the authenticated user</b><br/>
     <br/>
     {USER->FOOBAR}");

$row = $frm->addrow(
    "HTML code to put in the template variable",
    $frm->textarea("tpl_html", $rule['tpl_html'], 40, 8,'style="width:95%"')
);
$frm->addhelp(
    $row, "HTML code to put in the extra template variable",
    "If this rule matches its criteria, then the HTML code that is configured
     in this option will be put in one or more template variables
     (see also the help for the previous option).<br/>
     <br/>
     You can use some special strings in the code, which will be replaced
     automatically:
     <ul>
     <li>%count% = the matching post count for the user</li>
     <li>%http_path% = the URL to the root of the Phorum install</li>
     </ul>"
);

$frm->show();

?>

<script type="text/javascript">
//<![CDATA[
var vroot_forums = <?php print $vroot_js ?>;

function changeVroot()
{
    var vsel = document.getElementById('vroot_select');
    var fsel = document.getElementById('forum_select');
    if (!vsel || !fsel) return;

    var vroot = vsel.options[vsel.selectedIndex].value;

    var i = 0;
    fsel.options.length = 0;

    fsel.options[i++] = new Option('Any forum', -1);

    if (vroot >= 0 && vroot_forums[vroot]) {
        for (var nr in vroot_forums[vroot]) {
            fsel.options[i++] = new Option(vroot_forums[vroot][nr], nr);
        }
    }

}

//changeVroot();
//]]>
</script>
