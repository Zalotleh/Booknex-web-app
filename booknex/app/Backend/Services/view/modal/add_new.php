<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;

/**
 * @var mixed $parameters
 * @var mixed $_mn
 */

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Services')?>">
<script>
    var serviceAssetUrl = "<?php echo Helper::assets( '/', 'Services' )?>";
</script>
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new.js', 'Services')?>" id="add_new_JS" data-mn="<?php echo $_mn?>" data-service-id="<?php echo (int)$parameters['service']['id']?>" data-staff-count="<?php echo count($parameters['staff'])?>"></script>

<div class="fs-modal-title">
	<div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
	<div class="title-text"><?php echo $parameters[ 'service' ][ 'id' ] > 0 ? bkntc__( 'Edit Service' ) :  bkntc__('Add Service')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">
		<form id="addServiceForm" class="validate-form">

			<ul class="nav nav-tabs nav-light" data-tab-group="services_add">
                <?php foreach ( TabUI::get( 'services_add' )->getSubItems() as $tab ): ?>
                    <li class="nav-item"><a class="nav-link" data-tab="<?php echo $tab->getSlug(); ?>" href="#"><?php echo $tab->getTitle(); ?></a></li>
                <?php endforeach; ?>
			</ul>

			<div class="tab-content mt-5">
                <?php foreach ( TabUI::get( 'services_add' )->getSubItems() as $tab ): ?>
                    <div class="tab-pane" data-tab-content="services_add_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>"><?php echo $tab->getContent( $parameters ); ?></div>
                <?php endforeach; ?>
			</div>

		</form>
	</div>
</div>

<div class="fs-modal-footer">
	<?php
	if( $parameters['service']['id'] > 0 )
	{
		?>
		<button type="button" class="btn btn-lg btn-default" id="hideServiceBtn"><?php echo $parameters['service']['is_active'] != 1 ? bkntc__('UNHIDE SERVICE') : bkntc__('HIDE SERVICE')?></button>
		<?php
	}
	?>
	<button type="button" class="btn btn-lg btn-default" data-dismiss="modal" id="addServiceClose" ><?php echo bkntc__('CLOSE')?></button>
	<button type="button" class="btn btn-lg btn-primary validate-button" id="addServiceSave"><?php echo $parameters['service'][ 'id' ] > 0 ?bkntc__( 'SAVE' ):bkntc__('ADD SERVICE')?></button>
</div>

<div class="fs-popover color-picker-popover" id="service_color_panel">
	<div class="fs-popover-title">
		<span><?php echo bkntc__('Hex')?></span>
		<img src="<?php echo Helper::icon('cross.svg')?>" class="close-popover-btn">
	</div>
	<div class="color-picker-content" style="height:auto;">
		<div class="form-row">
			<div class="col-md-12">
				<input type="text" class="form-control" id="input_color_hex" value="<?php echo !empty($parameters['service']['color'])?htmlspecialchars($parameters['service']['color']):'#53d56c'?>"/>
                <span class="selected-color" style="background-color:<?php echo $parameters['service']['color'] ?>"></span>
			</div>
		</div>
		<div class="fs-popover-footer">
			<button type="button" class="btn btn-primary btn-lg ml-2 save-btn1"><?php echo bkntc__('SAVE')?></button>
		</div>
	</div>
</div>
