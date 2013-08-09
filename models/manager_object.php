<?php
/**
 * Represents a PHPDraft "manager" object.
 *
 * Managers have many players (picks), and belong to a single draft.
 */
class manager_object {
	/** @var int $manager_id The unique identifier for this manager */
	public $manager_id;
	/** @var int $draft_id Foreign key to the draft this manager belongs to */
	public $draft_id;
	/** @var string $manager_name Textual display name for each manager */
	public $manager_name;
	/** @var string $manager_email Email address of manager */
	public $manager_email;
	/** @var int $draft_order The order in which the manager makes a pick in the draft. */
	public $draft_order;

	public function __construct() {
		//Leaving this here in case other init needs to happen later
	}
}

?>
