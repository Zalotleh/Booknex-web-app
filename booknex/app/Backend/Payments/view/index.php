<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */

echo $parameters['table'];
?>

<script type="application/javascript" src="<?php echo Helper::assets('js/payments.js', 'Payments')?>"></script>


