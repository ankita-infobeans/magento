(function($){		
	$(function(){
		var ie7 = false;
		if ($.browser.msie  && parseInt($.browser.version, 10) === 7) {
			ie7 = true;
		}
		
		//pricing volume discount popup
		$('.tier-popup-wrapper').hover(
			function() {
				$(this).find('.tier-popup').fadeIn(50);
				if (ie7) $('a.volume-discount').css('visibility', 'hidden');
			},
			function() {
				$(this).find('.tier-popup').hide();
				if (ie7) $('a.volume-discount').css('visibility', 'visible');
			}
		);

		//sort product listing on table head click
		$('.th-sort').click(function() {
			var sortVal = $('.sort-by select:first option[value*="order=' + $(this).attr('rel') + '"]').attr('value');
			$('.sort-by select:first').val(sortVal).trigger('change');
		});

		//Event/Certification Registrant Fields
		$('#product-options-wrapper a.add-recipient').click(function() {
			var dtObj = $('#registrant-fields dt:first').clone(false).removeClass('first');
			var ddObj = $('#registrant-fields dd:first').clone(false).removeClass('first');
			dtObj.html('Full Name (' + ($('#registrant-fields dt').length + 1) + ')');
			ddObj.find('a.btn-remove').click(function() { removeRegistrant($(this)); });
			ddObj.find('input').val('').blur(function() { populateRegistrantNames(); });
			$('#registrant-fields').append(dtObj);
			$('#registrant-fields').append(ddObj);			
		});

		$('#product-options-wrapper a.btn-remove').click(function() {
			removeRegistrant($(this));
		});

		$('#registrant-fields input').blur(function() {
			populateRegistrantNames();
		});

		$('div.event-add-to button.btn-cart').click(function() {
			//if ($('#product-options-wrapper dd.first textarea').val().length == 0) {
			//	$('#advice-required-entry-registrant').show();
			//	return false;
			//}
			
			// Added checking input field. And check if field being checked for length is defined.
		
			var lenTextArea = $('#product-options-wrapper dd.first textarea').val();
			var len  = (typeof lenTextArea ==='undefined') ? 0 : lenTextArea.length ;
			var lenInputArea = $('#product-options-wrapper dd.first input').val();
			len += (typeof lenInputArea ==='undefined') ? 0 : lenInputArea.length ;
			
			if ( len == 0 ) {
				$('#advice-required-entry-registrant').show();
				return false;
			}
		});

		//Quick Order
		$('#quickorder_add').click(function() {
			var tr = $('#quickorder-add-table tr.item:first').clone();
			tr.find('input').val('');
			$('#quickorder-add-table').append(tr);
		});

		//Configurable Product Pricing
		var lastSelectId = $('.product-options select.super-attribute-select:last').attr('id');
		$('.product-options select.super-attribute-select').change(function() {	
                    var id = $(this).attr('id');
                    updateTierPrice(id, lastSelectId);
			

		});

		$('#grouped-items-table .options-list select.super-attribute-select').change(function() {
			var lastSelectId = $(this).parents('.options-list').find('select.super-attribute-select:last').attr('id');
			var id = $(this).attr('id');
			var ndx = $(this).parents('tr').attr('id').substring(8);

			if (id != lastSelectId || (id == lastSelectId && $(this).val() == '')) {
				$(this).parents('tr').find('.regular-price .price').html('$---.--');
				$(this).parents('tr').find('.member-price .price').html('$---.--');
				$(this).parents('tr').find('#gpchild-'+ndx+'-savings .base-price .price').html('$---.--');
			} else {
				var availableProducts = spConfig[ndx].getInScopeProductIds();
				if (availableProducts.length == 1) {
					var product = spConfig[ndx].config.childProducts[availableProducts[0]];
					var nonMemberPrice = '';
					var memberPrice = '';
					var cid = availableProducts[0].toString();
                                        var qty = parseInt($('#gpchild-'+ndx).find('td:first').children('.qty').val());
                                        qty = (qty!=0 && qty>=0 && (typeof (qty) === 'number') && (isFinite(qty))) ? qty : 1;
					if (product.tierPrices.length != 0) {
                                            nonMemberPrice = findPrice(product.tierPrices, nonMemberGroupId, qty);
                                            memberPrice = findPrice(product.tierPrices, memberGroupUpdatedId, qty);
					}

					if (nonMemberPrice == '' || isNaN(nonMemberPrice) || (typeof nonMemberPrice === 'undefined')) nonMemberPrice = product.finalPrice;
                                        if (memberPrice == '' || isNaN(memberPrice) || (typeof nonMemberPrice === 'undefined')) memberPrice =  (product.finalPrice > nonMemberPrice) ? nonMemberPrice : product.finalPrice;

					$(this).parents('tr').find('.regular-price .price').html(formatPrice(nonMemberPrice));
					$(this).parents('tr').find('.member-price .price').html(formatPrice(memberPrice));
					$(this).parents('tr').find('#gpchild-'+ndx+'-savings .base-price .price').html(formatPrice(nonMemberPrice - memberPrice));
					
					$(this).parents('tr').find('input.no-display').val(availableProducts[0]);
				}
			}
		});

        /*$('#carousel').carouFredSel({
            width: 915,
            height: 'auto',
            prev: '#prev',
            next: '#next',
            auto: false,
            scroll: 1
        });*/

    });
	
	function removeRegistrant(btn) {
		var dl = btn.parents('dl');
		btn.parent().prev().remove();
		btn.parent().remove();

		var i = 1;
		dl.find('dt').each(function() {
			$(this).html('Name (' + i + ')');
			i++;
		});

		populateRegistrantNames();
	}
	window.removeRegistrant = removeRegistrant;

	function populateRegistrantNames() {
		var str = '';
		var i = 0;
		$('#registrant-fields input').each(function() {
			var val = $(this).val();
			if (val.length > 0) {
				$('#advice-required-entry-registrant').hide();
				str += val + "\n";
				i++;
			}
		});
		$('#qty').val(i);
		$('#registrant_total').html(i);
		$('#registrant-hidden').find('textarea').val(str);
	}
	window.populateRegistrantNames = populateRegistrantNames;

    /* 
     * Add function to get tier price according to customer group and update
     */
    function updateTierPrice (id, lastSelectId) {
        if (id != lastSelectId || (id == lastSelectId && $('select.super-attribute-select').val() == '')) {
            $('#regular_price').html('$---.--');
            $('#member_price').html('$---.--');
            $('#savings_price').html('$---.--');
        } else {
            var availableProducts = spConfig.getInScopeProductIds();
            if (availableProducts.length == 1) {
                var product = spConfig.config.childProducts[availableProducts[0]];
                var nonMemberPrice = '';
                var memberPrice = '';
                var qty = parseInt($j("#qty").val());
                qty = (qty!=0 && qty>=0 && (typeof (qty) === 'number') && (isFinite(qty))) ? qty : 1;
                if (product.tierPrices.length != 0) {
                    nonMemberPrice = findPrice(product.tierPrices, nonMemberGroupId, qty);
                    memberPrice = findPrice(product.tierPrices, memberGroupUpdatedId, qty);
                }

                if (nonMemberPrice == '' || isNaN(nonMemberPrice) || (typeof nonMemberPrice === 'undefined')) nonMemberPrice = product.finalPrice;
                if (memberPrice == '' || isNaN(memberPrice) || (typeof nonMemberPrice === 'undefined')) memberPrice =  (product.finalPrice > nonMemberPrice) ? nonMemberPrice : product.finalPrice;

                $('#regular_price').html(formatPrice(nonMemberPrice));
                $('#member_price').html(formatPrice(memberPrice));
                $('#savings_price').html(formatPrice(nonMemberPrice - memberPrice));
            }
        }
    }
})(jQuery);



    function findPrice(tierPrices, group, qty) {
       temp = 0;
       if ((group != nonMemberGroupId) && (group != memberGroupId)) {
           var flag = false;
            for (var i = 0; i < tierPrices.length; i++) {
                if ( tierPrices[i].cust_group == group) {
                    flag = true;
                }
            }
            if (flag) {
                group = memberGroupUpdatedId;
            } else {
                group = memberGroupId;
            }
        }
        
        tierPrices.push({"price_id":"99999999999","website_id":"0","all_groups":"1","cust_group":group,"price":"62.0000","price_qty":"999999999990","website_price":"62.0000"});
         console.log(JSON.stringify(tierPrices));
	for (var i = 0; i < tierPrices.length; i++) {
            if (tierPrices[i].cust_group == group && parseInt(tierPrices[i].price_qty) == parseInt(qty)) {
                    return tierPrices[i].website_price;
            } else {
                if ((tierPrices[i].cust_group == group)) {
                    if( tierPrices[i].price_qty < parseInt(qty)) {
                        temp = tierPrices[i];
                    } else {
                        return temp.website_price;
                    }
                }
            }
	}
}

function formatPrice(price) {
	return '$' + parseFloat(price).toFixed(2);
}
