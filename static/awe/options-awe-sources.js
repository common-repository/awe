jQuery(document).ready(function($){

	function parse_sources(){
		var sources = []
		$('.awe-sources-item').each(function(){
			var slug = $('.awe-sources-list-slug-field', this).val()
			var title = $('.awe-sources-list-title-field', this).val()
			var url = $('.awe-sources-list-url-field', this).val()
			sources.push({ 'slug': slug, 'title':  title, 'url': url  })
		})
		return sources
	}

    $('#submit').click(function(e){
		e.preventDefault()
		$.post(ajaxurl, {
			sources: parse_sources(), 
			nonce: nonce, 
			action: 'save_sources',
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

    $('#newsource').click(function(e){
		e.preventDefault()
		$('#awe-sources-list').append($(source_template))
    })
});
