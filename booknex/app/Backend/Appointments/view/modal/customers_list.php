<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var mixed $parameters
 */

?>

<div class="clist-area hidden">
	<?php
	foreach( $parameters['customers'] AS $customer )
	{
		echo '<div class="list_left_right_box">';
			echo '<div class="list_left_box">';
			echo Helper::profileCard( $customer['customer_name'], $customer['profile_image'], $customer['email'], 'Customers' );
			echo '</div>';
			echo '<div class="list_right_box">';
			echo '<span class="list_right_box_date">' . Date::datee($customer['created_at']) .  '</span>';
			echo '<div class="list_right_box_user"><i class="fa fa-user"></i><span>' . $customer['weight'] .  '</span></div>';
			echo '<div class="appointment-status-' . htmlspecialchars( $customer['status'] ) .'"></div>';
            echo '<span class="list_right_box_date" style="margin-left: 10px">#' . $customer['id'] .  '</span>';

        echo '</div>';
		echo '</div>';
	}
	?>
</div>

<script>
	$(".clist-area").fadeIn(400);
</script>
