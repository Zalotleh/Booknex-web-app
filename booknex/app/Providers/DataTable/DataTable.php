<?php

namespace BookneticApp\Providers\DataTable;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\QueryBuilder;

class DataTable {
	private int $limit;
	private int $currentPage;

	private $query;
	private string $orderBy;

	/**
	 * @param Model|QueryBuilder $query
	 * */
	public function setQuery( $query ) {
		$this->query = $query;
	}

	public function getPage(): array {
		$totalItems = ( clone $this->query )->countGroupBy();

		$results = $this->query
			->offset( $this->currentPage * $this->limit )
			->limit( $this->limit )
			->orderBy( $this->orderBy )
			->fetchAll();

		return [
			'data' => $results,
			'meta' => [
				'totalItems'  => $totalItems,
				'perPage'     => $this->limit,
				'currentPage' => $this->currentPage + 1,
				'totalPages'  => ceil( $totalItems / $this->limit ),
			]
		];
	}

	public function getAllPages(): array {
		return $this->query->orderBy( $this->orderBy )->fetchAllAsArray();
	}

	public function setCurrentPage( int $currentPage ): void {
		$this->currentPage = max( $currentPage, 0 );
	}

	public function setOrderBy( string $orderBy, string $sort ) {
		$this->orderBy = sprintf( '%s %s', $orderBy, $sort );
	}

	public function search( string $searchQuery, array $fields ) {
		if ( empty( $searchQuery ) || empty( $fields ) ) {
			return;
		}

		$this->query->where( fn( $query ) => array_map( fn( $field ) => $query->orLike( $field, $searchQuery ), $fields ) );
	}

	public function setLimit( int $limit ) {
		$this->limit = $limit;
	}

	public function setDateFilter( DateFilter $dateFilter, string $dateField ): void {
		$this->query = $this->query->where( $dateField, '>', $dateFilter->getFrom() )
		                           ->where( $dateField, '<', $dateFilter->getTo() );
	}
}