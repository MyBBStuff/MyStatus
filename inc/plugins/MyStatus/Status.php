<?php
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
	 *
	 * @access private
	 * @var mixed
	 */
	private $db;

	/**
	 * Our MyBB object.
	 *
	 * @access private
	 * @var MyBB
	 */
	private $mybb;

	/**
	 * MyCode parser object.
	 *
	 * @access private
	 * @var postParser
	 */
	private $parser;

	/**
	 * Create a new Status object.
	 *
	 * @param MyBB $mybbIn The MyBB object.
	 * @param DB_* $dbIn A Database instance object of type DB_MySQL, DB_MySQLi, DB_PgSQL or DB_SQLite.
	 * @param postParser $parserIn An instance of the MyBB post parser to handle MyCode, Smilies etc.
	 * @return null
	 */
	public function __construct(MyBB $mybbIn, $dbIn, postParser $parserIn)
	{
		$this->mybb = $mybbIn;

		if ($dbIn instanceof DB_MySQL OR $dbIn instanceof DB_MySQLi OR $dbIn instanceof DB_PgSQL OR $dbIn instanceof DB_SQLite) {
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
	 * Add a "like" for a specific status.
	 *
	 * @param int The status id to "like".
	 * @return int The insert id if available.
	 */
	public function addStatusLike($id)
	{
		$insertArray = [
			'status_id'  => $id,
			'user_id'    => (int) $this->mybb->user['uid'],
			'created_at' => new DateTime,
		];

		return $this->db->insert_query('status_likes', $insertArray);
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

			$queryString = "SELECT s.*, u.avatar, u.username, u.usergroup, u.displaygroup FROM %sstatuses s LEFT JOIN %susers u ON (s.user_id = u.uid) WHERE s.id = {$id} LIMIT 1";
			$status = $this->db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX));
			return $this->db->fetch_array($status);
		} else {
			$id = array_map('intval', array_filter($id));
			$inClause = "'".implode("','", $id)."'";

			$queryString = "SELECT s.*, u.avatar, u.username, u.usergroup, u.displaygroup FROM %sstatuses s LEFT JOIN %susers u ON (s.user_id = u.uid) WHERE s.id IN ({$inClause})";

			$statuses = $this->db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX));
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
	 * @return array/boolean An array of all the "likes" or false if nil.
	 */
	public function getStatusLikes($id)
	{
		$likes = [];
		if (!is_array($id)) {
			$id = (int) $id;

			$queryString = "SELECT s.*, u.avatar, u.username, u.usergroup, u.displaygroup FROM %sstatus_likes s LEFT JOIN %susers u ON (s.user_id = u.uid) WHERE s.status_id = {$id}";
			$query = $this->db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX));

			if ($this->db->num_rows($query) == 0) {
				return false;
			}

			while ($like = $this->db->fetch_array($query)) {
				$likes[] = $like;
			}
		} else {
			$id = array_map('intval',  array_filter($id));
			$inClause = "'".implode("','", $id)."'";

			$queryString = "SELECT s.*, u.avatar, u.username, u.usergroup, u.displaygroup FROM %sstatus_likes s LEFT JOIN %susers u ON (s.user_id = u.uid) WHERE s.status_id IN ({$inClause})";
			$query = $this->db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX));

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
	 * @return resource The query data.
	 */
	public function deleteStatus($id)
	{
		if (!is_array($id)) {
			$id = (int) $id;

			return $this->db->delete_query('statuses', 'id = '.$id, 1);
		} else {
			$id = array_map('intval', array_filter($id));
			$inClause = "'".implode("','", $id)."'";

			return $this->db->delete_query('statuses', "id IN ({$inClause})");
		}
	}

	/**
	 * Remove a "like" from a status.
	 *
	 * @param int The ID of the status to "unlike".
	 * @return resource The query data.
	 */
	public function deleteStatusLike($id)
	{
		$id = (int) $id;
		$user_id = (int) $this->mybb->user['uid'];

		return $this->db->delete_query('status_likes', 'status_id = '.$id.' AND user_id = '.$user_id, 1);
	}
}
