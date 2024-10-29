jQuery.fn.serializeObject = function(){
    var o = {};
    var a = this.serializeArray();
    jQuery.each(a, function() {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

jQuery(document).ready(function($){

    var newid_number = 1

    var rules = $('ol.sortable').nestedSortable({
		disableNesting: 'no-nest',
		forcePlaceholderSize: true,
		handle: 'table.handle',
		helper:	'clone',
		items: 'li',
		maxLevels: 10,
		opacity: .6,
		placeholder: 'placeholder',
		revert: 250,
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> table.handle'
    });

    var sl = $('ol.sortable')
    if(sl.size() > 0){
		sl.css('width', (($(document).width() - sl.position().left) / 2)-20)
    }

    function init(id){

	$('#'+id+' > .handle').click(function(){
	    
	    var t = $(this).parent()
	    $('ol.sortable li').removeClass('selected')
	    t.addClass('selected')
		var left = t.position().left
		var width = $('.sortable').outerWidth()
	    $('.nodeinput', t).css('left', left+t.outerWidth()+10).css('width',width).css('top', t.position().top + 8)
	    var node = $('.nodeinput', t)
		var sortable = $('.sortable')

		var node_height = node.position().top + node.height()
		var rules_height = sortable.position().top  + sortable.height()
		if (rules_height > node_height) {
		    $('.wrap').css('height', rules_height)
		} else {
		    $('.wrap').css('height', node_height)
		}

	    return false
	})


	$('#'+id+' table > thead > tr > th > .deletenode').click(function(){
	    $('.selected').remove()
	    return false
	})
	
	$('#'+id+' table > thead > tr > th > .addnode').click(function(){
	    var newnode = $('<li>'+rule_template+'</li>')
	    var newol = $('<ol></ol>')
	    if($('#'+id).hasClass('selected')){
			$('#'+id).append(newol)
	    }

	    newol.append(newnode)

		var newid_number = 0
        var id_exists = true
		while (id_exists) {
			if($('#list_'+newid_number).size() <= 0){
				id_exists = false
			} else {
				newid_number++
			}
		}

	    newnode.attr('id', 'list_'+newid_number)
	    init('list_'+newid_number)
	    newid_number++
	    return false
	})

	var add_new = $('<span class="button add-new-h2">Add New</span>')
	add_new.click(function(){
	    var ul_request = $('#'+id+' > table > tbody > tr > td > .path-request > .request-options')
	    ul_request.append(show_request_options(id, false, false))
	})

	$('#'+id+' > table > tbody > tr > td > .path-request').append(request_inputs(id))
	$('#'+id+' > table > tbody > tr > td > .path-request').append(add_new)

	$('#'+id+' > table > tbody > tr > td > input[name="rule-path"]').blur(function(){
	    var path = $(this).attr('value')
	    var queryvars = []
	    for(var i in uri_replace){
			if(path.search(i) > 0){
				queryvars.push(i)
			}
	    }
	    $(this).attr('data-queryvars', JSON.stringify(queryvars))

	    var request_html = request_inputs(id)

	    $('#'+id+' > table > tbody > tr > td > .path-request > .request-options').remove()
	    $('#'+id+' > table > tbody > tr > td > .path-request').prepend(request_html)

	})

	$('#'+id+' > table > tbody > tr > td > input[name="rule-title"]').blur(function(){
		$('#'+id+' > table > thead > tr > th > .title').text($(this).attr('value'))
	})
    }

    function map_init(tree){
		var l = tree.length
		for(var i=0;i<l;i++){
			init('list_'+tree[i]['id'])
		    if (tree[i]['children'] != undefined){
				map_init(tree[i]['children'])
		    }
		}
    }

    map_init(rules.nestedSortable('toHierarchy', {startDepthCount: 0}))

    function record_requests(id){
		var requests = $('#'+id+' > table > tbody > tr > td > .path-request > .request-options .rule-queryvarsoptions')
		rdata = {}
		requests.each(function(){
		    var t = $(this)
		    var qv = $('.rule-queryvars', t).find('option:selected').attr('value')
			var v = $('.rule-queryvars-value', t).val()
			if ( !v ) {
				v = $('.rule-queryvars-value', t).attr('value')
			}
		    rdata[qv] = v
		})
		$('#'+id+' > table > tbody > tr > td > .path-request').attr('data-request', JSON.stringify(rdata))
    }

    // select options with our available variables
    function show_available_query_vars(id, vars, qvar){
		var vars_length = vars.length
		var select = $('<select class="rule-queryvars-value"></select>');
		if (!qvar) {
			var qvar = ''
		}
		for(var i=0;i<vars_length;i++){
		    if(vars[i] == qvar){
				var selected = ' selected'
		    } else {
				var selected = ''
		    }
		    select.append($('<option value="'+vars[i]+'"'+selected+'>'+vars[i]+'</option>'))
		}
		select.change(function(){
			record_requests(id)
		})
		return select
    }

    function show_request_options(id, query_var, data_request){
		query_vars.sort()
		var query_vars_length=query_vars.length

		// select query var
		var request_option = $('<div class="rule-queryvarsoptions"></div>')
		var select_queryvars = $('<select class="rule-queryvars"></select>')

		for(var i=0;i<query_vars_length;i++){
			if(query_var == query_vars[i]){
				var s = ' selected'
		    } else {
				var s = ''
			}
		    select_queryvars.append($('<option value="'+query_vars[i]+'"'+s+'>' + query_vars[i] + '</option>'))
		}

		select_queryvars.change(function(){
		    // the left dropdown
		    record_requests(id)
		})

		request_option.append(select_queryvars)

		var available_query_vars_raw = $('#'+id+' > table > tbody > tr > td > input[name="rule-path"]').attr('data-queryvars')
		if (available_query_vars_raw){
			var available_query_vars = JSON.parse(available_query_vars_raw)
		} else {
		    var available_query_vars = []		
		}

		var path_raw = $('#'+id+' > table > tbody > tr > td > input[name="rule-path"]').attr('value')

		request_option.append($('<span class="rule-yield"> =&gt; </span>'))

		// select query var value
		if(data_request && data_request[query_var].search('%') == 0 && available_query_vars.length > 0){

			// we have a variable, show choices with selected
			var f = show_available_query_vars(id, available_query_vars, data_request[query_var])

	    } else if (available_query_vars.length > 0 && ( !data_request.hasOwnProperty(query_var) || !data_request)) {
			// we don't have a value and we can choose a variable
			var f = $('<div class="varinputfields"></div>')
			f.append($('<span class="rule-textinput">text</span>').click(function(){
			    var tin = $('<input type="text" class="rule-queryvars-value"></input>');
			    tin.blur(function(){
					record_requests(id)
				})
			    $(this).parent().html(tin)
			}))
			f.append($('<span class="rule-seperator"> or </span>'))
			f.append($('<span class="rule-variableinput">variable</span>').click(function(){
			    $(this).parent().html(show_available_query_vars(id, available_query_vars))
			}))
	    } else {
		    // we have text
		    if(data_request){
				var v = data_request[query_var]
		    } else {
				var v = ''
		    }
		    var f = $('<input type="text" class="rule-queryvars-value" value="'+v+'"></input>')
		    f.blur(function(){
			    // the right textfield
			    record_requests(id)
		    })
		}

		request_option.append(f)

		var del = $('<span class="rule-delete">Delete</span>')
		del.click(function(){
		    $(this).parent().remove()
		})

		request_option.append(del)
	
		return request_option
    }

    function request_inputs(id){
		var html = $('<div class="request-options"></div>')
		var data_request_raw = $('#'+id+' > table > tbody > tr > td > .path-request').attr('data-request')
		if(data_request_raw){
		    var data_request = JSON.parse(data_request_raw)
		} else {
		    var data_request = false
		}

		// input fields for each request
		if(data_request && data_request != ''){
		    for(var query_var in data_request){
				html.append(show_request_options(id, query_var, data_request))
		    } 
		} else {
		    var options_html = show_request_options(id, false, false)
		    html.append(options_html)
		}

		return html
    }
   
    function rules_hierarchy(){

		function map_inputs(h){

		    var l = h.length;

		    for(var i=0;i<l;i++){

				var item = $('#list_'+h[i]['id'])
				h[i]['title'] = $('input[name="rule-title"]', item).attr('value')
				h[i]['path'] = $('input[name="rule-path"]', item).attr('value')
				var path_request = $('.path-request', item).attr('data-request')
				if(path_request){
				    h[i]['request'] = JSON.parse(path_request)
				} else {
				    h[i]['request'] = ''
				}
				h[i]['template'] = $('select[name="rule-template"] > option:selected', item).attr('value')
	
				if (h[i]['children'] != undefined){
				    map_inputs(h[i]['children'])
				}
	
		    }
		}

		var hierarchy = rules.nestedSortable('toHierarchy', {startDepthCount: 0})
	
		map_inputs(hierarchy)
	
		return hierarchy
	
    }

    $('#submit').click(function(e){
		e.preventDefault()
		$.post(ajaxurl, {
			rules: rules_hierarchy(), 
			nonce: nonce, 
			action: 'save_rules',
			dataType: 'json'
			}, function(data){
				var data = JSON.parse(data)
				if(data['status'] == false){
					$('#messages').hide()

					var l = data['messages'].length
					var messages = '<ul>'
					for(var i=0;i<l;i++){
						messages += '<li>'+data['messages'][i]+'</li>'
					}
					messages += '</ul>';

					var html = $('<div class="error below-h2" id="notice"><p>'+data['message']+'</p>'+messages+'</div>')
					$('#messages').html(html)
					$('#messages').fadeIn()
				} else {
					$('#messages').hide()
					var html = $('<div class="updated below-h2" id="message"><p>'+data['message']+'</p></div>')
					$('#messages').html(html)
					$('#messages').fadeIn()
				}
			}
		);
    })

});
