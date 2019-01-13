<?php
/**
 * @package     Fabrik\Plugin\FabrikForm\Comment\Table
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Fabrik\Plugin\FabrikForm\Comment\Table;


use Fabrik\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Database\DatabaseDriver;

class CommentTable extends FabTable
{
	/**
	 * Object constructor to set table and key fields.
	 *
	 * @param   DatabaseDriver $db JDatabase connector object.
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__{package}_comments', 'id', $db);
	}
}