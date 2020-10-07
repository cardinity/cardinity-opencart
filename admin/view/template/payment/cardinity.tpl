<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-cardinity" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $heading_title; ?></h3>
      </div>
      <div class="panel-body">
       <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-cardinity" class="form-horizontal">  
          <div class="form-group">
            <label class="col-sm-2 control-label" for="cardinity_key"><?php echo $entry_key; ?></label>
            <div class="col-sm-10">
              <input type="text" name="cardinity_key" value="<?php echo $cardinity_key; ?>" placeholder="<?php echo $entry_key; ?>" id="cardinity_key" class="form-control" />
            </div>
          </div>
           <div class="form-group">
            <label class="col-sm-2 control-label" for="cardinity_secret"><?php echo $entry_secret; ?></label>
            <div class="col-sm-10">
              <input type="text" name="cardinity_secret" value="<?php echo $cardinity_secret; ?>" placeholder="<?php echo $entry_secret; ?>" id="cardinity_secret" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="cardinity_total"><span data-toggle="tooltip" title="<?php echo $help_total; ?>"><?php echo $entry_total; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="cardinity_total" value="<?php echo $cardinity_total; ?>" placeholder="<?php echo $entry_total; ?>" id="cardinity_total" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="cardinity_order_status_id"><?php echo $entry_order_status; ?></label>
            <div class="col-sm-10">
              <select name="cardinity_order_status_id" id="cardinity_order_status_id" class="form-control">
                <?php foreach ($order_statuses as $order_status) { ?>
                <?php if ($order_status['order_status_id'] == $cardinity_order_status_id) { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="cardinity_geo_zone_id"><?php echo $entry_geo_zone; ?></label>
            <div class="col-sm-10">
              <select name="cardinity_geo_zone_id" id="cardinity_geo_zone_id" class="form-control">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach ($geo_zones as $geo_zone) { ?>
                <?php if ($geo_zone['geo_zone_id'] == $cardinity_geo_zone_id) { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select>
            </div>
          </div>
           <div class="form-group">
            <label class="col-sm-2 control-label" for="cardinity_status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="cardinity_status" id="cardinity_status" class="form-control">
                <?php if ($cardinity_status) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="cardinity_sort_order"><?php echo $entry_sort_order; ?></label>
            <div class="col-sm-10">
              <input type="text" name="cardinity_sort_order" value="<?php echo $cardinity_sort_order; ?>" placeholder="<?php echo $entry_sort_order; ?>" id="cardinity_sort_order" class="form-control" />
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?> 
