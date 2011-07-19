<?php if (!defined('PHORUM_ADMIN')) return; ?>

<div class="PhorumAdminTitle">
  List of configured rules
</div>

<table border="0" cellspacing="2" cellpadding="3" width="100%">
<tr>
  <td class="PhorumAdminTableHead">Name</td>
  <td class="PhorumAdminTableHead">Actions</td>
</tr>

<?php
$rules = user_tagging_get_rules();

if (empty($rules))
{ ?>
    <tr>
      <td colspan="2" class="PhorumAdminTableRow">
        <i>There are no rules configured</i>
      </td>
    </tr> <?php
}
else foreach ($rules as $rule)
{
    ?>
    <tr>
      <td class="PhorumAdminTableRow">
        <a href="<?php user_tagging_admin_url(array('edit_rule=1', 'id='.$rule['id'])) ?>">
          <?php print htmlspecialchars($rule['name']) ?>
        </a>
      </td>
      <td class="PhorumAdminTableRow">
        <a href="<?php user_tagging_admin_url(array('edit_rule=1', 'id='.$rule['id'])) ?>">Edit</a> |
        <a href="<?php user_tagging_admin_url(array('copy_rule=1', 'id='.$rule['id'])) ?>">Copy</a> |
        <a href="<?php user_tagging_admin_url(array('delete_rule=1', 'id='.$rule['id'])) ?>">Delete</a>
      </td>
    </tr> <?php
}
?>

<tr>
  <td class="PhorumAdminTableRow">
    &nbsp;
  </td>
  <td class="PhorumAdminTableRow">
    <a href="<?php user_tagging_admin_url('edit_rule=1') ?>">Add a new rule</a>
  </td>
</tr>

</table>

