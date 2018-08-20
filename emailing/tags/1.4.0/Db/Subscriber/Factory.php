<?php
namespace tiFy\Plugins\Emailing\Db\Subscriber;

class Factory extends \tiFy\Core\Db\Factory
{	
	/* == == **/
	public function select( $query = null )
	{
		return new Select( $this );
	}
}