<?php

namespace Euantor\MyStatus;

/**
 * Status class.
 *
 * Handles the CRUD operations for statuses within MyStatus.
 *
 * @package MyStatus
 * @author Euan T. <euan@euantor.com>
 * @version 1.0.0
 * @license http://opensource.org/licenses/MIT MIT
 */
class Status
{
	/**
	 * Our Database connection object.
	 */
	private $db   = null;

	/**
	 * Our MyBB object.
	 */
	private $mybb = null;

	/**
	 * MyCode parser object.
	 */
	private $parser;

	/**
	 * Create a new Status object
	 *
	 * @param MyBB The MyBB object.
	 * @param DB_* A Database instance object of type DB_MySQL, DB_MySQLi, DB_PgSQL or DB_SQLite
	 * @return null
	 */
	public function __construct(MyBB $mybbIn, $dbIn, postParser $parserIn)
	{
		$this->mybb = $mybbIn;

		if ($dbIn instanceof DB_MySQL OR $dbIn instanceof DB_MySQLi OR $dbIN instanceof DB_PgSQL OR $dbIn instanceof DB_SQLite) {
			$this->db = $dbIn;
		}

		$this->parser = $parserIn;
	}

	/**
	 * Add a new status for the currently logged in user.
	 *
	 * @param string The new status to be added.
	 * @return string The MyCode formatted string.
	 */
	public function addStatus($message)
	{
		$message = (string) $message;

		if ($this->myb->user['uid'] > 0) {
			$this->db->insert_query('statuses', ['status' => $this->db->escape_string($message), 'user_id' => (int) $this->mybb->user['uid'], 'created_at' => new DateTime(), 'updated_at' => new DateTime()]);
		}

		return $this->parser->parse_message($message, ['allow_html' => false, 'filter_badwords' => true, 'allow_mycode' => true, 'allow_smilies' => true, 'nl2br' => true, 'me_username' => $this->mybb->user['username']]);
	}

	/**
	 * Get a single status or an array of statuses by ID.
	 *
	 * @param int/array The id of the status to fetch or an array of status IDs to fetch multiple statuses.
	 * @return array The status's row in the statuses table or an associative array in the format ID => status row.
	 */
	public function getStatus($id)
	{
		if (!is_array($id)) {
			$id = (int) $id;

			$status = $this->db->simple_select('statuses', '*', 'id = '.$id, ['limit' => 1]);
			return $this->db->fetch_array($status);
		} else {
			$id = array_filter($id);
			$id = array_map('intval', $id);
			$inClause = "'".implode("','", $id)."'";

			$statuses = $this->db->simple_select('statuses', '*', "id IN ({$inClause})");
			$toReturn = [];
			while ($row = $this->db->fetch_array($statuses)) {
				$toReturn[(int) $row['id']] = $row;
			}

			return $toReturn;
		}
	}

	/**
	 * Get the "likes" for a specific status or set of statuses.
	 *
	 * @param int/array The status(es) to get the likes for.
	 * @return array/boolean An array of all the "likes" or false if nil
	 */
	public function getStatusLikes($id)
	{
		$likes = [];
		if (!is_array($id)) {
			$id = (int) $id;

			$query = $this->db->simple_select('status_likes', '*', 'status_id = '.$id);

			if ($this->db->num_rows($query) == 0) {
				return false;
			}

			while ($like = $this->db->fetch_array($query)) {
				$likes[] = $like;
			}
		} else {
			$id = array_filter($id);
			$id = array_map('intval', $id);
			$inClause = "'".implode("','", $id)."'";

			$query = $this->db->simple_select('status_likes', '*', "status_id IN ({$inClause})");

			if ($this->db->num_rows($query) == 0) {
				return false;
			}

			while ($like = $this->db->fetch_array($query)) {
				$likes[(int) $like['status_id']][] = $like;
			}
		}

		return $likes;
	}

	/**
	 * Delete a single status by it's ID.
	 *
	 * @param int/array Either the ID of a single status to delete or an array of status IDs to delete.
	 * @return boolean Whether the status(es) were deleted.
	 */
	public function deleteStatus($id)
	{
		if (!is_array($id)) {
			$id = (int) $id;

			return $this->db->delete_query('statuses', 'id = '.$id, 1);
		} else {
			$id = array_filter($id);
			$id = array_map('intval', $id);
			$inClause = "'".implode("','", $id)."'";

			return $this->db->delete_query('statuses', "id IN ({$inClause})");
		}
	}
}
