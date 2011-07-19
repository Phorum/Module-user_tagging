<?php

if (!defined('PHORUM_ADMIN')) return;

// Handle a posted form.
if (!empty($_POST['delete_rule']))
{
    if (!empty($_POST['id']) && !empty($_POST['delete:yes'])) {
        user_tagging_delete_rule($_POST['id']);
        phorum_admin_okmsg("The rule was deleted successfully.");
    }
    include("./mods/user_tagging/settings/list_rules.php");
    return;
}

if (!isset($_GET['id']) ||
    empty($PHORUM['mod_user_tagging']['rules'][$_GET['id']])) {
    include("./mods/user_tagging/settings/list_rules.php");
    return;
}

$rule = $PHORUM['mod_user_tagging']['rules'][$_GET['id']];

// Show a confirmation page.
?>
<div class="PhorumAdminTitle">Delete a rule</div>
<br/>
Are you sure you want to delete the rule
"<?php print htmlspecialchars($rule['name']) ?>"?
<br/>
<br/>
<form action="<?php print $base_url ?>" method="post">
<input type="hidden" name="module"      value="modsettings"/>
<input type="hidden" name="mod"         value="user_tagging"/>
<input type="hidden" name="delete_rule" value="1" />
<input type="hidden" name="id"          value="<?php print $rule['id'] ?>" />
<input type="submit" name="delete:yes"  value="Yes" />
<input type="submit" name="delete:no"   value="No" />
</form>

