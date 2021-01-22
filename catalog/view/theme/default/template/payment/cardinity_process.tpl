<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title><?php echo $heading_title; ?></title>
<base href="<?php echo $base; ?>" />
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/template/payment/cardinity/bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/template/payment/cardinity/cardinity.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="catalog/view/theme/default/template/payment/cardinity/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container main">
	<div class="row header">
  		<div class="col-md-12">
  			<img src="catalog/view/theme/default/template/payment/cardinity/logo.png" alt="" height="40">
			<img src="catalog/view/theme/default/template/payment/cardinity/mastercard.jpg" alt="" height="40">
			<img src="catalog/view/theme/default/template/payment/cardinity/maestro.jpg" alt="" height="40">
			<img src="catalog/view/theme/default/template/payment/cardinity/visa.jpg" alt="" height="40">
			<img src="catalog/view/theme/default/template/payment/cardinity/visa_secure.png" alt="" height="40">
			<img src="catalog/view/theme/default/template/payment/cardinity/mastercard_secure.png" alt="" height="40">
			<img src="catalog/view/theme/default/template/payment/cardinity/pci.jpg" alt="" height="40">
  		</div>
	</div>
	<div class="row">
	<h2><?php echo $heading_title; ?></h2>
	<div class="col-sm-12 col-md-12">
		<form action="<?php echo $action; ?>" method="post" id="form">
		<?php if ($error_warning) { ?>
	 	<div class="row">
	 		 <div class="alert alert-danger" role="alert"><?php echo $error_warning; ?></div>
	 	</div>
	 	<?php } ?>
		<div class="row">
			 <div class="form-group">
	            <label for="holder"><span class="required">*</span><?php echo $entry_holder; ?></label>
	           		<input type="text" name="holder" value="<?php echo $holder; ?>" id="holder" class="form-control" />
	           		<?php if(isset($error_holder)) { ?><span class="text-danger"><?php echo $error_holder; ?></span><?php } ?>
	          </div>
		</div>
		<div class="row">
			 <div class="form-group">
	            <label for="pan"><span class="required">*</span><?php echo $entry_pan; ?></label>
                	<input type="text" name="pan" value="<?php echo $pan; ?>" id="pan" class="form-control" />
                	<?php if(isset($error_pan)) { ?><span class="text-danger"><?php echo $error_pan; ?></span><?php } ?>
	          </div>
		</div>
		<div class="row">
			 <div class="col-sm-6 pl0">
			 	<label><span class="required">*</span><?php echo $entry_expiry_date; ?></label>
			 	<div  class="form-inline">
			 		<div class="form-group"  style="width: 49%;">
			 			<select id="year" name="year" class="form-control" style="width: 100%;">
						<?php
						 $year = date('Y');
						 $year_end = date('Y', strtotime('+ 19 years'));
						for ( $year; $year <=  $year_end; $year++) { ?>
							<option value="<?php echo $year; ?>"<?php if($year == $pyear){ echo ' selected="selected"'; } ?>><?php echo $year; ?></option>
						<?php } ?>
						</select>
			 		</div>
			 		<div class="form-group" style="width: 49%;">
			 		<select id="month" name="month" class="form-control" style="width: 100%;">
						<?php for ($month = 1; $month <= 12; $month++) { ?>
							<option value="<?php echo $month; ?>"<?php if($month == $pmonth){ echo ' selected="selected"'; } ?>><?php echo $month; ?></option>
						<?php } ?>
						</select>
			 		</div>
			 	</div>
			 </div>

			  <div class="col-sm-6 pr0">
			  		<label><span class="required">*</span><?php echo $entry_cvc; ?></label> <a class="cvc-link" data-toggle="modal" data-target=".cvc-modal"><?php echo $cvc_heading; ?></a>
			  		<div class="form-group">
		              <input type="text" name="cvc" value="<?php echo $cvc; ?>" id="cvc" class="form-control" />
		              <?php if(isset($error_cvc)) { ?><span class="text-danger"><?php echo $error_cvc; ?></span><?php } ?>
		          </div>
			  </div>
		</div>

		<div class="row text-right">

	    </div>
	    <div class="row text-right">
	    	 <span class="order-total"><?php echo $entry_order_total; ?> <?php echo $order_total; ?> </span> <a onclick="document.getElementById('form').submit();" id="pay" class="btn btn-primary"><?php echo $button_make_payment; ?></a>
	    </div>

			<input type='hidden' id='screen_width' name='screen_width' value='1920' />
                 <input type='hidden' id='screen_height' name='screen_height' value='1080' />
                 <input type='hidden' id='browser_language' name='browser_language' value='en-US' />
                 <input type='hidden' id='challenge_window_size' name='challenge_window_size' value='full-screen' />
                 <input type='hidden' id='color_depth' name='color_depth' value='24' />
                 <input type='hidden' id='time_zone' name='time_zone' value='-60' />


		</form>

		<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById("screen_width").value = window.innerWidth;
                document.getElementById("screen_height").value = window.innerHeight;
                document.getElementById("browser_language").value = navigator.language;
                document.getElementById("color_depth").value = screen.colorDepth;
                document.getElementById("time_zone").value = new Date().getTimezoneOffset();

                var availChallengeWindowSizes = [
                    [600, 400],
                    [500, 600],
                    [390, 400],
                    [250, 400]
                ];

                var cardinity_screen_width = window.innerWidth;
                var cardinity_screen_height = window.innerHeight;
                document.getElementById("challenge_window_size").value = 'full-screen';

                //display below 800x600
                if (!(cardinity_screen_width > 800 && cardinity_screen_height > 600)) {
                    //find largest acceptable size
                    availChallengeWindowSizes.every(function(element, index) {
                        console.log(element);
                        if (element[0] > cardinity_screen_width || element[1] > cardinity_screen_height) {
                            //this challenge window size is not acceptable
                            console.log('skip');
                            return true;
                        } else {
                            document.getElementById("challenge_window_size").value = element[0]+'x'+element[1];
                            console.log(element[0]+'x'+element[1]);
                            return false;
                        }
                    });
                }

            });
        </script>
	</div>
	</div>
</div>
<div class="modal cvc-modal" tabindex="-1" role="dialog" aria-labelledby="cvc-modal" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
     <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $cvc_heading; ?></h4>
      </div>
    	<div class="container-fluid">
			<p><?php echo $cvc_1; ?></p>
			<p><img src="catalog/view/theme/default/template/payment/cardinity/cv_card.gif" height="150" alt=""></p>
			<p><?php echo $cvc_2; ?></p>
			<p><img src="catalog/view/theme/default/template/payment/cardinity/cv_amex_card.gif" alt="" height="150"></p>
	   	</div>
    </div>
  </div>
</div>
</body>
</html>
