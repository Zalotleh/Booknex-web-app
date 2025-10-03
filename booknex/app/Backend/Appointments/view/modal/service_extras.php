<?php

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */
?>
<?php if ( count( $parameters[ 'extras' ] ) === 0 ): ?>
    <div class="text-secondary font-size-14 text-center"><?php echo bkntc__( 'No extras found' ) ?></div>
<?php else: ?>
    <?php foreach ( $parameters[ 'extras' ] as $extra ): ?>
        <div class="customer-fields-area dashed-border pb-3" data-extra-id="<?php echo (int) $extra[ 'id' ] ?>">
            <div class="row mb-2">
                <div class="col-md-4">
                    <div class="form-control-plaintext">
                        <?php echo htmlspecialchars( $extra[ 'name' ] ) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="number"
                           min="<?php echo (int) $extra[ 'min_quantity' ] ?>"
                           max="<?php echo (int) $extra[ 'max_quantity' ] ?>"
                           class="form-control extra_quantity"
                           value="<?php echo $extra[ 'quantity' ] ?>"
                    />
                </div>
                <div class="col-md-5">
                    <div class="form-control-plaintext help-text text-secondary">
                        ( <?php echo bkntc__( 'min quantity' ) ?>: <?php echo (int) $extra[ 'min_quantity' ] ?> ,
                        <?php echo bkntc__( 'max quantity' ) ?>: <?php echo (int) $extra[ 'max_quantity' ] ?> )
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>