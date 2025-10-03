<?php

namespace BookneticApp\Providers\Request;

interface Request {
	public static function string( string $key, string $default, array $whiteList ): string;

	public static function int( string $key, int $default, array $whiteList ): int;

	public static function array( string $key, array $default, array $whiteList ): array;
}