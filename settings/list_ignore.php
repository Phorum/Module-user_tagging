<?php

if (!defined('PHORUM_ADMIN')) return;

// Build a list of vroot folders and a javascript structure, describing
// the forums inside the vroot.
$forums = phorum_api_forums_get();
$vroot_folders = array();
$vroot2forums  = array();
$forumid2name  = array(
    0 => "<img src=\"{$PHORUM['http_path']}/mods/user_tagging/settings/folder.gif\" style=\"border:0\"/>&nbsp;Top level forum folder"
);
foreach ($forums as $forum)
{
    if ($forum['forum_id'] == $forum['vroot'])
    {
        $forumid2name[$forum['forum_id']]  =
            "<img src=\"{$PHORUM['http_path']}/mods/user_tagging/settings/folder.gif\" style=\"border:0\"/>&nbsp;" .
            addslashes(strip_tags($forum['name'])) .
            " (vroot {$forum['forum_id']})";

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
        $name = strip_tags(implode("::", $path));
        $forumid2name[$fid] = "<img src=\"{$PHORUM['http_path']}/mods/user_tagging/settings/forum.gif\" style=\"border:0\"/>&nbsp;$name";
        array_shift($path);
        $name = strip_tags(implode("::", $path));
        $vroot2forums[$vroot][$fid]["strpath"] = $name;
        $forum_js_parts[$vroot][] = "'{$forum['forum_id']}':'".addslashes($name)."'";
    }
}
$vroot_js_parts = array();
foreach($forum_js_parts as $vroot => $parts) {
    $vroot_js_parts[] = "'$vroot': {" . implode(', ', $parts) . "}";
}
$vroot_js = "{".implode(', ', $vroot_js_parts)."}";

// Retrieve current ignore list.
$ignores = user_tagging_get_ignore_list();

// Update the ignore list if required.
if (isset($_POST['delete_id']) || isset($_GET['delete_id'])) {
    if (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
    } else {
        $id = (int) $_GET['delete_id'];
    }
    unset($ignores[$id]);
    user_tagging_store_ignore_list($ignores);
}
if (isset($_POST['add'])) {
    if (!empty($_POST['forum']) && $_POST['forum'] != -1) {
        $id = (int) $_POST['forum'];
        $ignores[$id] = $id;
        user_tagging_store_ignore_list($ignores);
    }
    elseif (isset($_POST['vroot']) && $_POST['vroot'] != -1) {
        $id = (int) $_POST['vroot'];
        $ignores[$id] = $id;
        user_tagging_store_ignore_list($ignores);
    }
}

?>

<div class="PhorumAdminTitle">
  List of vroots and forums for which to ignore posts for the post counts
</div>

<div style="padding: 10px 0px">
  If you have forums or vroots that you do not want to include in
  the post counts for this module, then you can list those here.
  This could for example be useful if you have a sandbox forum,
  which can be used by your users to try out forum features.
</div>

<form action="<?php user_tagging_admin_url() ?>" method="post">
<input type="hidden" name="module"      value="modsettings"/>
<input type="hidden" name="mod"         value="user_tagging"/>
<input type="hidden" name="list_ignore" value="1" />

<table border="0" cellspacing="2" cellpadding="3" width="100%">
<tr>
  <td class="PhorumAdminTableHead">Name</td>
  <td class="PhorumAdminTableHead">Actions</td>
</tr>

<?php

if (empty($ignores))
{ ?>
    <tr>
      <td colspan="2" class="PhorumAdminTableRow">
        <i>The ignore list is empty</i>
      </td>
    </tr> <?php
}
else foreach ($ignores as $id)
{
    if (!isset($forumid2name[$id])) continue;
    ?>
    <tr>
      <td class="PhorumAdminTableRow">
        <?php print $forumid2name[$id] ?>
      </td>
      <td class="PhorumAdminTableRow">
        <a href="<?php user_tagging_admin_url(array('list_ignore=1', 'delete_id='.$id)) ?>">Delete</a>
      </td>
    </tr> <?php
}


require_once('./include/admin/PhorumInputForm.php');
$frm = new PhorumInputForm ("", "post", "Save rule");
$select = '';

// Create a vroot drop down menu if there are vroots available.
if (!empty($vroot_folders))
{
    $vroots = array(
        -1 => "Select vroot folder",
         0 => "Top level forum folder"
    );
    foreach ($vroot_folders as $vroot_folder_id => $vroot_path) {
        $vroots[$vroot_folder_id] = $vroot_path;
    }

    $select .= $frm->select_tag("vroot", $vroots, -1, 'id="vroot_select" onchange="changeVroot()"');
    $initforums = array(
        -1 => "Any forum"
    );
} else {
    $initforums = array(
        -1 => "Select forum"
    );
    foreach ($vroot2forums[0] as $f) {
        $initforums[$f['forum_id']] = $f['strpath'];
    }
}

// Create a forum drop down menu to allow selecting a specific forum.
$select .= $frm->select_tag("forum", $initforums, -1, 'id="forum_select"');

?>
<tr>
  <td colspan="2" class="PhorumAdminTableRow">
    <?php print $select ?>
    <input type="submit" name="add" value="Add" />
  </td>
</tr>

</table>

</form>

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

