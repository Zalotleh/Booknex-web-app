<?php

namespace BookneticApp\Backend\Base\DTOs\Response;

use BookneticApp\Providers\UI\DataTableUI;

class DataTableResponse {
	private DataTableUI $table;

	public function setTable( DataTableUI $table ) {
		$this->table = $table;
	}

	public function getTable(): DataTableUI {
		return $this->table;
	}
}