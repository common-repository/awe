/* 

BEGIN feedparser

Remix of jFeed by Braydon Fuller - http://braydon.com/

jFeed is Copyright (C) 2007-2011 Jean-François Hovinne - http://hovinne.com/
Dual licensed under the MIT (MIT-license.txt) and GPL (GPL-license.txt) licenses.
https://github.com/jfhovinne/jFeed/blob/master/example.html
      _____                          _______   ____  __
     / ___/__  ______  ___  _____   / ____/ | / / / / /
     \__ \/ / / / __ \/ _ \/ ___/  / / __/  |/ / / / /
    ___/ / /_/ / /_/ /  __/ /     / /_/ / /|  / /_/ /
   /____/\__,_/ .___/\___/_/      \____/_/ |_/\____/
             /_/

ASCII Art by Vijay Kumar: https://gnu.org/graphics/supergnu-ascii.html

*/

function Feed(xml ) {
    if ( xml ) this.parse( xml );
}

Feed.prototype = {

    type: '',
    version: '',
    title: '',
    link: '',
    description: '',
    parse: function( xml ) {

        if (jQuery.browser.msie) {
            var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
            xmlDoc.loadXML(xml);
            xml = xmlDoc;
        }

        if (jQuery('channel', xml).length == 1) {

            this.type = 'rss';
            var feed_class = new RSSFeed( xml );

        } else if (jQuery('feed', xml).length == 1) {

            this.type = 'atom';
            var feed_class = new AtomFeed( xml );
        }

        if ( feed_class ) jQuery.extend( this, feed_class )
;
    }
};

function RSSFeed( xml ) {
    this._parse( xml );
};

