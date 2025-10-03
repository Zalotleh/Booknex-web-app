<?php

namespace BookneticApp\Providers\DataTable;

use BookneticApp\Providers\Helpers\Date;
use Exception;

class DateFilter {
	private string $type;
	private string $from;
	private string $to;

	/**
	 * @throws Exception
	 */
	public function __construct( array $arr ) {
		if ( empty( $arr[ 'type' ] ) ) {
			throw new Exception( 'Invalid date filter data.' );
		}

		$this->type = $arr[ 'type' ];

		switch ( $this->type ) {
			case 'today':
				$this->setAsToday();
				break;
			case 'this_week':
				$this->setAsThisWeek();
				break;
			case 'last_week':
				$this->setAsLastWeek();
				break;
			case 'this_month':
				$this->setAsThisMonth();
				break;
			case 'last_month':
				$this->setAsLastMonth();
				break;
			case 'this_year':
				$this->setAsThisYear();
				break;
			case 'last_year':
				$this->setAsLastYear();
				break;
			case 'last_30_days':
				$this->setAsLast30Days();
				break;
			case 'all_time':
				$this->setAsAllTime();
				break;
			case 'custom':
			default:
				if ( empty( $arr[ 'from' ] ) || empty( $arr[ 'to' ] ) ) {
					throw new Exception( 'Invalid date filter data.' );
				}

				$this->setAsCustom( $arr[ 'from' ], $arr[ 'to' ] );
		}
	}

	private function setAsToday() {
		$this->from = Date::dateSQL( 'today' );
		$this->to   = Date::dateSQL( 'tomorrow' );
	}

	private function setAsThisWeek() {
		$this->from = Date::dateSQL( 'monday this week' );
		$this->to   = Date::dateSQL( 'monday next week' );
	}

	private function setAsLastWeek() {
		$this->from = Date::dateSQL( 'monday previous week' );
		$this->to   = Date::dateSQL( 'monday this week' );
	}

	private function setAsThisMonth() {
		$this->from = Date::dateSQL( 'first day of this month' );
		$this->to   = Date::dateSQL( 'first day of next month' );
	}

	private function setAsLastMonth() {
		$this->from = Date::dateSQL( 'first day of previous month' );
		$this->to   = Date::dateSQL( 'first day of this month' );
	}

	private function setAsThisYear() {
		$this->from = Date::dateSQL( 'first day of January this year' );
		$this->to   = Date::dateSQL( 'first day of January next year' );
	}

	private function setAsLastYear() {
		$this->from = Date::dateSQL( 'first day of January last year' );
		$this->to   = Date::dateSQL( 'first day of January this year' );
	}

	private function setAsLast30Days() {
		$this->from = Date::dateSQL( '30 days ago' );
		$this->to   = Date::dateSQL( 'tomorrow' );
	}

	private function setAsAllTime() {
		$this->from = 0;
		$this->to   = Date::dateSQL( 'tomorrow' );
	}

	private function setAsCustom( string $from, string $to ) {
		$this->from = Date::dateSQL( Date::reformatDateFromCustomFormat( $from ) );
		$this->to   = Date::dateSQL( Date::reformatDateFromCustomFormat( $to ), '+1 day' );
	}

	public function getType(): string {
		return $this->type;
	}

	public function getFrom(): string {
		return $this->from;
	}

	public function getTo(): string {
		return $this->to;
	}

	public function getFromAsEpoch(): string {
		return Date::epoch( $this->from );
	}

	public function getToAsEpoch(): string {
		return Date::epoch( $this->getTo() );
	}
}