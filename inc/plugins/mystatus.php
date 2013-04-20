<?php
/**
 * MyStatus Plugin File
 *
 * @package MyStatus
 * @author Euan T. <euan@euantor.com>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @version 1.1
 */

if (!defined('IN_MYBB')) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

define('MYSTATUS_PLUGIN_PATH', MYBB_ROOT.'inc/plugins/MyStatus/');

if (!defined('PLUGINLIBRARY')) {
    define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');
}

function mystatus_info()
{
    global $lang;

    if (!isset($lang->mystatus)) {
        $lang->load('mystatus');
    }

    return array(
        'name'          => $lang->mystatus_info_name,
        'description'   => $lang->mystatus_info_desc,
        'website'       => 'http://www.mybbstuff.com',
        'author'        => 'Euan T.',
        'authorsite'    => 'http://euantor.com',
        'version'       => '1.1',
        'guid'          => 'efe2b0b1198f4642e842bf2ee546ad74',
        'compatibility' => '16*'
    );
}

function mystatus_install()
{
    global $db, $cache;

    $plugin_info = mystatus_info();
    $euantor_plugins = $cache->read('euantor_plugins');
    $euantor_plugins['mystatus'] = array(
        'title'     =>  'MyStatus',
        'version'   =>  $plugin_info['version'],
    );
    $cache->update('euantor_plugins', $euantor_plugins);

    if (!$db->table_exists('statuses')) {
        $collation = $db->build_create_table_collation();
        $db->write_query(
            "CREATE TABLE `".TABLE_PREFIX."statuses` (
                `sid` INT(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `uid` INT(10) NOT NULL,
                `status` TEXT NOT NULL,
                `dateline` BIGINT(30) NOT NULL,
                `tweetid` VARCHAR(20) DEFAULT 0
            ) ENGINE=MyISAM{$collation};"
        );
    }

    if (!$db->table_exists('status_comments')) {
        $collation = $db->build_create_table_collation();
        $db->write_query(
            "CREATE TABLE `".TABLE_PREFIX."status_comments` (
                id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(10) unsigned NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME
            ) ENGINE=MyISAM{$collation};"
        );
    }

    if (!$db->field_exists('mystatus_can_use', 'usergroups')) {
        $db->add_column('usergroups', 'mystatus_can_use', "INT(1) NOT NULL DEFAULT '0'");
    }

    if (!$db->field_exists('mystatus_can_comment', 'usergroups')) {
        $db->add_column('usergroups', 'mystatus_can_comment', "INT(1) NOT NULL DEFAULT '0'");
    }

    if (!$db->field_exists('mystatus_can_moderate', 'usergroups')) {
        $db->add_column('usergroups', 'mystatus_can_moderate', "INT(1) NOT NULL DEFAULT '0'");
    }

    if (!$db->field_exists('mystatus_can_delete_own', 'usergroups')) {
        $db->add_column('usergroups', 'mystatus_can_delete_own', "INT(1) NOT NULL DEFAULT '0'");
    }

    $db->write_query('UPDATE '.TABLE_PREFIX.'usergroups SET `mystatus_can_delete_own` = 1, `mystatus_can_use` = 1, `mystatus_can_comment` = 1 WHERE gid NOT IN (1, 7);');
    $db->write_query('UPDATE '.TABLE_PREFIX.'usergroups SET `mystatus_can_moderate` = 1 WHERE gid IN (3, 4, 6);');
    $cache->update_usergroups();

    if (!$db->field_exists('mystatus_twitter_posting_enabled', 'users')) {
        $db->add_column('users', 'mystatus_twitter_posting_enabled', "INT(1) NOT NULL DEFAULT '0'");
    }

    if (!$db->field_exists('mystatus_twitter_oauth_token', 'users')) {
        $db->add_column('users', 'mystatus_twitter_oauth_token', 'VARCHAR(90)');
    }

    if (!$db->field_exists('mystatus_twitter_oauth_token_secret', 'users')) {
        $db->add_column('users', 'mystatus_twitter_oauth_token_secret', 'VARCHAR(90)');
    }
}

function mystatus_is_installed()
{
    global $db;

    return $db->table_exists('statuses');
}

