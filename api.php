<?php

// Phorum 5.2
if (file_exists('./include/profile_functions.php')) {
    include_once './include/profile_functions.php';
    $GLOBALS['PHORUM']['ban_check_func'] = 'phorum_check_ban_lists';
}
// Phorum 5.3
elseif (file_exists('./include/api/ban.php')) {
    include_once './include/api/ban.php';
    $GLOBALS['PHORUM']['ban_check_func'] = 'phorum_api_ban_check';
}
// Phorum ???
else {
  trigger_error(
    'user_tagging: Cannot find a banlist checking library.',
    E_USER_ERROR
  );
}

function user_tagging_init()
{
    global $PHORUM;

    // Initialize settings array.
    if (empty($PHORUM['mod_user_tagging'])) {
        $PHORUM['mod_user_tagging'] = array();
    }
    if (empty($PHORUM['mod_user_tagging']['rules'])) {
        $PHORUM['mod_user_tagging']['rules'] = array();
    }
    if (empty($PHORUM['mod_user_tagging']['ignore'])) {
        $PHORUM['mod_user_tagging']['ignore'] = array();
    }
}

function user_tagging_rulesort($a, $b)
{
    $a1 = strtolower($a['name']);
    $b1 = strtolower($b['name']);
    if ($a1 == $b1) return 0;
    if ($a1 >  $b1) return +1;
    return -1;
}

function user_tagging_get_rules()
{
    global $PHORUM;
    if (empty($PHORUM['mod_user_tagging']['rules'])) {
        return array();
    }

    $rules = $PHORUM['mod_user_tagging']['rules'];
    uasort($rules, 'user_tagging_rulesort');

    return $rules;
}

function user_tagging_store_rule(&$rule)
{
    global $PHORUM;

    // Determine the rule id to use.
    if (empty($rule['id'])) {
        $store_id = 1;
        if (!empty($PHORUM['mod_user_tagging']['rules'])) {
            $keys = array_keys($PHORUM['mod_user_tagging']['rules']);
            rsort($keys, SORT_NUMERIC);
            $store_id = $keys[0] + 1;
        }
        $rule['id'] = $store_id;
    }

    $PHORUM['mod_user_tagging']['rules'][$rule['id']] = $rule;

    phorum_db_update_settings(array(
        'mod_user_tagging' => $PHORUM['mod_user_tagging']
    ));
}

function user_tagging_get_ignore_list()
{
    global $PHORUM;
    if (empty($PHORUM['mod_user_tagging']['ignore'])) {
        return array();
    }

    return $PHORUM['mod_user_tagging']['ignore'];
}

function user_tagging_store_ignore_list($ignore)
{
    global $PHORUM;

    $PHORUM['mod_user_tagging']['ignore'] = $ignore;

    phorum_db_update_settings(array(
        'mod_user_tagging' => $PHORUM['mod_user_tagging']
    ));
}


function user_tagging_delete_rule($rule)
{
    global $PHORUM;

    if (is_array($rule)) {
        if (empty($rule['id'])) return;
        $rule = $rule['id'];
    }
    if (empty($rule)) return;

    if (isset($PHORUM['mod_user_tagging']['rules'][$rule])) {
        unset($PHORUM['mod_user_tagging']['rules'][$rule]);
        phorum_db_update_settings(array(
            'mod_user_tagging' => $PHORUM['mod_user_tagging']
        ));
    }
}

// Update the counters for a post.
function user_tagging_modify($message, $delta)
{
    global $PHORUM;

    // Only for registered users.
    if (empty($message['user_id'])) return;
    $user = $PHORUM['user']['user_id'] == $message['user_id']
          ? $PHORUM['user']
          : phorum_api_user_get($message['user_id']);

    // Get the info for the forum.
    $forums = phorum_db_get_forums($message['forum_id']);
    $forum = $forums[$message['forum_id']];
    $vroot = $forum['vroot'];

    // Initialize counter structure.
    if (empty($user['mod_user_tagging'])) {
        $counts = array();
    } else {
        $counts = $user['mod_user_tagging'];
    }
    if (!isset($counts['forum']))  $counts['forum']  = array();
    if (!isset($counts['vroot']))  $counts['vroot']  = array();
    if (!isset($counts['global'])) $counts['global'] = 0;
    if (!isset($counts['vroot'][$vroot])) $counts['vroot'][$vroot] = 0;
    if (!isset($counts['forum'][$message['forum_id']])) {
        $counts['forum'][$message['forum_id']] = 0;
    }

    // Check for ignore rule.
    $ignores = $PHORUM['mod_user_tagging']['ignore'];
    if (isset($ignores[$message['forum_id']]) ||
        isset($ignores[$vroot])) return;

    // Update vroot counter.
    $counts['vroot'][$vroot] += $delta;
    if ($counts['vroot'][$vroot] < 0) {
        $counts['vroot'][$vroot] = 0;
    }

    // Update forum counter.
    $counts['forum'][$message['forum_id']] += $delta;
    if ($counts['forum'][$message['forum_id']] < 0) {
        $counts['forum'][$message['forum_id']] = 0;
    }

    // Update global counter.
    $counts['global'] += $delta;
    if ($counts['global'] < 0) {
        $counts['global']  = 0;
    }

    // Store the new counter info.
    phorum_api_user_save(array(
        'user_id' => $user['user_id'],
        'mod_user_tagging' => $counts
    ));

    // Make the info available to the template engine, if we're handling
    // the active user.
    if ($PHORUM['user']['user_id'] == $message['user_id']) {
        $PHORUM['user']['mod_user_tagging'] = $counts;
    }
}