RSSFeed.prototype = {
    
    _parse: function( xml ) {
    
        if ( jQuery('rss', xml).length == 0 ) {
			this.version = '1.0';
		} else {
			this.version = jQuery('rss', xml).eq(0).attr('version');
		}

        var channel = jQuery('channel', xml).eq(0);
    
        this.title = jQuery(channel).find('title:first').text();
        this.link = jQuery(channel).find('link:first').text();
        this.description = jQuery(channel).find('description:first').text();
        this.language = jQuery(channel).find('language:first').text();
        this.updated = jQuery(channel).find('lastBuildDate:first').text();
    
        this.items = new Array();
       
        var feed = this;
        
        jQuery('item', xml).each( function() {
        
            var item = {}

            item['title'] = jQuery(this).find('title').eq(0).text();

            item['link'] = jQuery(this).find('link').eq(0).text();

            item['author'] = jQuery(this).find('dc\\:creator, creator').text();

            item['description'] = jQuery(this).find('content\\:encoded, encoded').text();
			if ( !item['description'] ) {
	            item['description'] = jQuery(this).find('description').eq(0).text();
			}

            var date_text = jQuery(this).find('pubDate').eq(0).text();
			var date_obj = false;

			if ( date_text ) {

				// Replace GMT abbev
				date_text = date_text.replace('GMT+', '+');
				date_text = date_text.replace('GMT-', '-');
				//date_text = date_text.replace('GMT', '+0000');
				//date_text = date_text.replace('EST', '+0500');

				var tzsregex = /\b(ACDT|ACST|ACT|ADT|AEDT|AEST|AFT|AKDT|AKST|AMST|AMT|ART|AST|AWDT|AWST|AZOST|AZT|BDT|BIOT|BIT|BOT|BRT|BST|BTT|CAT|CCT|CDT|CEDT|CEST|CET|CHADT|CHAST|CIST|CKT|CLST|CLT|COST|COT|CST|CT|CVT|CXT|CHST|DFT|EAST|EAT|ECT|EDT|EEDT|EEST|EET|EST|FJT|FKST|FKT|GALT|GET|GFT|GILT|GIT|GMT|GST|GYT|HADT|HAEC|HAST|HKT|HMT|HST|ICT|IDT|IRKT|IRST|IST|JST|KRAT|KST|LHST|LINT|MART|MAGT|MDT|MET|MEST|MIT|MSD|MSK|MST|MUT|MYT|NDT|NFT|NPT|NST|NT|NZDT|NZST|OMST|PDT|PETT|PHOT|PKT|PST|RET|SAMT|SAST|SBT|SCT|SGT|SLT|SST|TAHT|THA|UYST|UYT|VET|VLAT|WAT|WEDT|WEST|WET|WST|YAKT|YEKT)\b/gi;

				// estimates ... todo: daylight savings?
				var timezonenames = {
					"GMT" : "+0000",
					"CET" : "+0100",
					"EET" : "+0200",
					"EEDT" : "+0300",
					"IRST" : "+0330",
					"MSD" : "+0400",
					"AFT" : "+0430",
					"PKT" : "+0500",
					"IST" : "+0530",
					"BST" : "+0600",
					"MST" : "+0630",
					"THA" : "+0700",
					"AWST" : "+0800",
					"AWDT" : "+0900",
					"ACST" : "+0930",
					"AEST" : "+1000",
					"ACDT" : "+1030",
					"AEDT" : "+1100",
					"NFT" : "+1130",
					"NZST" : "+1200",
					"AZOST" : "-0100",
					"GST" : "-0200",
					"BRT" : "-0300",
					"NST" : "-0320",
					"CLT" : "-0400",
					"VET" : "-0430",
					"EDT" : "-0400",
					"EST" : "-0500",
					"CST" : "-0600",
					"MST" : "-0700",
					"PDT" : "-0700",
					"PST" : "-0800",
					"AKST" : "-9000",
					"MIT" : "-0930",
					"HST" : "-1000",
					"SST" : "-1100",
					"BIT" : "-1200"
				};

				//more: http://www.timeanddate.com/library/abbreviations/timezones/

				var abv = date_text.match(tzsregex);
				if ( abv ) {
					date_text = date_text.replace( abv, timezonenames[ abv ] );
				}

				date_obj = moment( date_text, "ddd, DD MMM YYYY HH:mm:ss Z" );
				if ( !date_obj.isValid() ) {
					date_obj = moment( date_text, "DD MMM YYYY HH:mm:ss Z" );
				}

				if ( !date_obj.isValid() ) {
					date_obj = false;
				}
			} else {
				// SPIP - www.spip.net and RDF
	            date_text = jQuery(this).find('dc\\:date, date').text();
				if ( date_text ) {
					date_obj = moment( date_text, "YYYY-MM-DDTHH:mm:ssZ");
				}
			}

            item['id'] = jQuery(this).find('guid').eq(0).text();

			if ( !item['id'] && item['link'] ) {
				// missing guid use the link attribute... todo: log warning
	            item['id'] = item['link'];
			}

			if ( item['id'] && item['link'] && date_obj ) {
				item['updated'] = date_obj.utc().format("YYYY-MM-DD HH:mm:ss");
	            feed.items.push(item);
			} else {
				// todo: report and record errors
			}

        });
    }
};

function AtomFeed( xml ) {
    this._parse( xml );
};