function mystatus_uninstall()
{
    global $db, $cache, $PL;

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message('The selected plugin could not be uninstalled because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.', 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    if ($db->table_exists('statuses')) {
        $db->drop_table('statuses');
    }

    if ($db->table_exists('status_comments')) {
        $db->drop_table('status_comments');
    }

    if ($db->field_exists('mystatus_can_use', 'usergroups')) {
        $db->drop_column('usergroups', 'mystatus_can_use');
    }

    if ($db->field_exists('mystatus_can_moderate', 'usergroups')) {
        $db->drop_column('usergroups', 'mystatus_can_moderate');
    }

    if ($db->field_exists('mystatus_can_delete_own', 'usergroups')) {
        $db->drop_column('usergroups', 'mystatus_can_delete_own');
    }

    $cache->update_usergroups();

    if ($db->field_exists('mystatus_twitter_posting_enabled', 'users')) {
        $db->drop_column('users', 'mystatus_twitter_posting_enabled');
    }

    if ($db->field_exists('mystatus_twitter_oauth_token', 'users')) {
        $db->drop_column('users', 'mystatus_twitter_oauth_token');
    }

    if ($db->field_exists('mystatus_twitter_oauth_token_secret', 'users')) {
        $db->drop_column('users', 'mystatus_twitter_oauth_token_secret');
    }


    $PL->settings_delete('mystatus', true);
    $PL->templates_delete('mystatus');
}

function mystatus_activate()
{
    global $db, $lang, $PL;

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->myalerts_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    if ((int) $PL->version < 9) {
        flash_message('This plugin requires PluginLibrary 9 or newer', 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if (!isset($lang->mystatus)) {
        $lang->load('mystatus');
    }

    $PL->settings(
        'mystatus',
        $lang->setting_group_mystatus,
        $lang->setting_group_mystatus_desc,
        array(
            'min_length' => array(
                'title'       => $lang->mystatus_setting_min_length,
                'description' => $lang->mystatus_setting_min_length_desc,
                'value'       => '10',
                'optionscode' => 'text',
                ),
            'max_length' => array(
                'title'       => $lang->mystatus_setting_max_length,
                'description' => $lang->mystatus_setting_max_length_desc,
                'value'       => '140',
                'optionscode' => 'text',
                ),
            'index_num_recent' => array(
                'title'       => $lang->mystatus_setting_index_num_recent,
                'description' => $lang->mystatus_setting_index_num_recent_desc,
                'value'       => '10',
                'optionscode' => 'text',
                ),
            'profile_num_recent' => array(
                'title'       => $lang->mystatus_setting_profile_num_recent,
                'description' => $lang->mystatus_setting_profile_num_recent_desc,
                'value'       => '5',
                'optionscode' => 'text',
                ),
            'postbit' => array(
                'title'       => $lang->mystatus_setting_postbit,
                'description' => $lang->mystatus_setting_postbit_desc,
                'value'       => '1',
                ),
            'post_to_twitter' => array(
                'title'       => $lang->mystatus_setting_post_to_twitter,
                'description' => $lang->mystatus_setting_post_to_twitter,
                'value'       => '0',
                ),
            'twitter_consumer' => array(
                'title'       => $lang->mystatus_setting_twitter_consumer,
                'description' => $lang->mystatus_setting_twitter_consumer_desc,
                'value'       => '',
                'optionscode' => 'text',
                ),
            'twitter_consumer_secret' => array(
                'title'       => $lang->mystatus_setting_twitter_consumer_secret,
                'description' => $lang->mystatus_setting_twitter_consumer_secret_desc,
                'value'       => '',
                'optionscode' => 'text',
                ),
            )
    );

    // Templating, like a BAWS - http://www.euantor.com/185-templates-in-mybb-plugins
    $dir = new DirectoryIterator(dirname(__FILE__).'/MyStatus/templates');
    $templates = array();
    foreach ($dir as $file) {
        if (!$file->isDot() AND !$file->isDir() AND pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
            $templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
        }
    }

    $PL->templates(
        'mystatus',
        'MyStatus',
        $templates
    );

    require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
    find_replace_templatesets("member_profile", "#".preg_quote('{$signature}')."#i", '{$mystatus_latest_statuses}'."\n".'{$signature}');
    find_replace_templatesets("index", "#".preg_quote('{$boardstats}')."#i", '{$mystatus_latest_statuses}'."\n".'{$boardstats}');
    find_replace_templatesets("headerinclude", "#".preg_quote('{$newpmmsg}')."#i", '{$newpmmsg}'."\n".'{$mystatus_js}');
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'usertitle\']}')."#i", '{$post[\'userStatus\']}<br />'."\n".'{$post[\'usertitle\']}');
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'usertitle\']}')."#i", '{$post[\'userStatus\']}<br />'."\n".'{$post[\'usertitle\']}');
}

