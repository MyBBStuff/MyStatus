<?php
/**
 * MyStatus Upgrade File
 *
 * @package MyStatus
 * @author Euan T. <euan@euantor.com>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @version 1.1
 */

if(!defined('IN_MYBB')) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$cacheContents = $cache->read('euantor-plugins');
if (is_array($cacheContents))
{
		foreach ($cacheContents as $content)
		{
			if ($content['name'] == 'mystatus')
			{
				$previousVersion = $content['version'];
				break;
			}
		}
		
		if (empty($previousVersion))
		{
			$previousVersion = '1.00';
		}
}
else
{
	$previousVersion = '1.00';
}

mystatus_upgrade('1.04', $previousVersion);

function mystatus_upgrade($newVersion, $previous)
{
	global $db;
	
	if ($newVersion == '1.04' && $previous == '1.00')
	{
			if (!$db->table_exists('statuscomments'))
			{
				$db->write_query('CREATE TABLE `'.TABLE_PREFIX.'statuscomments` (
						`cid` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`uid` INT(10) NOT NULL,
						`sid` INT(10) NOT NULL,
						`dateline` BIGINT(30) NOT NULL,
						`comment` TEXT NOT NULL
					) ENGINE=MyISAM '.$db->build_create_table_collation().';');
			}
	}
}
?>
