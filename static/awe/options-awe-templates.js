jQuery(document).ready(function($){
	var em1 = $('#editor1menu')
    var em2 = $('#editor2menu')

	var display_message = function(data){
		if(data['status'] == false){
			$('#messages').hide()
			var html = $('<div class="error below-h2" id="notice"><p>'+data['message']+'</p></div>')
			$('#messages').html(html)
			$('#messages').fadeIn()
		} else {
			$('#messages').hide()
			var html = $('<div class="updated below-h2" id="message"><p>'+data['message']+'</p></div>')
			$('#messages').html(html)
			$('#messages').fadeIn()
		}
	}


    em1.change(function(){
		var data = {
			action: 'read_template_file',
			template: $(':selected', this).attr('value'),
			nonce: nonce,
			dataType: 'json'
		};

		$.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
			if(data['status'] == true){
				editor1.getSession().setValue(data['template']);
			}
		});
	})

	em2.change(function(){
		var data = {
			action: 'read_template_file',
			template: $(':selected', this).attr('value'),
			nonce: nonce,
			dataType: 'json'
		};

		$.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
			if(data['status'] == true){
				editor2.getSession().setValue(data['template']);
			}
		})
	})

	var save1 = $('#editor1save')
    var save2 = $('#editor2save')

	save1.click(function(){

	    var template_data = editor1.getSession().getValue();
	    var data = {
			action: 'save_template_file',
			template: $(':selected', em1).attr('value'),
			dataType: 'json',
			nonce: nonce,
			template_data: template_data
		}

		$.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
		})
	})

	save2.click(function(){
		var template_data = editor2.getSession().getValue();
		var data = {
			action: 'save_template_file',
			template: $(':selected', em2).attr('value'),
			dataType: 'json',
			nonce: nonce,
			template_data: template_data
		}

		$.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
		})
	})

	var new1 = $('#editor1new')
	var delete1 = $('#editor1delete')
	var field1 = $('#editor1field')

	new1.click(function(){

	    var template_field = field1.attr('value');
	    var data = {
	        action: 'new_template_file',
			nonce: nonce,
			template_field: template_field
	    };

	    $.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
			if(data['status'] == true){
				var selected1 = $('#editor1menu')[0].options[$('#editor1menu').attr('selectedIndex')].value
				$('#editor1menu').html(data['templates']).val(selected1)
				var selected2 = $('#editor2menu')[0].options[$('#editor2menu').attr('selectedIndex')].value
				$('#editor2menu').html(data['templates']).val(selected2)
				field1.attr('value','')
			}
		})
	})

	delete1.click(function(){
		var template_field = field1.attr('value');
		var data = {
			action: 'delete_template_file',
			template_field: template_field,
			nonce: nonce
		}

	    $.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
			if(data['status'] == true){
				var selected1 = $('#editor1menu')[0].options[$('#editor1menu').attr('selectedIndex')].value
				$('#editor1menu').html(data['templates']).val(selected1)
				var selected2 = $('#editor2menu')[0].options[$('#editor2menu').attr('selectedIndex')].value
				$('#editor2menu').html(data['templates']).val(selected2)
				field1.attr('value','')
			}
		})
	})

	var new2 = $('#editor2new')
	var delete2 = $('#editor2delete')
	var field2 = $('#editor2field')

	new2.click(function(){

	    var template_field = field2.attr('value');
	    var data = {
	        action: 'new_template_file',
			template_field: template_field,
			nonce: nonce
	    }

	    $.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
			if(data['status'] == true){
				var selected1 = $('#editor1menu')[0].options[$('#editor1menu').attr('selectedIndex')].value
				$('#editor1menu').html(data['templates']).val(selected1)
				var selected2 = $('#editor2menu')[0].options[$('#editor2menu').attr('selectedIndex')].value
				$('#editor2menu').html(data['templates']).val(selected2)
				field2.attr('value','')
			}
		})
	})

	delete2.click(function(){
	    var template_field = field2.attr('value');
	    var data = {
	        action: 'delete_template_file',
			template_field: template_field,
			nonce: nonce
	    }

	    $.post(ajaxurl, data, function(response) {
			var data = JSON.parse(response)
			display_message(data)
			if(data['status'] == true){
				var selected1 = $('#editor1menu')[0].options[$('#editor1menu').attr('selectedIndex')].value
				$('#editor1menu').html(data['templates']).val(selected1)
				var selected2 = $('#editor2menu')[0].options[$('#editor2menu').attr('selectedIndex')].value
				$('#editor2menu').html(data['templates']).val(selected2)
				field2.attr('value','')
			}
		})
	})

	var editorcontainer1 = $('#templateeditor')
	var editorcontainer2 = $('#templateeditor2')
	var editor1 = ace.edit("templateeditor")
	var editor2 = ace.edit("templateeditor2")

	editor1.setKeyboardHandler(require("ace/keyboard/keybinding/emacs").Emacs)
	editor2.setKeyboardHandler(require("ace/keyboard/keybinding/emacs").Emacs)		

	var PHPMode = require("ace/mode/php").Mode
	editor1.getSession().setMode(new PHPMode())
	editor2.getSession().setMode(new PHPMode())

})