// Check if a rule applies or not.
function user_tagging_process_rule($rule, $user)
{
    global $PHORUM;

    $moddata = isset($user['mod_user_tagging'])
             ? $user['mod_user_tagging'] : array();

    // Check forum constraint.
    if ($rule['forum'] != -1) {
        if ($rule['forum'] != $PHORUM['forum_id']) return NULL;
    }
    // Check vroot constraint.
    elseif ($rule['vroot'] != -1) {
        if ($rule['vroot'] != $PHORUM['vroot']) return NULL;
    }

    // Determine the post counter to look at.
    $count = NULL;
    switch ($rule['scope'])
    {
        case 'VROOT':
            $count = empty($moddata['vroot'][$PHORUM['vroot']])
                   ? 0 : $moddata['vroot'][$PHORUM['vroot']];
            break;

        case 'FORUM':
            $count = empty($moddata['forum'][$PHORUM['forum_id']])
                   ? 0 : $modata['forum'][$PHORUM['forum_id']];
            break;

        case 'GLOBAL':
            $count = empty($moddata['global'])
                   ? 0 : $moddata['global'];
            break;
    }

    if ($count === NULL) return NULL; // should not happen

    // Check if all the conditions match.
    $match = 0;

    // Do post count checks.
    if ( ($rule['postsgte'] == '' || $count >= $rule['postsgte']) &&
         ($rule['postslt']  == '' || $count < $rule['postslt']) ) {
        $match ++;
    }

    // Do registration date checks.
    if ( $rule['reggte'] == '' && $rule['reglt'] == '')
    {
        $match ++;
    }
    else
    {
        $date_added = isset($user['raw_date_added'])
                    ? $user['raw_date_added'] : $user['date_added'];
        $days = (time() - $date_added) / 86400;
        if ( ($rule['reggte'] == '' || $days >= $rule['reggte']) &&
             ($rule['reglt']  == '' || $days < $rule['reglt']) ) {
            $match ++;
        }
    }

    // Do last activity checks.
    if ( $rule['activegte'] == '' && $rule['activelt'] == '')
    {
        $match ++;
    }
    else
    {
        $date_last_active = isset($user['raw_date_last_active'])
                    ? $user['raw_date_last_active'] : $user['date_last_active'];
        $days = (time() - $date_last_active) / 86400;
        if ( ($rule['activegte'] == '' || $days >= $rule['activegte']) &&
             ($rule['activelt']  == '' || $days < $rule['activelt']) ) {
            $match ++;
        }
    }

    // Do user type checks.
    if ($rule['user_type'] == '')
    {
        $match ++;
    }
    elseif ($rule['user_type'] == 'mod')
    {
        if ($user['admin']) {
            $match ++;
        } else {
            $is_moderator = phorum_api_user_check_access(
                PHORUM_USER_ALLOW_MODERATE_MESSAGES, 0, $user
            );
            if ($is_moderator) {
                $match ++;
            }
        }
    }
    elseif ($rule['user_type'] == 'admin')
    {
        if ($user['admin']) {
            $match ++;
        }
    }

    // Do user active status check.
    if ($rule['user_active'] == -1) {
        $match ++;
    } elseif ($rule['user_active'] == $user['active']) {
        $match ++;
    }

    // Do user_id check.
    if (empty($rule['user_ids'])) {
        $match ++;
    } elseif (isset($rule['user_ids'][$user['user_id']])) {
        $match ++;
    }

    // Do user_group check.
    if (empty($rule['user_group'])) {
        $match ++;
    } else {
        // Retrieve user's group information if we don't have it already.
        if (!isset($user['groups'])) {
            $user = phorum_db_user_get($user['user_id'], TRUE);
        }
        if (!empty($user['groups']) &&
            isset($user['groups'][$rule['user_group']])) {
            $match ++;
        }
    }

    // Do user ban list check.
    if (empty($rule['ban_list']) || $rule['ban_list'] == -1) {
        $match ++;
    } else {
        $func = $GLOBALS['PHORUM']['ban_check_func'];
        $ban_list_check = $func($user['user_id'], PHORUM_BAD_USERID);
        if (($rule['ban_list'] == 1 && !$ban_list_check) ||
            ($rule['ban_list'] == 2 &&  $ban_list_check)) {
            $match ++;
        }
    }

    if ($match != 8)  return NULL;

    // Format the user tag for this rule.
    $tag = str_replace(array(
        '%count%',
        '%http_path%'
    ), array(
        $count,
        $PHORUM['http_path']
    ), $rule['tpl_html']);

    return $tag;
}

?>
