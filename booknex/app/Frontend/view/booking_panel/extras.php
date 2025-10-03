<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;

$printedCategories = [];
$i                 = 0;

?>

<?php if ( empty( $parameters[ 'components' ] ) ): ?>
    <div class="booknetic_empty_box">
        <img src="<?php echo Helper::assets( 'images/empty-extras.svg', 'front-end' ) ?>">
        <span><?php echo bkntc__( 'Extras not found in this service. You can select other service or click the <span class="booknetic_text_primary">"Next step"</span> button.', [], false ) ?>
        </span>
    </div>
	<?php return; ?>
<?php else: ?>
	<?php foreach ( $parameters[ 'components' ] as $component ): ?>
		<?php echo htmlspecialchars_decode( $component ) ?>
	<?php endforeach; ?>
<?php endif; ?>

<?php $limitations = json_encode($parameters['extra_limitations']); ?>
<div class="limitations" data-extra-limitations="<?php echo htmlspecialchars($limitations) ?>"></div>