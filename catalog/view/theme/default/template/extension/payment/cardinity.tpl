<div style="display: none;" class="payment-warning alert alert-danger">
  <i class="fa fa-exclamation-circle"></i>
  <span class="message"></span>
</div>


<div style="display: none;" class="payment-warning alert alert-danger">
  <i class="fa fa-exclamation-circle"></i>
  <span class="message"></span>
</div>

<form class="form-horizontal" id="payment" style="padding-top: 10px">
	
  <div class="form-group required">
    <label class="col-sm-2 control-label" for="input-holder"><?php echo $entry_holder; ?></label>
    <div class="col-sm-10">
	  <input type="text" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" maxlength="32" name="holder" placeholder="<?php echo $entry_holder; ?>" id="input-holder" class="form-control" />
    </div>
  </div>
  <div class="form-group required">
    <label class="col-sm-2 control-label" for="input-pan"><?php echo $entry_pan; ?></label>
    <div class="col-sm-10">
	  <input type="text" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" maxlength="19" name="pan" placeholder="<?php echo $entry_pan; ?>" id="input-pan" class="form-control" />
    </div>
  </div>
  <div id="expiry-date-group" class="form-group required">
    <label class="col-sm-2 control-label"><?php echo $entry_expires; ?></label>
    <div class='row'>
		<div class="col-sm-4">
			<select name="exp_month" class="form-control">
				<?php foreach ($months as $month) { ?>
				<option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="col-sm-4">
			<select name="exp_year" class="form-control">
				<?php foreach ($years as $year) { ?>
				<option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
  </div>
  <div class="form-group required">
    <label class="col-sm-2 control-label" for="input-cvc"><?php echo $entry_cvc; ?></label>
    <div class="col-sm-10">
	  <input type="text" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" maxlength="4" name="cvc" placeholder="<?php echo $entry_cvc; ?>" id="input-cvc" class="form-control" />
    </div>
  </div>
  
  <div class="form-group">
    <div class="col-sm-1 col-sm-offset-2">	
	 	<div class="buttons">
            <div class="pull-right">
                <input id="button-confirm" type="button" value="<?php echo $button_confirm; ?>" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary" />
            </div>
        </div>		
    </div>
  </div>


	<input type='hidden' id='screen_width' name='screen_width' value=''/>
	<input type='hidden' id='screen_height' name='screen_height' value=''/>
	<input type='hidden' id='browser_language' name='browser_language' value=''/>
	<input type='hidden' id='color_depth' name='color_depth' value=''/>
	<input type='hidden' id='time_zone' name='time_zone' value=''/>

</form>
<div id="cardinity-3ds"></div>
<script type="text/javascript"><!--
$('#button-confirm').on('click', function() {

	document.getElementById("screen_width").value = screen.availWidth;
    document.getElementById("screen_height").value = screen.availHeight;
    document.getElementById("browser_language").value = navigator.language;
    document.getElementById("color_depth").value = screen.colorDepth;
    document.getElementById("time_zone").value = new Date().getTimezoneOffset();


	$.ajax({
		url: 'index.php?route=extension/payment/cardinity/send',
		type: 'post',
		data: $('#payment :input'),
		dataType: 'json',
		beforeSend: function() {
			$('.payment-warning').hide();

			$('.payment-warning .message').text();

			$('#payment').find('*').removeClass('has-error');

			$('#button-confirm').button('loading').attr('disabled', true);
		},
		complete: function() {
			$('#button-confirm').button('reset');
			$(".confirm-button").button('reset');
		},
		success: function(json) {
			if (json['error']) {
				
				$('.payment-warning').show();
				$('.payment-warning .message').text(json['error']);

				if (json['error']['warning']) {
					$('.payment-warning').show();

					$('.payment-warning .message').text(json['error']['warning']);
				}

				if (json['error']['holder']) {
					$('#input-holder').closest('.form-group').addClass('has-error');
				}

				if (json['error']['pan']) {
					$('#input-pan').closest('.form-group').addClass('has-error');
				}

				if (json['error']['expiry_date']) {
					$('#expiry-date-group').addClass('has-error');
				}

				if (json['error']['cvc']) {
					$('#input-cvc').closest('.form-group').addClass('has-error');
				}
			}else{

				if (json['3ds']) {
					$.ajax({
						url: 'index.php?route=extension/payment/cardinity/threeDSecureForm',
						type: 'post',
						data: json['3ds'],
						dataType: 'html',
						success: function(html) {
							$('#cardinity-3ds').html(html);
						}
					});
				}
				

				if (json['redirect']) {
					location = json['redirect'];
				}
			}

		
		}
	});
});
//--></script>