AtomFeed.prototype = {
    
    _parse: function( xml ) {
    
        var channel = jQuery('feed', xml).eq(0);

        this.version = '1.0';
        this.title = jQuery(channel).find('title:first').text();
        this.link = jQuery(channel).find('link:first').attr('href');
        this.description = jQuery(channel).find('subtitle:first').text();
        this.language = jQuery(channel).attr('xml:lang');
        this.updated = jQuery(channel).find('updated:first').text();
        
        this.items = new Array();
        
        var feed = this;
        
        jQuery('entry', xml).each( function() {
        
            var item = {};
            
            item['title'] = jQuery(this).find('title').eq(0).text();

			item['link'] = jQuery(this).find('link[rel="alternate"]').eq(0).attr('href');
			if ( !item['link'] ) {
				item['link'] = jQuery(this).find('link').eq(0).attr('href');
			}

			var author = jQuery(this).find('author').eq(0);
			item['author'] = jQuery(author).find('name').eq(0).text();

            item['description'] = jQuery(this).find('content').eq(0).text();
			if ( !item['description'] ) {
	            item['description'] = jQuery(this).find('summary').eq(0).text();
			}
            item['updated'] = jQuery(this).find('updated').eq(0).text();
            item['id'] = jQuery(this).find('id').eq(0).text();

			if ( item['id'] && item['link'] && item['updated'] ) {
				var date_obj = moment( item['updated'], "YYYY-MM-DDTHH:mm:ssZ");
				item['updated'] = date_obj.format("YYYY-MM-DD HH:mm:ss");
	            feed.items.push(item);
			} else {
				//todo
				console.log('ignoring item');
			}

        });
    }
};

/* 
END feedparser 
*/

/* 

BEGIN reader
by Braydon Fuller - http://braydon.com/

*/
 
jQuery(document).ready(function($){

	var awe = {};

	// Fetch all subscribed feeds
	awe.fetch_feeds = function(){

		var _length = 0;
		var _length_completed = 0;
		var _length_save = 0
		var _length_save_completed = 0;

		var fetch_feed = function( key ) {
			$.ajax({
				dataType: 'xml',
				type: 'post',
				url: ajaxurl,
				data: {
					action: 'request_source',
					source_id: key
				},
				success: function(doc, status, xhr) {

					if ( doc ) { 

						var feed = new Feed( doc );

						_length_save++;

						$.ajax({
							type: 'post',
							url: ajaxurl,
							data: {
								action: 'save_updates',
								updates: feed.items,
								source_id: key
							},
							complete: function(xhr, data){

								_length_save_completed++;

								if ( _length_save_completed < _length_save ) {
									$('#messages').html('<div class="updated below-h2"><p> '+Math.round(100*(_length_save_completed/_length_save))+'% Saved</p></div>');
								} else {
									$('#messages').html('<div class="updated below-h2"><p>100% Saved</p></div>');
									$('#awe-refresh-manual').removeClass('activated');
								}
							}
						});
					}
				},
				complete: function(xhr, data) {

					_length_completed++;

					if ( _length_completed < _length ) {
						$('#messages').html('<div class="updated below-h2"><p> '+Math.round(100*(_length_completed/_length))+'% Downloaded</p></div>');
					} else {
						$('#messages').html('<div class="updated below-h2"><p>100% Downloaded</p></div>');
					}

				}
			});
		}

		for ( key in awe_sources ) {
			fetch_feed( key );
			_length++;
		}

	}

	$('#awe-refresh-manual').live('click', function(){

		if ( !$(this).hasClass('activated') ) {
			$(this).addClass('activated');

			$('#messages').html('<div class="updated below-h2"><p>Fetching Feeds</p></div>');

			awe.fetch_feeds();

		}
	})

	$('.awe-update-publish').live('click', function(){
		if ( $(this).hasClass('selected') ) {
			var action = 'awe_update_unpublish';
		} else {
			var action = 'awe_update_publish';
		}
		var data = {
			action: action,
			id: $(this).attr('data-id')
		}

		jQuery.post(ajaxurl, data, function(response) {
			if ( response['status'] == true ) {
				if ( action == 'awe_update_publish' ) {
					$('[data-id="'+response['id']+'"] .awe-update-publish').addClass('selected');
				} else {
					$('[data-id="'+response['id']+'"] .awe-update-publish').removeClass('selected');
				}
			}
		}, "json");
	})

	$('.awe-update-source a').live('click', function(){
		window.open( $(this).attr('href') );
		return false;
	})

	$('.awe-update-body a').live('click', function(){
		window.open( $(this).attr('href') );
		return false;
	});

})

/* END reader */