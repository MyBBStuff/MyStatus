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
}
