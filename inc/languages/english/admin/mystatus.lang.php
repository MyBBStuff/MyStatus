<?php
/**
 *  MyStatus Admin Language File
 *
 *  A simple user status system for MyBB based on the idea behind Twitter.
 *
 * @package MyAlerts
 * @author  Euan T. <euan@euantor.com>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @version 1.02
 */

$l['mystatus']                                      = 'MyStatus';

$l['mystatus_info_name']                            = 'MyStatus';
$l['mystatus_info_desc']                            = 'Custom user status plugin for MyBB';

$l['setting_group_mystatus']                        = 'MyStatus Settings';
$l['setting_group_mystatus_desc']                   = 'Settings for the MyStatus plugin';

$l['mystatus_setting_min_length']                   = 'Minimum status length';
$l['mystatus_setting_min_length_desc']              = 'Minimum length that a single status must be? To disable this feature, set this to 0.';
$l['mystatus_setting_max_length']                   = 'Maximum status length';
$l['mystatus_setting_max_length_desc']              = 'Maximum length that a single status may be? To disable this feature, set this to 0.';
$l['mystatus_setting_index_num_recent']             = 'Number of recent statuses on index?';
$l['mystatus_setting_index_num_recent_desc']        = 'How many recent statuses do you wish to show on the index page? To disable this feature, set this to 0.';
$l['mystatus_setting_profile_num_recent']           = 'Number of recent statuses in profile?';
$l['mystatus_setting_profile_num_recent_desc']      = 'How many recent statuses do you wish to show on a user\'s profile page? To disable this feature, set this to 0.';
$l['mystatus_setting_post_to_twitter']              = 'Allow status updates to be posted to twitter?';
$l['mystatus_setting_post_to_twitter_desc']         = 'Allow users to enable the MyStatus twitter posting feature? Doing so will add an area to the UCP allowing users to set up OAuth for MyStatus.';
$l['mystatus_setting_twitter_consumer']             = 'Twitter Consumer Key';
$l['mystatus_setting_twitter_consumer_desc']        = 'Twitter Consumer Key required to post status updates to twitter. To get your key, visit <a href="http://dev.twitter.com">here</a> and register your application.';
$l['mystatus_setting_twitter_consumer_secret']      = 'Twitter Consumer Secret';
$l['mystatus_setting_twitter_consumer_secret_desc'] = 'Twitter Consumer Secret required to post status updates to twitter. To get your key, visit <a href="http://dev.twitter.com">here</a> and register your application.';
$l['mystatus_setting_postbit']                      = 'Show MyStatus On Postbit';
$l['mystatus_setting_postbit_desc']                 = 'You can enable the functionality to show a user\'s most recent status update on their psotbit. Doing so does add 1 extra query to showthread.php though. Disabled by default.';
$l['mystatus_setting_flood_check']                      = 'Status Flood Time';
$l['mystatus_setting_flood_check_desc']                 = 'Set the time (in seconds) users have to wait between posting statuses, leave empty to disable.';

$l['mystatus_can_use']                              = 'Can Use MyStatus?';
$l['mystatus_can_moderate']                         = 'Can Moderate Statuses?';
$l['mystatus_can_delete_own']                       = 'Can Delete Own Statuses?';