function mystatus_deactivate()
{
    require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
    find_replace_templatesets("member_profile", "#".preg_quote('{$mystatus_latest_statuses}'."\n")."#i", '');
    find_replace_templatesets("index", "#".preg_quote('{$mystatus_latest_statuses}'."\n")."#i", '');
    find_replace_templatesets("headerinclude", "#".preg_quote("\n".'{$mystatus_js}')."#i", '');
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'userStatus\']}'."\n")."#i", '');
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'userStatus\']}'."\n")."#i", '');
}

$plugins->add_hook('admin_user_groups_edit_graph_tabs', 'mystatus_usergroup_perms_tab');
function mystatus_usergroup_perms_tab(&$tabs)
{
    global $lang;

    if (!isset($lang->mystatus)) {
        $lang->load('mystatus');
    }

    $tabs['mystatus'] = $lang->group_mystatus;
}

$plugins->add_hook('global_start', 'mystatus_templates_cache');
function mystatus_templates_cache()
{
    global $mybb, $templatelist, $mystatus_js;

    if (isset($templatelist)) {
        $templatelist .= ',';
    }

    if (THIS_SCRIPT == 'member.php' AND $mybb->input['action'] == 'profile') {
        $templatelist .= 'mystatus_button_delete,mystatus_latest_statuses_row,mystatus_update_form,mystatus_latest_statuses,mystatus_latest_statuses_row_no_statuses';
        $mystatus_js = "<script type=\"text/javascript\" src=\"{$mybb->settings['bburl']}/jscripts/mystatus.js\"></script>";
    } elseif (THIS_SCRIPT == 'index.php') {
        $templatelist .= 'mystatus_button_delete,mystatus_latest_statuses_row,mystatus_update_form,mystatus_latest_statuses,mystatus_latest_statuses_row_no_statuses';
        $mystatus_js = "<script type=\"text/javascript\" src=\"{$mybb->settings['bburl']}/jscripts/mystatus.js\"></script>";
    } elseif (THIS_SCRIPT == 'usercp.php' AND $mybb->input['action'] == '') {
        $templatelist .= 'mystatus_usercp_nav_oauth';
    } elseif (THIS_SCRIPT == 'usercp.php' AND $mybb->input['action'] == 'mystatus-oauth') {
        $templatelist .= 'mystatus_usercp_nav_oauth,mystatus_usercp_oauth_button,mystatus_usercp_oauth';
    } elseif (THIS_SCRIPT == 'misc.php' AND $mybb->input['action'] == 'mystatus') {
        $templatelist .= 'mystatus_button_delete,mystatus_latest_statuses_row,mystatus_update_form,mystatus_latest_statuses,mystatus_statuses_page,mystatus_latest_statuses_row_no_statuses';
        $mystatus_js = "<script type=\"text/javascript\" src=\"{$mybb->settings['bburl']}/jscripts/mystatus.js\"></script>";
    }
}

$plugins->add_hook('admin_user_groups_edit_graph', 'mystatus_usergroup_perms');
function mystatus_usergroup_perms()
{
    global $lang, $form, $mybb;

    if (!isset($lang->mystatus)) {
        $lang->load('mystatus');
    }

    echo '<div id="tab_mystatus">';
    $form_container = new FormContainer($lang->group_mystatus);
    $form_container->output_row($lang->mystatus_can_use, "", $form->generate_yes_no_radio('mystatus_can_use', $mybb->input['mystatus_can_use'], true), 'mystatus_can_use');
    $form_container->output_row($lang->mystatus_can_moderate, "", $form->generate_yes_no_radio('mystatus_can_moderate', $mybb->input['mystatus_can_moderate'], true), 'mystatus_can_moderate');
    $form_container->output_row($lang->mystatus_can_delete_own, "", $form->generate_yes_no_radio('mystatus_can_delete_own', $mybb->input['mystatus_can_delete_own'], true), 'mystatus_can_delete_own');
    $form_container->output_row($lang->mystatus_can_comment, "", $form->generate_yes_no_radio('mystatus_can_comment', $mybb->input['mystatus_can_comment'], true), 'mystatus_can_comment');
    $form_container->end();
    echo '</div>';
}

