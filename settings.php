<?php

if (!defined("PHORUM_ADMIN")) return;

require_once('./mods/user_tagging/api.php');

// Forums API was renamed in 5.3.
require_once('./include/api/forums.php');

// Handle module installation:
// Load the module installation code if this was not yet done.
// The installation code will take care of automatically adding
// the custom profile field that is needed for this module.
if (! isset($PHORUM["mod_user_tagging_installed"]) ||
    ! $PHORUM["mod_user_tagging_installed"]) {
    include("./mods/user_tagging/install.php");
}

user_tagging_init();

// Easy way to build URLs for admin panels for this module.
function user_tagging_admin_url($args = array())
{
  if (!is_array($args)) {
      $args = array($args);
  }

  $args = array_merge(array(
      'module=modsettings',
      'mod=user_tagging'
  ), $args);

  print phorum_admin_build_url($args);
}
?>

<h1>User Tagging Settings</h1>

<div style="padding-bottom: 5px">
  <a href="<?php user_tagging_admin_url() ?>">List of rules</a> |
  <a href="<?php user_tagging_admin_url('edit_rule=1') ?>">Add a new rule</a> |
  <a href="<?php user_tagging_admin_url('list_ignore=1') ?>">Vroots and forums to ignore for post counts</a> |
  <a href="<?php print user_tagging_admin_url('recalculate=1') ?>">Recalculate post counts</a>
</div>

<?php

if (isset($_GET['copy_rule'])) {
    include("./mods/user_tagging/settings/copy_rule.php");
} elseif (isset($_POST['delete_rule']) || isset($_GET['delete_rule'])) {
    include("./mods/user_tagging/settings/delete_rule.php");
} elseif (isset($_POST['edit_rule']) || isset($_GET['edit_rule'])) {
    include("./mods/user_tagging/settings/edit_rule.php");
} elseif (isset($_POST['list_ignore']) || isset($_GET['list_ignore'])) {
    include("./mods/user_tagging/settings/list_ignore.php");
} elseif (isset($_POST['recalculate']) || isset($_GET['recalculate'])) {
    include("./mods/user_tagging/settings/recalculate.php");
} else {
    include("./mods/user_tagging/settings/list_rules.php");
}

?>
