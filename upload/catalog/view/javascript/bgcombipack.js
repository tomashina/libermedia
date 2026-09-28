var bgcombipack_url20X = 'index.php?route=total/bgcombipack/';
var bgcombipack_url230X = 'index.php?route=extension/total/bgcombipack/';
var bgcombipack_url401X = 'index.php?route=extension/bgcombipack/total/bgcombipack|';
var bgcombipack_url402X = 'index.php?route=extension/bgcombipack/total/bgcombipack.';

var ribarray = ["bgcombipack_rib1", "bgcombipack_rib2", "bgcombipack_rib3", "bgcombipack_rib4", "bgcombipack_rib5", "bgcombipack_rib6", "bgcombipack_rib7", "bgcombipack_rib8"];
var bgcombipack = {
	'setpophtml': function(bgid, title, htmldata) {		
		var pophtml = '<div id="pop-bgcombipack'+bgid+'" class="modal fade" role="dialog"> <div class="modal-dialog modal-lg"> <div class="modal-content">';
		pophtml += '<div class="modal-header"> <button type="button" class="close" data-dismiss="modal">&times;</button> <h4>'+title+'</h4> </div>';
		pophtml += '<div class="modal-body">'+htmldata+'</div>';
		pophtml += '</div></div></div>';
		$('body').append(pophtml);
	},
	'setpophtmloc4': function(bgid, title, htmldata) {		
		var pophtml = '<div id="pop-bgcombipack'+bgid+'" class="modal fade" role="dialog"> <div class="modal-dialog modal-lg"> <div class="modal-content">';
		pophtml += '<div class="modal-header"> <h4>'+title+'</h4> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div>';
		pophtml += '<div class="modal-body">'+htmldata+'</div>';
		pophtml += '</div></div></div>';
		$('body').append(pophtml);
	},
	'atgrid': function() {
  		var product_ids = [];
 		$("[onclick*='cart.add'], [onclick*='addToCart'], .product-thumb form input[name='product_id']").each(function() {
			if($(this).closest('form').find("[name*='product_id']").length) {
				var target = $(this).closest('.edge, .product-wrapper');
				var product_id = $(this).val(); 
			} else {
				var target = $(this).closest('.edge, .product-wrapper');
				var product_id = $(this).attr('onclick').match(/[0-9]+/).toString(); 
			}
			if(target.length && target.hasClass('bgcombipack_atgridcol') == false) {
				target.addClass('bgcombipack_atgridcol').addClass('bgcombipack_atgridcol'+product_id);
				product_ids.push(product_id);
 			}
		});
		if(product_ids.length) { 
			//console.log(product_ids.length);
 			$.ajax({
				url: bgcombipack_url230X + 'getcache',
				type: 'post',
				data: {product_ids:product_ids},
				cache: true,
				complete: function() { 
				},
				success: function(json) {
					if(json) {
						//console.log(json);
						$("[onclick*='cart.add'], [onclick*='addToCart'], .product-thumb form input[name='product_id']").each(function() {
 							if($(this).closest('form').find("[name*='product_id']").length) {
								var target = $(this).closest('.edge, .product-wrapper');
								var product_id = $(this).val(); 
							} else {
								var target = $(this).closest('.edge, .product-wrapper');
								var product_id = $(this).attr('onclick').match(/[0-9]+/).toString(); 
							}
							if(json[product_id]) {
								var cnt = -1;
								$.each(json[product_id], function(bgid,val) {
									if(target.length && target.find('.bgcombipack'+bgid).length == 0) {
										cnt = cnt+1; if(cnt==8) { cnt = 1;}
										if(val['rib'] !== undefined && val['rib'].length) { 
											target.prepend('<div class="'+ribarray[cnt]+' bgcombipack'+bgid+'">'+val['rib']+'</div>');
										}
									}
								});
							}
						});
					}
				}
			});
		}		
  	},	
	'prodpage': function() {
		var product_ids = [];
		var product_id = false;
		
		if($(".button-group-page input[name='product_id']").length) {
			product_id = $(".button-group-page input[name='product_id']").val();
			product_ids.push(product_id);
		} else if($('#input-product-id').length) {
			product_id = $('#input-product-id').val();
			product_ids.push(product_id);
		} else if($(".product-info input[name='product_id']").length) {
			product_id = $(".product-info input[name='product_id']").val();
			product_ids.push(product_id);
		} else if($("#product input[name='product_id']").length) {
			product_id = $("#product input[name='product_id']").val();
			product_ids.push(product_id);
		} 
			
		if (product_ids.length) {
			$.ajax({
				url: bgcombipack_url230X + 'getcache',
				type: 'post',
				data: {product_ids:product_ids},
				cache: true,
				complete: function() { 
				},
				success: function(json) {
					if(json) {
						//console.log(json);
						var target = $('.col-sm-8 ul.thumbnails');
						var target_J2 = $('.route-product-product .product-info .left .image');
						var target_J3 = $('.left .main-image');
						var target_OC4 = $('.magnific-popup');
						
						if(json[product_id]) {
							var cnt = -1;
							$.each(json[product_id], function(bgid,val) {
								if(target.length && target.find('.bgcombipack'+bgid).length == 0) {
									cnt = cnt+1; if(cnt==8) { cnt = 1;}
									if(val['rib'] !== undefined && val['rib'].length) { 
										target.prepend('<div class="'+ribarray[cnt]+' bgcombipack'+bgid+'">'+val['rib']+'</div>');
									}
									
									var offer_heading = val['offer_heading'];
									var offer_content = val['offer_content'];
									
									if(val['showofferat'] == 0) {
										$('#product').before(offer_content);
									}
									if(val['showofferat'] == 1) {
										$('#product').before('<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#pop-bgcombipack'+bgid+'">'+offer_heading+'</button>');
										bgcombipack.setpophtml(bgid, offer_heading, offer_content);
									}
									if(val['showofferat'] == 2) {
										$('ul.nav-tabs').before(offer_content);
									}
									if(val['showofferat'] == 3) {
										$('ul.nav-tabs').append('<li><a href="#tab-bgcombipack'+bgid+'" data-toggle="tab">'+offer_heading+'</a></li>');
										$('.tab-content').append('<div class="tab-pane" id="tab-bgcombipack'+bgid+'">'+offer_content+'</div>');
									}
								}
								
								// for OC4
								if(target_OC4.length && target_OC4.find('.bgcombipack'+bgid).length == 0) {
									cnt = cnt+1; if(cnt==8) { cnt = 1;}
									if(val['rib'] !== undefined && val['rib'].length) { 
										target_OC4.prepend('<div class="'+ribarray[cnt]+' bgcombipack'+bgid+'">'+val['rib']+'</div>');
									}
									
									var offer_heading = val['offer_heading'];
									var offer_content = val['offer_content'];
									
									if(val['showofferat'] == 0) {
										$('#product').before(offer_content);
									}
									if(val['showofferat'] == 1) {
										$('#product').before('<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pop-bgcombipack'+bgid+'">'+offer_heading+'</button>');
										bgcombipack.setpophtmloc4(bgid, offer_heading, offer_content);
									}
									if(val['showofferat'] == 2) {
										$('ul.nav-tabs').before(offer_content);
									}
									if(val['showofferat'] == 3) {
										$('ul.nav-tabs').append('<li class="nav-item"><a class="nav-link" href="#tab-bgcombipack'+bgid+'" data-bs-toggle="tab">'+offer_heading+'</a></li>');
										$('.tab-content').append('<div class="tab-pane" id="tab-bgcombipack'+bgid+'">'+offer_content+'</div>');
									}
								}
								
								// for j2
								if(target_J2.length && target_J2.find('.bgcombipack'+bgid).length == 0) {
									cnt = cnt+1; if(cnt==8) { cnt = 1;}
									if(val['rib'] !== undefined && val['rib'].length) { 
										target_J2.prepend('<div class="'+ribarray[cnt]+' bgcombipack'+bgid+'">'+val['rib']+'</div>');
									}
									
									var offer_heading = val['offer_heading'];
									var offer_content = val['offer_content'];
									
									if(val['showofferat'] == 0) {
										$('.route-product-product #product ul.price').after(offer_content);
									}
									if(val['showofferat'] == 1) {
										$('.route-product-product #product .cart').after('<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#pop-bgcombipack'+bgid+'">'+offer_heading+'</button>');
										bgcombipack.setpophtml(bgid, offer_heading, offer_content);
									}
									if(val['showofferat'] == 2) {
										$('.route-product-product .product-tabs').prepend(offer_content);
									}
									if(val['showofferat'] == 3) {
										$('.route-product-product .product-tabs ul.nav-tabs').append('<li><a href="#tab-bgcombipack'+bgid+'" data-toggle="tab">'+offer_heading+'</a></li>');
										$('.route-product-product .product-tabs .tabs-content').append('<div class="tab-pane tab-content" id="tab-bgcombipack'+bgid+'">'+offer_content+'</div>');
									}
								}
								
								// for j3
								if(target_J3.length && target_J3.find('.bgcombipack'+bgid).length == 0) {
									cnt = cnt+1; if(cnt==8) { cnt = 1;}
									if(val['rib'] !== undefined && val['rib'].length) { 
										target_J3.prepend('<div class="'+ribarray[cnt]+' bgcombipack'+bgid+'">'+val['rib']+'</div>');
									}
									
									var offer_heading = val['offer_heading'];
									var offer_content = val['offer_content'];
									
									if(val['showofferat'] == 0) {
										$('#product').before(offer_content);
									}
									if(val['showofferat'] == 1) {
										$('.button-group-page').append('<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#pop-bgcombipack'+bgid+'">'+offer_heading+'</button>');
										bgcombipack.setpophtml(bgid, offer_heading, offer_content);
									}
									if(val['showofferat'] == 2) {
										$('.product_tabs-top ul.nav-tabs').before(offer_content);
									}
									if(val['showofferat'] == 3) {
										$('.product_tabs-top ul.nav-tabs').append('<li><a href="#tab-bgcombipack'+bgid+'" data-toggle="tab">'+offer_heading+'</a></li>');
										$('.product_tabs-top .tab-content').append('<div class="tab-pane" id="tab-bgcombipack'+bgid+'">'+offer_content+'</div>');
									}
								}
							});
						}					
					}
				}
			});
		}
	},
	'initjson': function() {
		bgcombipack.atgrid(); 
		$(document).ajaxStop(function() { bgcombipack.atgrid(); });
		bgcombipack.prodpage();
	}	
}
$(document).ready(function() {
bgcombipack.initjson();
});