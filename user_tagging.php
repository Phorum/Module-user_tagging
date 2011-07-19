<?php

if (!defined('PHORUM')) return;

require_once('./mods/user_tagging/api.php');

// Handle module installation:
// Load the module installation code if this was not yet done.
// The installation code will take care of automatically adding
// the custom profile field that is needed for this module.
if (! isset($PHORUM["mod_user_tagging_installed"]) ||
    ! $PHORUM["mod_user_tagging_installed"]) {
    include("./mods/user_tagging/install.php");
}

// Update the post counters for the user.
function phorum_mod_user_tagging_after_post($message)
{
    // Do not count the post when it is not yet approved.
    if ($message['status'] != PHORUM_STATUS_APPROVED) {
        return $message;
    }

   user_tagging_modify($message, +1);
   return $message;
}

// Update the post counters for messages that needed approval.
function phorum_mod_user_tagging_after_approve($data)
{
    list ($message, $approve_type) = $data;

    // Only count the post if it was on hold
    // (fresh post, not yet approved).
    if ($message['status'] == PHORUM_STATUS_HOLD) {
        user_tagging_modify($message, +1);
    }

   return $data;
}

// Apply user tagging rules for the user profile page.
function phorum_mod_user_tagging_profile($profile)
{
    global $PHORUM;

    if (empty($PHORUM['mod_user_tagging']['rules']) ||
        empty($profile['mod_user_tagging'])) return $profile;

    foreach ($PHORUM['mod_user_tagging']['rules'] as $rule)
    {
        if (empty($rule['enable_profile'])) continue;

        $value = user_tagging_process_rule($rule, $profile);

        if ($value !== NULL) {
            $profile[$rule['tpl_var']] = $value;
        }
    }

    return $profile;
}

// Apply user tagging rules for the message read page.
function phorum_mod_user_tagging_read($messages)
{
    global $PHORUM;

    if (empty($PHORUM['mod_user_tagging']['rules'])) return $messages;

    foreach ($PHORUM['mod_user_tagging']['rules'] as $rule)
    {
        if (empty($rule['enable_read'])) continue;

        foreach ($messages as $id => $message)
        {
            if (empty($message['user_id'])) continue;

            $value = user_tagging_process_rule($rule, $message['user']);

            if ($value !== NULL) {
                $messages[$id]['user'][$rule['tpl_var']] = $value;
            }
        }
    }

    return $messages;
}

// Apply user tagging rules for authors and recent authors on the list page.
function phorum_mod_user_tagging_list($messages)
{
    global $PHORUM;

    if (empty($PHORUM['mod_user_tagging']['rules'])) return $messages;

    $users = array();

    foreach ($PHORUM['mod_user_tagging']['rules'] as $rule)
    {
        if (empty($rule['enable_list'])) continue;

        // Collect the users in the messages. We need to retrieve the full
        // user information for processing the rules.
        if (empty($users))
        {
            // First, collect all involved user ids.
            foreach ($messages as $id => $message) {
              if (!empty($message['user_id'])) {
                $users[$message['user_id']] = $message['user_id'];
              }
              if (!empty($message['recent_user_id'])) {
                $users[$message['recent_user_id']] = $message['recent_user_id'];
              }
            }

            // If no users were found, then there are no list rules
            // that need processing.
            if (empty($users)) return $messages;

            // Retrieve the user information.
            $users = phorum_api_user_get($users);
        }

        foreach ($messages as $id => $message)
        {
            if (!empty($message['user_id']))
            {
                if (empty($messages[$id]['user'])) {
                    $messages[$id]['user'] = array();
                }
                $value = user_tagging_process_rule(
                    $rule, $users[$message['user_id']]
                );
                if ($value !== NULL) {
                    $messages[$id]['user'][$rule['tpl_var']] = $value;
                }
            }

            if (!empty($message['recent_user_id']))
            {
                if (empty($messages[$id]['recent_user'])) {
                    $messages[$id]['recent_user'] = array();
                }
                $value = user_tagging_process_rule(
                    $rule, $users[$message['recent_user_id']]
                );
                if ($value !== NULL) {
                    $messages[$id]['recent_user'][$rule['tpl_var']] = $value;
                }
            }
        }
    }

    return $messages;
}

// Apply user tagging rules for the active user.
function phorum_mod_user_tagging_common_post_user()
{
    global $PHORUM;

    if (empty($PHORUM['mod_user_tagging']['rules']) ||
        empty($PHORUM['user']['user_id'])) return;

    foreach ($PHORUM['mod_user_tagging']['rules'] as $rule)
    {
        if (empty($rule['enable_user'])) continue;

        $value = user_tagging_process_rule($rule, $PHORUM['user']);
        if ($value !== NULL) {
            $PHORUM['user'][$rule['tpl_var']] = $value;
        }
    }
}

?>
