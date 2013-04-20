/**
 * MyStatus
 *
 * @author euantor <admin@xboxgeneration.com>
 * @version 1.0
 * @copyright euantor 2011
 * @package MyStatus
 * 
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
jQuery.noConflict();
jQuery(document).ready(function($)
{
	$('#mystatusUpdater').on('submit', function(event)
	{
		event.preventDefault();
		
		var statusForm = $(this);
		var statusText = statusForm.find('input[name="statusText"]:first');
				
		$.post(
			'misc.php?action=mystatus_update',
			{
				statusText: statusText.val(),
				accessMethod: "js",
				my_post_key: my_post_key
			},
			function(data) {
				$(data).hide().prependTo('#latestStatusList').fadeIn("slow");
				statusText.val('');
			}
		);
		
		return false;
	});
	
	$('[id^="mystatus_delete_"]').on('click', function(event)
	{
		event.preventDefault();
		
		var deleteLink = $(this);
		
		$.post(
			deleteLink.attr('href'),
			{
				accessMethod: 'js'
			},
			function(data) {
				deleteLink.parents('tr').prev('tr').remove();
				deleteLink.parents('tr').remove();			
			}
		);
		
		return false;
	});
});