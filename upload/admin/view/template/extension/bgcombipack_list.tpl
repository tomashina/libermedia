<?php echo $header; ?><?php echo $column_left; ?>

<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right"><a href="<?php echo $add; ?>" data-toggle="tooltip" title="<?php echo $button_add; ?>" class="btn btn-primary"><i class="fa fa-plus"></i></a>
        <button type="button" data-toggle="tooltip" title="<?php echo $button_delete; ?>" class="btn btn-danger" onclick="confirm('<?php echo $text_confirm; ?>') ? $('#form-bgcombipack').submit() : false;"><i class="fa fa-trash-o"></i></button>
      </div>
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
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo $text_list; ?></h3>
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="filter_div">
            <style>
        .form-group { padding: 5px !important; } .col-sm-2 { width: 16%; font-size:11px; display: inline-block; margin: 2px !important;}
        @media only screen and (max-width: 600px) {
        .col-sm-2 { width: 30%; display:inline-block }
        }
        </style>
            <?php echo $filter_html;?>
            <div class="form-group col-sm-2">
              <label class="control-label col-sm-12" for="input-btnfilter">&nbsp;</label>
              <button type="button" id="button-filter" class="btn btn-primary"><i class="fa fa-filter"></i> <?php echo $button_filter; ?></button>
            </div>
          </div>
        </div>
        <form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form-bgcombipack">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <td style="width: 1px;" class="text-center"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', this.checked);" /></td>
                  <?php echo $coltd_html;?>
                  
                  <td class="text-right"><?php echo $column_action; ?></td>
                </tr>
              </thead>
              <tbody>
                <?php if ($bgcombipacks) { ?>
                <?php foreach ($bgcombipacks as $bgcombipack) { ?>
                <tr>
                  <td class="text-center"><?php if (in_array($bgcombipack['bgcombipack_id'], $selected)) { ?>
                    <input type="checkbox" name="selected[]" value="<?php echo $bgcombipack['bgcombipack_id']; ?>" checked="checked" />
                    <?php } else { ?>
                    <input type="checkbox" name="selected[]" value="<?php echo $bgcombipack['bgcombipack_id']; ?>" />
                    <?php } ?></td>
                 
                  <?php echo $bgcombipack['coltd_val_html']; ?>

                  <td class="text-right"><a href="<?php echo $bgcombipack['edit']; ?>" data-toggle="tooltip" title="<?php echo $button_edit; ?>" class="btn btn-primary"><i class="fa fa-pencil"></i></a></td>
                </tr>
                <?php } ?>
                <?php } else { ?>
                <tr>
                  <td class="text-center" colspan="30"><?php echo $text_no_results; ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </form>
        <div class="row">
          <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
          <div class="col-sm-6 text-right"><?php echo $results; ?></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script language="javascript" type="text/javascript">
function geturlparam(name) {
	var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
	return results[1] || 0;
}
function load_autocmp_pcm(target, route) {	
	token = geturlparam('token');
	
	$('input[name=\'filter_'+target+'_name\']').autocomplete({
		'source': function(request, response) {
			$.ajax({
				url: 'index.php?route=catalog/'+route+'/autocomplete&token='+token+'&filter_name=' +  encodeURIComponent(request),
				dataType: 'json',
				success: function(json) {
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item[''+route+'_id']
						}
					}));
				}
			});
		},
		'select': function(item) {
			$('input[name=\'filter_'+target+'_name\']').val(item['label']);
			$('input[name=\'filter_'+target+'_id\']').val(item['value']);
		}
	});
}
function dofilter() {
	var bgcombipack_url2X3X = 'index.php?route=extension/bgcombipack';
	var bgcombipack_url4X = 'index.php?route=extension/bgcombipack/extension/bgcombipack';

	token = geturlparam('token');

	$('#button-filter').on('click', function() {
		var url = bgcombipack_url2X3X + '&token='+token;
	
		var filter_title = $('input[name=\'filter_title\']').val();
		if (filter_title) {
			url += '&filter_title=' + encodeURIComponent(filter_title);
		}
		
		var filter_disctype = $('select[name=\'filter_disctype\']').val();
		if (filter_disctype != '') {
			url += '&filter_disctype=' + encodeURIComponent(filter_disctype);
		} 
		
		var filter_discount = $('input[name=\'filter_discount\']').val();
		if (filter_discount) {
			url += '&filter_discount=' + encodeURIComponent(filter_discount);
		}
		
		var filter_buyqty = $('input[name=\'filter_buyqty\']').val();
		if (filter_buyqty) {
			url += '&filter_buyqty=' + encodeURIComponent(filter_buyqty);
		}
		
		var filter_getqty = $('input[name=\'filter_getqty\']').val();
		if (filter_getqty) {
			url += '&filter_getqty=' + encodeURIComponent(filter_getqty);
		}
		
		var filter_startdate = $('input[name=\'filter_startdate\']').val();
		if (filter_startdate) {
			url += '&filter_startdate=' + encodeURIComponent(filter_startdate);
		}
		
		var filter_enddate = $('input[name=\'filter_enddate\']').val();
		if (filter_enddate) {
			url += '&filter_enddate=' + encodeURIComponent(filter_enddate);
		}
		
		var filter_status = $('select[name=\'filter_status\']').val();
		if (filter_status != '') {
			url += '&filter_status=' + encodeURIComponent(filter_status);
		}
	 
		var filter_customer_group_id = $('select[name=\'filter_customer_group_id\']').val();
		if (filter_customer_group_id != '') {
			url += '&filter_customer_group_id=' + encodeURIComponent(filter_customer_group_id);
		}
		
		var filter_store_id = $('select[name=\'filter_store_id\']').val();
		if (filter_store_id != '') {
			url += '&filter_store_id=' + encodeURIComponent(filter_store_id);
		} 
		 
		var filter_buyproduct_name = $('input[name=\'filter_buyproduct_name\']').val();
		var filter_buyproduct_id = $('input[name=\'filter_buyproduct_id\']').val();
		if (filter_buyproduct_name && filter_buyproduct_id) {
			url += '&filter_buyproduct_name=' + encodeURIComponent(filter_buyproduct_name);
			url += '&filter_buyproduct_id=' + encodeURIComponent(filter_buyproduct_id);
		} 
	
		var filter_buycategory_name = $('input[name=\'filter_buycategory_name\']').val();
		var filter_buycategory_id = $('input[name=\'filter_buycategory_id\']').val();
		if (filter_buycategory_name && filter_buycategory_id) {
			url += '&filter_buycategory_name=' + encodeURIComponent(filter_buycategory_name);
			url += '&filter_buycategory_id=' + encodeURIComponent(filter_buycategory_id);
		} 
		
		var filter_buymanufacturer_name = $('input[name=\'filter_buymanufacturer_name\']').val();
		var filter_buymanufacturer_id = $('input[name=\'filter_buymanufacturer_id\']').val();
		if (filter_buymanufacturer_name && filter_buymanufacturer_id) {
			url += '&filter_buymanufacturer_name=' + encodeURIComponent(filter_buymanufacturer_name);
			url += '&filter_buymanufacturer_id=' + encodeURIComponent(filter_buymanufacturer_id);
		}
		
		var filter_exbuyproduct_name = $('input[name=\'filter_exbuyproduct_name\']').val();
		var filter_exbuyproduct_id = $('input[name=\'filter_exbuyproduct_id\']').val();
		if (filter_exbuyproduct_name && filter_exbuyproduct_id) {
			url += '&filter_exbuyproduct_name=' + encodeURIComponent(filter_exbuyproduct_name);
			url += '&filter_exbuyproduct_id=' + encodeURIComponent(filter_exbuyproduct_id);
		} 
	
		var filter_exbuycategory_name = $('input[name=\'filter_exbuycategory_name\']').val();
		var filter_exbuycategory_id = $('input[name=\'filter_exbuycategory_id\']').val();
		if (filter_exbuycategory_name && filter_exbuycategory_id) {
			url += '&filter_exbuycategory_name=' + encodeURIComponent(filter_exbuycategory_name);
			url += '&filter_exbuycategory_id=' + encodeURIComponent(filter_exbuycategory_id);
		} 
		
		var filter_exbuymanufacturer_name = $('input[name=\'filter_exbuymanufacturer_name\']').val();
		var filter_exbuymanufacturer_id = $('input[name=\'filter_exbuymanufacturer_id\']').val();
		if (filter_exbuymanufacturer_name && filter_exbuymanufacturer_id) {
			url += '&filter_exbuymanufacturer_name=' + encodeURIComponent(filter_exbuymanufacturer_name);
			url += '&filter_exbuymanufacturer_id=' + encodeURIComponent(filter_exbuymanufacturer_id);
		}
		
		var filter_getproduct_name = $('input[name=\'filter_getproduct_name\']').val();
		var filter_getproduct_id = $('input[name=\'filter_getproduct_id\']').val();
		if (filter_getproduct_name && filter_getproduct_id) {
			url += '&filter_getproduct_name=' + encodeURIComponent(filter_getproduct_name);
			url += '&filter_getproduct_id=' + encodeURIComponent(filter_getproduct_id);
		} 
	
		var filter_getcategory_name = $('input[name=\'filter_getcategory_name\']').val();
		var filter_getcategory_id = $('input[name=\'filter_getcategory_id\']').val();
		if (filter_getcategory_name && filter_getcategory_id) {
			url += '&filter_getcategory_name=' + encodeURIComponent(filter_getcategory_name);
			url += '&filter_getcategory_id=' + encodeURIComponent(filter_getcategory_id);
		} 
		
		var filter_getmanufacturer_name = $('input[name=\'filter_getmanufacturer_name\']').val();
		var filter_getmanufacturer_id = $('input[name=\'filter_getmanufacturer_id\']').val();
		if (filter_getmanufacturer_name && filter_getmanufacturer_id) {
			url += '&filter_getmanufacturer_name=' + encodeURIComponent(filter_getmanufacturer_name);
			url += '&filter_getmanufacturer_id=' + encodeURIComponent(filter_getmanufacturer_id);
		}
		
		var filter_exgetproduct_name = $('input[name=\'filter_exgetproduct_name\']').val();
		var filter_exgetproduct_id = $('input[name=\'filter_exgetproduct_id\']').val();
		if (filter_exgetproduct_name && filter_exgetproduct_id) {
			url += '&filter_exgetproduct_name=' + encodeURIComponent(filter_exgetproduct_name);
			url += '&filter_exgetproduct_id=' + encodeURIComponent(filter_exgetproduct_id);
		} 
	
		var filter_exgetcategory_name = $('input[name=\'filter_exgetcategory_name\']').val();
		var filter_exgetcategory_id = $('input[name=\'filter_exgetcategory_id\']').val();
		if (filter_exgetcategory_name && filter_exgetcategory_id) {
			url += '&filter_exgetcategory_name=' + encodeURIComponent(filter_exgetcategory_name);
			url += '&filter_exgetcategory_id=' + encodeURIComponent(filter_exgetcategory_id);
		} 
		
		var filter_exgetmanufacturer_name = $('input[name=\'filter_exgetmanufacturer_name\']').val();
		var filter_exgetmanufacturer_id = $('input[name=\'filter_exgetmanufacturer_id\']').val();
		if (filter_exgetmanufacturer_name && filter_exgetmanufacturer_id) {
			url += '&filter_exgetmanufacturer_name=' + encodeURIComponent(filter_exgetmanufacturer_name);
			url += '&filter_exgetmanufacturer_id=' + encodeURIComponent(filter_exgetmanufacturer_id);
		}
		
		location = url; 
	}); 
}
$(document).ready(function() {
	$('.date').datetimepicker({pickTime: false});
	
	load_autocmp_pcm('buyproduct', 'product');
	load_autocmp_pcm('exbuyproduct', 'product');
	load_autocmp_pcm('buycategory', 'category');
	load_autocmp_pcm('exbuycategory', 'category');	
	load_autocmp_pcm('buymanufacturer', 'manufacturer');
	load_autocmp_pcm('exbuymanufacturer', 'manufacturer');
	
	load_autocmp_pcm('getproduct', 'product');
	load_autocmp_pcm('exgetproduct', 'product');
	load_autocmp_pcm('getcategory', 'category');
	load_autocmp_pcm('exgetcategory', 'category');	
	load_autocmp_pcm('getmanufacturer', 'manufacturer');
	load_autocmp_pcm('exgetmanufacturer', 'manufacturer');
	
	dofilter();
});
</script>
<?php echo $footer; ?>