<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <!-- breadcrumb -->

  <?php if (isset($error['error_warning'])): ?>
    <div class="warning"><?php echo $error['error_warning']; ?></div>
  <?php endif; ?>
  <!-- error -->

  <div class="box">
    <div class="heading">
      <h1><img src="view/image/payment.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>
    <!-- heading -->

	<div class="content">								
		<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">
 		<table class="form">
          <tr>
            <td><?php echo $entry_status; ?></td>
            <td>
              <select name="duitku_shopeepay_status">
                <?php $options = array('1' => $text_enabled, '0' => $text_disabled) ?>
                <?php foreach ($options as $key => $value): ?>
                  <option value="<?php echo $key ?>" <?php if ($key == $duitku_shopeepay_status) echo 'selected' ?> ><?php echo $value ?></option>
                <?php endforeach ?>
              </select>
            </td>
          </tr>
          <!-- Status -->

            <tr>
            <td><span class="required">*</span> <?php echo $entry_display_name; ?></td>
            <td><input type="text" name="duitku_shopeepay_display_name" value="<?php echo $duitku_shopeepay_display_name; ?>" />
              <?php if (isset($error['display_name'])): ?>
                <span class="error"><?php echo $error['display_name']; ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <!-- Display name -->

           <tr>
            <td><span class="required">*</span> <?php echo $entry_endpoint; ?></td>
            <td>
             <input type="text" name="duitku_shopeepay_endpoint" value="<?php echo $duitku_shopeepay_endpoint; ?>" id="input-merchant-id" />
              <?php if (isset($error['endpoint'])): ?>
                <span class="error"><?php echo $error['endpoint']; ?></span>
              <?php endif; ?>
            </td>
          </tr>
          	<!-- endpoint -->

          	 <tr>
            <td><span class="required">*</span> <?php echo $entry_merchant; ?></td>
            <td>
             <input type="text" name="duitku_shopeepay_merchant" value="<?php echo $duitku_shopeepay_merchant; ?>" id="input-merchant-id" />
              <?php if (isset($error['server_key_v2'])): ?>
                <span class="error"><?php echo $error['server_key_v2']; ?></span>
              <?php endif; ?>
            </td>
          </tr>
          	<!-- merchant code -->

          <tr>
            <td><span class="required">*</span> <?php echo $entry_api_key; ?></td>
            <td><input type="text" name="duitku_shopeepay_api_key" value="<?php echo $duitku_shopeepay_api_key; ?>" />
              <?php if (isset($error['client_key_v2'])): ?>
              <span class="error"><?php echo $error['client_key_v2']; ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <!-- API Key -->
		  
			  <tr>
				<td><span class="required">*</span> <?php echo $entry_expired_period; ?></td>
				<td><input type="number" name="duitku_shopeepay_expired" value="<?php echo $duitku_shopeepay_expired; ?>" />
				  <?php if (isset($error['expired_period'])): ?>
				  <span class="error"><?php echo $error['expired_period']; ?></span>
				  <?php endif; ?>
				</td>
			  </tr>
			<!-- expired_period Key -->
		

           <?php foreach (array('duitku_shopeepay_success_mapping', 'duitku_shopeepay_failure_mapping') as $status): ?>
            <tr class="">
              <td><span class="required">*</span> <?php echo ${'entry_' . $status} ?></td>
              <td>
                <select name="<?php echo $status ?>" id="duitkuPaymentType">
                  <?php foreach ($order_statuses as $option): ?>
                    <option value="<?php echo $option['order_status_id'] ?>" <?php if ($option['order_status_id'] == ${$status}) echo 'selected' ?> ><?php echo $option['name'] ?></option>
                  <?php endforeach ?>
                </select>
              </td>
            </tr>

          <?php endforeach ?>
          <!-- Duitku Mapping -->


          <tr>
            <td><?php echo $entry_geo_zone; ?></td>
            <td>
              <select name="duitku_shopeepay_geo_zone_id">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach ($geo_zones as $geo_zone) { ?>
                  <?php if ($geo_zone['geo_zone_id'] == $duitku_shopeepay_geo_zone_id) { ?>
                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                  <?php } else { ?>
                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                  <?php } ?>
                <?php } ?>
              </select>
            </td>
          </tr>
          <!-- Geo Zone -->

          <tr>
            <td><?php echo $entry_sort_order; ?></td>
            <td><input type="text" name="duitku_shopeepay_sort_order" value="<?php echo $duitku_shopeepay_sort_order; ?>" size="1" /></td>
          </tr>
        </table>
      </form>
      <div>
              <center><font size="1">version 2.1</font></center>
            </div>
    </div>
    <!-- content -->
																							
	</div>			
</div>

<?php echo $footer; ?>