$plugins->add_hook('admin_user_groups_edit_commit', 'mystatus_usergroup_perms_save');
function mystatus_usergroup_perms_save()
{
    global $updated_group, $mybb;

    $updated_group['mystatus_can_use'] = (int) $mybb->input['mystatus_can_use'];
    $updated_group['mystatus_can_moderate'] = (int) $mybb->input['mystatus_can_moderate'];
    $updated_group['mystatus_can_delete_own'] = (int) $mybb->input['mystatus_can_delete_own'];
    $updated_group['mystatus_can_comment'] = (int) $mybb->input['mystatus_can_comment'];
}

$plugins->add_hook('usercp_menu', 'mystatus_usercp_nav_oauth', 10);
function mystatus_usercp_nav_oauth()
{
    global $mybb, $templates, $lang, $usercpmenu;

    if (!$lang->mystatus) {
        $lang->load('mystatus');
    }

    if ($mybb->settings['mystatus_post_to_twitter']) {
        eval("\$usercpmenu .= \"".$templates->get('mystatus_usercp_nav_oauth')."\";");
    }
}

$plugins->add_hook('usercp_start', 'mystatus_usercp_oauth');
function mystatus_usercp_oauth()
{
    global $mybb, $db, $lang, $templates, $theme, $headerinclude, $header, $footer, $usercpnav, $mystatus_twitter_link_button;

    if (!isset($lang->mystatus)) {
        $lang->load('mystatus');
    }

    if ($mybb->settings['mystatus_post_to_twitter'] AND $mybb->input['action'] == 'mystatus-oauth') {
        if (!$mybb->user['mystatus_twitter_posting_enabled']) {
            eval("\$mystatus_twitter_link_button = \"".$templates->get('mystatus_usercp_oauth_button')."\";");
        } else {
            eval("\$mystatus_twitter_link_button = \"".$templates->get('mystatus_usercp_oauth_button_deactivate')."\";");
        }

        eval("\$page = \"".$templates->get('mystatus_usercp_oauth')."\";");
        output_page($page);
    } elseif ($mybb->settings['mystatus_post_to_twitter'] AND $mybb->input['action'] == 'mystatus-oauth-redirect') {
        session_start();
        require_once MYSTATUS_PLUGIN_PATH.'twitteroauth.php';
        $connection = new TwitterOAuth($mybb->settings['mystatus_twitter_consumer'], $mybb->settings['mystatus_twitter_consumer_secret']);
        $request_token = $connection->getRequestToken($mybb->settings['bburl'].'/usercp.php?action=mystatus-oauth-callback');
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
        $url = $connection->getAuthorizeURL($_SESSION['oauth_token']);
        header('Location: '.$url);
    } elseif ($mybb->settings['mystatus_post_to_twitter'] AND $mybb->input['action'] == 'mystatus-oauth-callback') {
        session_start();
        require_once MYSTATUS_PLUGIN_PATH.'twitteroauth.php';
        $connection = new TwitterOAuth($mybb->settings['mystatus_twitter_consumer'], $mybb->settings['mystatus_twitter_consumer_secret'], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
        $access_token = $connection->getAccessToken($mybb->input['oauth_verifier']);
        $updateArray = array(
            'mystatus_twitter_posting_enabled'      => 1,
            'mystatus_twitter_oauth_token'          => $access_token['oauth_token'],
            'mystatus_twitter_oauth_token_secret'   => $access_token['oauth_token_secret']
        );
        if ($db->update_query('users', $updateArray, 'uid = '.(int) $mybb->user['uid'])) {
            redirect('usercp.php?action=mystatus-oauth', $lang->mystatus_twitter_link_success);
        } else {
            error($lang->mystatus_twitter_link_error);
        }
    } elseif ($mybb->settings['mystatus_post_to_twitter'] AND $mybb->input['action'] == 'mystatus-oauth-disable') {
        $updateArray = array(
            'mystatus_twitter_posting_enabled'      =>  0
        );

        if ($db->update_query('users', $updateArray, 'uid = '.(int) $mybb->user['uid'])) {
            redirect('usercp.php?action=mystatus-oauth', $lang->mystatus_twitter_unlink_success);
        } else {
            error($lang->mystatus_twitter_unlink_error);
        }
    }
}

$plugins->add_hook('index_start', 'mystatus_index');
function mystatus_index()
{
    global $mybb, $lang, $db, $templates, $theme, $mystatus_latest_statuses, $mystatus_latest_statuses_row, $altbg, $mystatus_button;

    if ((int) $mybb->settings['mystatus_index_num_recent'] != 0) {
        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        $query = $db->write_query('SELECT s.*, u.username, u.avatar, u.usergroup, u.displaygroup FROM '.$db->table_prefix.'statuses s INNER JOIN '.$db->table_prefix.'users u ON u.uid = s.uid ORDER BY sid DESC LIMIT 0, '.(int) $mybb->settings['mystatus_index_num_recent']);
        while ($status = $db->fetch_array($query)) {
            $altbg = alt_trow();
            $status['dateline'] = my_date($mybb->settings['dateformat'], $status['dateline']).', '.my_date($mybb->settings['timeformat'], $status['dateline']);
            $status['formattedusername'] = '<a href="'.get_profile_link($status['uid']).'">'.format_name($status['username'], $status['usergroup'], $status['displaygroup']).'</a>';

            if (($status['uid'] == $mybb->user['uid'] AND $mybb->usergroup['mystatus_can_delete_own']) OR $mybb->usergroup['mystatus_can_moderate']) {
                eval("\$mystatus_button['delete'] = \"".$templates->get('mystatus_button_delete')."\";");
            } else {
                $mystatus_button['delete'] = '';
            }
            require_once MYBB_ROOT.'inc/class_parser.php';
            $parser = new postParser;

            $parserOptions  =   array(
                'allow_html'        => 0,
                'allow_smilies'     => 1,
                'allow_mycode'      => 1,
                'nl2br'             => 0,
                'filter_badwords'   => 1,
                'me_username'       => $status['username'],
                'shorten_urls'      => 1
                );

            $status['status'] = $parser->parse_message($status['status'], $parserOptions);
            eval("\$mystatus_latest_statuses_row .= \"".$templates->get('mystatus_latest_statuses_row')."\";");
        }

        if ($db->num_rows($query) == 0) {
            eval("\$mystatus_latest_statuses_row = \"".$templates->get('mystatus_latest_statuses_row_no_statuses')."\";");
        }

        if ($mybb->usergroup['mystatus_can_use']) {
            eval("\$mystatus_update_form = \"".$templates->get('mystatus_update_form')."\";");
        }

        eval("\$mystatus_latest_statuses = \"".$templates->get('mystatus_latest_statuses')."\";");
    }
};

$plugins->add_hook('member_profile_end', 'mystatus_profile');
function mystatus_profile()
{
    global $mybb, $db, $lang, $theme, $templates, $cache, $memprofile, $mystatus_button, $mystatus_latest_statuses_row, $mystatus_update_form, $mystatus_latest_statuses;

    if (!isset($lang->mystatus)) {
        $lang->load('mystatus');
    }

    if ((int) $mybb->settings['mystatus_profile_num_recent'] != 0 AND $mybb->usergroup['mystatus_can_use']) {
        $query = $db->simple_select('statuses', '*', 'uid = '.(int) $memprofile['uid'], array('order_by' => 'sid', 'order_dir' => 'DESC', 'limit' => $mybb->settings['mystatus_profile_num_recent']));
        while ($status = $db->fetch_array($query)) {
            $altbg = alt_trow();
            $status['dateline'] = my_date($mybb->settings['dateformat'], $status['dateline']).', '.my_date($mybb->settings['timeformat'], $status['dateline']);
            $status['formattedusername'] = '<a href="'.get_profile_link($status['uid']).'">'.format_name($memprofile['username'], $memprofile['usergroup'], $memprofile['displaygroup']).'</a>';
            $status['avatar'] = $memprofile['avatar'];
            $status['username'] = $memprofile['username'];

            if (($mybb->usergroup['mystatus_can_delete_own'] AND $mybb->user['uid'] == $memprofile['uid']) OR $mybb->usergroup['mystatus_can_moderate']) {
                eval("\$mystatus_button['delete'] = \"".$templates->get('mystatus_button_delete')."\";");
            } else {
                $mystatus_button['delete'] = '';
            }
            require_once MYBB_ROOT.'inc/class_parser.php';
            $parser = new postParser;

            $parserOptions  =   array(
                'allow_html'        => 0,
                'allow_smilies'     => 1,
                'allow_mycode'      => 1,
                'nl2br'             => 0,
                'filter_badwords'   => 1,
                'me_username'       => $status['username'],
                'shorten_urls'      => 1
                );

            $status['status'] = $parser->parse_message($status['status'], $parserOptions);
            eval("\$mystatus_latest_statuses_row .= \"".$templates->get('mystatus_latest_statuses_row')."\";");
        }

        if ($db->num_rows($query) == 0) {
            eval("\$mystatus_latest_statuses_row = \"".$templates->get('mystatus_latest_statuses_row_no_statuses')."\";");
        }

        if ($mybb->user['uid'] == $memprofile['uid']) {
            eval("\$mystatus_update_form = \"".$templates->get('mystatus_update_form')."\";");
        }

        eval("\$mystatus_latest_statuses = \"".$templates->get('mystatus_latest_statuses')."\";");
    }
}

$plugins->add_hook('postbit', 'mystatus_postbit');
function mystatus_postbit(&$post)
{
    global $mybb, $db, $lang, $pids;

    if ($mybb->settings['mystatus_postbit']) {
        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        static $myStatus;

        if (!is_array($myStatus) AND $mybb->input['method'] != "quickreply") {
            $myStatus = array();

            $statusQuery = $db->write_query('SELECT p.uid, s.status FROM '.TABLE_PREFIX.'posts p INNER JOIN '.TABLE_PREFIX.'statuses s ON (p.uid = s.uid) WHERE '.$pids.' ORDER BY ABS(s.dateline) DESC;');
            while ($row = $db->fetch_array($statusQuery)) {
                $myStatus[$row['uid']] = $row['status'];
            }
        } elseif ($mybb->input['method'] == 'quickreply') {
            $statusQuery = $db->simple_select('statuses', '*', 'uid = \''.$post['uid'].'\'');
            $myStatus[$post['uid']] = $db->fetch_array($statusQuery);
        }

        if (isset($myStatus[$post['uid']])) {
            if (is_array($myStatus[$post['uid']])) {
                $myStatus[$post['uid']] = $myStatus[$post['uid']][count($myStatus[$post['uid']]) - 1];
            }

            $post['userStatus'] = $lang->sprintf($lang->mystatus_postbit, $myStatus[$post['uid']]);
        }
    }
}

$plugins->add_hook('misc_start', 'mystatus_statuses_page');
function mystatus_statuses_page()
{
    global $lang, $mybb, $db, $templates, $theme, $cache, $headerinclude, $header, $footer, $mystatus_js;

    if ($mybb->input['action'] == 'mystatus') {
        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        add_breadcrumb($lang->mystatus_latest_statuses_page, 'misc.php?action=mystatus');

        $numStatuses = $db->fetch_field($db->simple_select('statuses', 'COUNT(sid) as count'), 'count');
        $curPage = (int) $mybb->input['page'];

        if ($curPage < 1) {
            $curPage = 1;
        }

        $multipage = multipage($numStatuses, 10, $curPage, 'misc.php?action=mystatus');

        $query = $db->write_query('SELECT s.*, u.username, u.avatar, u.usergroup, u.displaygroup FROM '.$db->table_prefix.'statuses s INNER JOIN '.$db->table_prefix.'users u ON u.uid = s.uid ORDER BY ABS(sid) DESC LIMIT '.(($curPage - 1) * 10).', 10;');
        while ($status = $db->fetch_array($query)) {
            $altbg = alt_trow();
            $status['dateline'] = my_date($mybb->settings['dateformat'], $status['dateline']).', '.my_date($mybb->settings['timeformat'], $status['dateline']);
            $status['formattedusername'] = '<a href="'.get_profile_link($status['uid']).'">'.format_name($status['username'], $status['usergroup'], $status['displaygroup']).'</a>';

            if (($mybb->usergroup['mystatus_can_delete_own'] AND $mybb->user['uid'] == $status['uid']) OR $mybb->usergroup['mystatus_can_moderate']) {
                eval("\$mystatus_button['delete'] = \"".$templates->get('mystatus_button_delete')."\";");
            } else {
                $mystatus_button['delete'] = '';
            }
            require_once MYBB_ROOT."inc/class_parser.php";
            $parser = new postParser;

            $parserOptions  =   array(
                'allow_html'        => 0,
                'allow_smilies'     => 1,
                'allow_mycode'      => 1,
                'nl2br'             => 0,
                'filter_badwords'   => 1,
                'me_username'       => $status['username'],
                'shorten_urls'      => 1,
                );

            $status['status'] = $parser->parse_message($status['status'], $parserOptions);
            eval("\$mystatus_latest_statuses_row .= \"".$templates->get('mystatus_latest_statuses_row')."\";");
        }

        if ($db->num_rows($query) == 0) {
            eval("\$mystatus_latest_statuses_row = \"".$templates->get('mystatus_latest_statuses_row_no_statuses')."\";");
        }

        if ($mybb->usergroup['mystatus_can_use']) {
            eval("\$mystatus_update_form = \"".$templates->get('mystatus_update_form')."\";");
        }

        eval("\$mystatus_statuses = \"".$templates->get('mystatus_latest_statuses')."\";");
        eval("\$page = \"".$templates->get('mystatus_statuses_page')."\";");
        output_page($page);
    }
}

$plugins->add_hook('misc_start', 'mystatus_process');
function mystatus_process()
{
    global $lang, $mybb, $db, $templates, $theme, $cache;
    if (!isset($lang->mystatus)) {
        $lang->load('mystatus');
    }

    if ($mybb->input['action'] == 'mystatus_update' AND strtolower($mybb->request_method) == 'post') {
        if (!verify_post_check($mybb->input['my_post_key'], true)) {
            if ($mybb->input['accessMethod'] == 'js') {
                header('HTTP/1.0 600');
                echo $lang->mystatus_error_updating;
            } else {
                error($lang->mystatus_error_updating);
            }
            die();
        }

        if ((int) $mybb->settings['mystatus_min_length'] != 0) {
            if (strlen(trim($mybb->input['statusText'])) < $mybb->settings['mystatus_min_length']) {
                if ($mybb->input['accessMethod'] == 'js') {
                    header('HTTP/1.0 600');
                    echo $lang->mystatus_error_status_too_short;
                } else {
                    error($lang->mystatus_error_status_too_short);
                }
                die();
            }
        }

        if ((int) $mybb->settings['mystatus_max_length'] != 0) {
            if (strlen(trim($mybb->input['statusText'])) > $mybb->settings['mystatus_max_length']) {
                if ($mybb->input['accessMethod'] == 'js') {
                    header('HTTP/1.0 600');
                    echo $lang->mystatus_error_status_too_long;
                } else {
                    error($lang->mystatus_error_status_too_long);
                }
                die();
            }
        }

        $insertArray    =   array(
            'uid'       =>  (int) $mybb->user['uid'],
            'status'    =>  $db->escape_string($mybb->input['statusText']),
            'dateline'  =>  time(),
            );

        if ($mybb->user['mystatus_twitter_posting_enabled'] AND $mybb->settings['mystatus_post_to_twitter']) {
            require_once MYSTATUS_PLUGIN_PATH.'twitteroauth.php';
            $connection = new TwitterOAuth($mybb->settings['mystatus_twitter_consumer'], $mybb->settings['mystatus_twitter_consumer_secret'], $mybb->user['mystatus_twitter_oauth_token'], $mybb->user['mystatus_twitter_oauth_token_secret']);
            $response = $connection->post('statuses/update', array('status' => substr(htmlspecialchars_uni(stripslashes($insertArray['status'])), 0, 140)));
            $insertArray['tweetid'] = $db->escape_string($response->id_str);
        }

        if ($db->insert_query('statuses', $insertArray)) {
            if ($mybb->input['accessMethod'] == 'js') {
                require_once MYBB_ROOT.'inc/class_parser.php';
                $parser = new postParser;

                $parserOptions  =   array(
                    'allow_html'        => 0,
                    'allow_smilies'     => 1,
                    'allow_mycode'      => 1,
                    'nl2br'             => 0,
                    'filter_badwords'   => 1,
                    'me_username'       => $mybb->user['username'],
                    'shorten_urls'      => 1,
                    );

                $status =   array(
                    'sid'               =>  (int) $db->insert_id(),
                    'username'          =>  $mybb->user['username'],
                    'formattedusername' =>  '<a href="'.get_profile_link($mybb->user['uid']).'">'.format_name($mybb->user['username'], $mybb->user['usergroup'], $mybb->user['displaygroup']).'</a>',
                    'dateline'          =>  my_date($mybb->settings['dateformat'], time()).', '.my_date($mybb->settings['timeformat'], time()),
                    'avatar'            =>  $mybb->user['avatar'],
                    'status'            =>  $parser->parse_message($mybb->input['statusText'], $parserOptions)
                    );

                $altbg = 'trow1';

                if ($mybb->usergroup['mystatus_can_delete_own'] OR $mybb->usergroup['mystatus_can_moderate']) {
                    eval("\$mystatus_button['delete'] = \"".$templates->get('mystatus_button_delete')."\";");
                }
                eval("\$mystatus_latest_statuses_row .= \"".$templates->get('mystatus_latest_statuses_row')."\";");
                echo $mystatus_latest_statuses_row;
            } else {
                redirect($_SERVER['HTTP_REFERER'], $lang->mystatus_status_updated_message, $lang->mystatus_status_updated);
            }
        } else {
            if ($mybb->input['accessMethod'] == 'js') {
                echo $lang->mystatus_error_updating;
            } else {
                error($lang->mystatus_error_updating);
            }
        }
    } elseif ($mybb->input['action'] == 'mystatus_delete') { // Deleting a status
        if (!verify_post_check($mybb->input['my_post_key'], true)) {
            if ($mybb->input['accessMethod'] == 'js') {
                header('HTTP/1.0 600');
                echo $lang->mystatus_error_deleting;
            } else {
                error($lang->mystatus_error_deleting);
            }
            die();
        }

        if ((int) $mybb->input['sid'] < 1) {
            if ($mybb->input['accessMethod'] == 'js') {
                header('HTTP/1.0 600');
                echo $lang->mystatus_error_deleting_invalid_status;
            } else {
                error($lang->mystatus_error_deleting_invalid_status);
            }
            die();
        }

        $status = $db->fetch_array($db->simple_select('statuses', 'tweetid, uid', 'sid = '.(int) $mybb->input['sid']));
        if (($mybb->usergroup['mystatus_can_delete_own'] AND $mybb->user['uid'] == $status['uid']) OR $mybb->usergroup['mystatus_can_moderate']) {
            if ($mybb->user['mystatus_twitter_posting_enabled'] AND $mybb->settings['mystatus_post_to_twitter']) {
                require_once MYSTATUS_PLUGIN_PATH.'twitteroauth.php';
                $connection = new TwitterOAuth($mybb->settings['mystatus_twitter_consumer'], $mybb->settings['mystatus_twitter_consumer_secret'], $mybb->user['mystatus_twitter_oauth_token'], $mybb->user['mystatus_twitter_oauth_token_secret']);
                $connection->delete('statuses/destroy/'.(int) $status['tweetid']);
            }

            if ($db->delete_query('statuses', 'sid = '.(int) $mybb->input['sid'], '1')) {
                if ($mybb->input['accessMethod'] == 'js') {
                    echo $lang->mystatus_status_deleted_message;
                } else {
                    redirect($_SERVER['HTTP_REFERER'], $lang->mystatus_status_deleted_message, $lang->mystatus_deleted_updated);
                }
            } else {
                if ($mybb->input['accessMethod'] == 'js') {
                    header('HTTP/1.0 600');
                    echo $lang->mystatus_error_deleting;
                } else {
                    error($lang->mystatus_error_deleting);
                }
            }
        } else {
            if ($mybb->input['accessMethod'] == 'js') {
                echo $lang->mystatus_status_delete_nopermissions;
            } else {
                redirect($_SERVER['HTTP_REFERER'], $lang->mystatus_status_delete_nopermissions, $lang->mystatus_status_delete_nopermissions);
            }
        }
    }
}

$plugins->add_hook('build_friendly_wol_location_end', 'mystatus_online_location');
function mystatus_online_location(&$plugin_array)
{
    if ($plugin_array['user_activity']['activity'] == 'misc' AND my_strpos($plugin_array['user_activity']['location'], 'mystatus')) {
        global $lang;

        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        $plugin_array['location_name'] = $lang->mystatus_online_location_statuses_page;
    }

    if ($plugin_array['user_activity']['activity'] == 'misc' AND my_strpos($plugin_array['user_activity']['location'], 'mystatus_update')) {
        global $lang;

        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        $plugin_array['location_name'] = $lang->mystatus_online_location_updating_status;
    }

    if ($plugin_array['user_activity']['activity'] == 'misc' AND my_strpos($plugin_array['user_activity']['location'], 'mystatus_delete')) {
        global $lang;

        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        $plugin_array['location_name'] = $lang->mystatus_online_location_deleting_status;
    }

    if ($plugin_array['user_activity']['activity'] == 'usercp' AND my_strpos($plugin_array['user_activity']['location'], 'mystatus')) {
        global $lang;

        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        $plugin_array['location_name'] = $lang->mystatus_online_location_usercp;
    }

    if ($plugin_array['user_activity']['activity'] == 'usercp' AND my_strpos($plugin_array['user_activity']['location'], 'mystatus-oauth-disable')) {
        global $lang;

        if (!isset($lang->mystatus)) {
            $lang->load('mystatus');
        }

        $plugin_array['location_name'] = $lang->mystatus_online_location_usercp_unlink;
    }
}
