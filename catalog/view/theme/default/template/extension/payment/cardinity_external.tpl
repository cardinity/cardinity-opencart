       <div>
            <form name="checkout" id="payment" method="POST" action="https://checkout.cardinity.com">
                
                <input type="hidden" name="amount" value="<?php echo $amount;?>" />
                <input type="hidden" name="country" value="<?php echo $country; ?>" />
                <input type="hidden" name="currency" value="<?php echo $currency; ?>" />
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>" />
                <input type="hidden" name="description" value="<?php echo $description; ?>" />
                <input type="hidden" name="return_url" value="<?php echo $return_url; ?>" />
                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
                <input type="hidden" name="signature" value="<?php echo $signature; ?>" />
                <div class="buttons">
                    <div class="pull-right">
                        <input type="submit" value="<?php echo $button_confirm; ?>" id="button-submit" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary" />
                    </div>
                </div>
            </form>
       </div>
      