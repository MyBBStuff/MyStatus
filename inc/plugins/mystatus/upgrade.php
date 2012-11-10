<?php
/**
 * MyStatus
 *
 * @author euantor <admin@xboxgeneration.com>
 * @version 1.04
 * @copyright euantor 2011
 * @package MyStatus
 * 
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Disallow Direct Access
if(!defined('IN_MYBB'))
{
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
