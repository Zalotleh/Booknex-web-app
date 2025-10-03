<?php

namespace BookneticApp\Providers\DB;

use BookneticApp\Providers\Helpers\StringUtil;

/**
 * Bele olmasinda sebeb mixed tipi PHP 8-de geldi. Ashagi versiyalarda yoxdu.
 * JsonSerializable ise sennen teleb edir ki, ora mixed atasan 8ci versiyada.
 * Biz ora mixed atanda ashag versiya error verir ki, tanimiram bu tipi;
 * Atamayanda 8 error verir ki, mixed atmalisan. Ona gore fix usulu yalniz budu.
 */
if ( version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
	trait CollectionMixedTypes {
		public function offsetGet( $offset ) {
			if ( isset( $this->container[ $offset ] ) ) {
				return $this->container[ $offset ];
			}

			$methodName = sprintf( 'get%sAttribute', StringUtil::snakeCaseToCamel( $offset ) );

			if ( isset( $this->model ) && method_exists( $this->model, $methodName ) ) {
				return call_user_func( [ new $this->model(), $methodName ], $this );
			}

			return null;
		}

		public function jsonSerialize() {
			return $this->toArray();
		}
	}
} else {
	trait CollectionMixedTypes {
		public function offsetGet( $offset ): mixed {
			if ( isset( $this->container[ $offset ] ) ) {
				return $this->container[ $offset ];
			}

			$methodName = sprintf( 'get%sAttribute', StringUtil::snakeCaseToCamel( $offset ) );

			if ( isset( $this->model ) && method_exists( $this->model, $methodName ) ) {
				return call_user_func( [ new $this->model(), $methodName ], $this );
			}

			return null;
		}

		public function jsonSerialize(): mixed {
			return $this->toArray();
		}
	}
}