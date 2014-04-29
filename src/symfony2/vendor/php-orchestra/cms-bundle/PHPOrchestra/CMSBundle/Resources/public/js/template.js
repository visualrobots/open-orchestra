function formatForSubmit(settings){
	if(!('blocks' in settings.values)){
		settings.values.blocks = [];
	}
	treeFormatForSubmit(settings, settings.values);
	if('areas' in settings.values){
        $("#" + settings.type + "_areas").val(JSON.stringify(settings.values.areas));
    }
    if('blocks' in settings.values){
        $("#" + settings.type + "_blocks").val(JSON.stringify(settings.values.blocks));
    }
}
function treeFormatForSubmit(settings, values){
	if('blocks' in values){
		for(var i in values.blocks){
			delete values.blocks[i].method;
			delete values.blocks[i].label;
			delete values.blocks[i].is_recursive;
			if(!('nodeId' in values.blocks[i])){
				if('component' in values.blocks[i]){
					var newBlock = {'component': values.blocks[i].component};
					var percent = values.blocks[i].boPercent;
					delete values.blocks[i].component;
					delete values.blocks[i].boPercent;
					newBlock.attributes = values.blocks[i];
					settings.values.blocks.push(newBlock);
					values.blocks[i] = {'nodeId': 0, 'blockId': settings.values.blocks.length - 1, 'boPercent': percent};
				}
			}
		}
	}
	if('areas' in values){
		for(var i in values.areas){
			treeFormatForSubmit(settings, values.areas[i]);
		}
	}
}

function formatForLoad(settings){
	$( "#dialog-" + settings.type ).getValue(settings.values);
	settings.values.areas = eval($("#" + settings.type + "_areas").val());
	settings.blocks = eval($("#" + settings.type + "_blocks").val());
	delete settings.values.blocks;
	treeFormatForLoad(settings, settings.values);
	delete settings.blocks;
}
function treeFormatForLoad(settings, values){
	if('blocks' in values){
		for(var i in values.blocks){
			if(values.blocks[i].nodeId == 0){
				var refBlock = settings.blocks[values.blocks[i].blockId];
				values.blocks[i] = $.extend({'is_recursive' : true, 'method': 'create', 'component': refBlock.component}, refBlock.attributes);
			}
			else{
				values.blocks[i] = $.extend({'is_recursive' : true, 'method': 'load'}, values.blocks[i]);
			}
			$( "#dialog-blocks" ).setValue(values.blocks[i]);
			$( "#dialog-blocks" ).getValue(values.blocks[i]);
		}
	}
	if('areas' in values){
		for(var i in values.areas){
			treeFormatForLoad(settings, values.areas[i]);
			values.areas[i].is_recursive = true;
			$( "#dialog-areas" ).setValue(values.areas[i]);
			$( "#dialog-areas" ).getValue(values.areas[i]);
		}
	}
}

function resetPercent(objects){
	for(var i in objects){
		delete objects[i].boPercent;
	}
}

function moveFromTo(settings, source, destination){
	var pattern = new RegExp('^(.*)\\.(.*?)\\[(\\d*)]$');
	var copy = eval('$.extend(true, {}, '+ source + ');');
	eval(source.replace(pattern, 'source = {"path" : "$1", "type" : "$2", "index" : $3};'));
	eval(source.path + '.' + source.type + '.splice(' + source.index + ', 1)');
	resetPercent(eval(source.path + '.' + source.type));
	if(destination){
		if(isNaN(destination)){
			eval(destination + ' = $.extend({}, {"' + source.type + '" : []}, ' + destination + ');');
			eval(destination + '.' + source.type + '.push(copy);');
			resetPercent(eval(destination + '.' + source.type));
		}
		else{
			eval(source.path + '.' + source.type + '.splice(' + (source.index + destination) +  ', 0, copy)');
		}
	}
	settings.element.parseTemplate($.extend(settings, {"path": null}));
}
(function($){
    $.fn.createSubTemplate = function(settings, type){
		var values = eval(settings.path);
		var tab = eval(settings.path + '.' + type);
		var path = settings.path + '.' + type;
		values.boDirection = (values.boDirection) ? values.boDirection : 'h';
		if(tab.length > 0){
			for(var i in tab){
				var style = {};
				tab[i].boPercent = (!("boPercent" in tab[i])) ? 100 / (tab.length) : tab[i].boPercent;
				style.width = (values.boDirection == 'v') ? tab[i].boPercent + '%' : '100%';
				style.height = (values.boDirection == 'h') ? tab[i].boPercent + '%' : '100%';
				$(this).parseTemplate({"values": settings.values,
									"path": path + '[' + i + ']',
									"css": settings.css,
									"style" : style,
									"type": type,
									"element": settings.element}).appendTo($(this));
				if(i != tab.length -1){
					$('<li/>', {"class": ((values.boDirection == 'h') ? 'separator-h' : 'separator-v')}).appendTo($(this));
				}
			}
		}
	}
	$.fn.parseTemplate = function(options)
	{
		var settings = $.extend({
			"values": $(this).data(),
			"css": "ui-widget-model"
		}, options || {});

		var actions;
		if(settings.path == null){
			settings.element = this;
			settings.path = "settings.values";
			settings.type = $(this).attr('id').replace('-model', '');
			settings.style = '';
			settings.element.html('');
			actions = {
					'fa fa-cog' : [
					   	'$( "#dialog-" + settings.type ).data("settings", settings);',
						'$( "#dialog-" + settings.type ).data("this_values", this_values);',
						'$( "#dialog-" + settings.type ).dialog( "open" );'
				    ]
				};
		}
		else{
			actions = {
					'fa fa-trash-o' : [
					    'moveFromTo(settings, settings.path);',
						'settings.element.parseTemplate($.extend(settings, {"path": null}));'
					],
					'fa fa-cog' : [
					   	'$( "#dialog-" + settings.type ).data("settings", settings);',
						'$( "#dialog-" + settings.type ).data("this_values", this_values);',
						'$( "#dialog-" + settings.type ).dialog( "open" );'
					],
					'fa fa-plus-circle' : [
						'moveFromTo(settings, settings.path, +1);',
						'settings.element.parseTemplate($.extend(settings, {"path": null}));'
						],
					'fa fa-minus-circle' : [
						'moveFromTo(settings, settings.path, -1);',
						'settings.element.parseTemplate($.extend(settings, {"path": null}));'
						]
				};
		}
		if(settings.init){
		    formatForLoad(settings);
		    delete settings.init;
		}

		var this_values = eval(settings.path);
		var span = $( "<span/>", {"class": settings.css, "text": (this_values.label) ? this_values.label : 'No Record'});
		var div = $( "<div/>", {"class": settings.css});
		var li = $( "<li/>", {"class": settings.css, "css": settings.style});
		var ul = $( "<ul/>", {"class": settings.css});
		var action = $( "<span/>", {"class": "action"});
		span.appendTo(div);
		action.appendTo(div);
		div.appendTo(li);

		for(var i in actions){
			$("<i/>", {"class": i}).click({'js': actions[i].join('')}, function(event){
				event.stopPropagation();
				eval(event.data.js);
			}).appendTo(action);
		}

		var found = false;
		for(var i in this_values){
			try{
				if('is_recursive' in this_values[i][0]){
					found = true;
					ul.addClass(settings.css + '-' + i);
					ul.createSubTemplate(settings, i);
					if(this_values.boDirection == 'v'){
						ul.children().css('display', 'inline-block');
						ul.children().addClass('resize-v');
					}
					else{
						ul.children().addClass('resize-h');
					}
					ul.children().addClass(settings.css + '-' + i);
				}
			}
			catch (e){}
		}
		
		if(found || $( "#dialog-" + settings.type ).dialog("option", "addArray").length){
			ul.appendTo(div);
			ul.data('path', settings.path);
		}
		if(settings.path == "settings.values"){
			ul = $( "<ul/>", {"class": settings.css + ' ' + settings.css + '-' + settings.type,
				"css": {"display": settings.style}});
			li.appendTo(ul);
			ul.appendTo($(this));
			
			if($( "#dialog-" + settings.type ).dialog("option", "addArray").length){
				$('ul.' + settings.css).parent().droppable({
					greedy: true,
					tolerance: "pointer",
					hoverClass: 'over',
					drop : function(event, ui){
						moveFromTo(settings, ui.draggable.data('path'), $(this).find('ul').data('path'));
					},
					accept: function(event){
						var source = $(this).children('ul').attr("class").split(' ');
						var destination = event.attr("class").split(' ');
						var found = true;
						
						for(var i in source){
							found = found && ($.inArray(source[i], destination) != -1);
						}
						return found;
					}
				});
				$('li.' + settings.css).draggable({
					opacity: 0.5,
					containment: settings.element,
					zIndex: 100
				});
				separator = {
					'separator-h' : {'axe' : 'y', 'origine': 'top', 'vector':'height'},
					'separator-v' : {'axe' : 'x', 'origine': 'left', 'vector':'width'}
				};
				for(var i in separator){
					(function(s){
						$('li.' + i).draggable({
							opacity: 1,
							zIndex: 100,
							axis: s.axe,
							drag: function(event, ui){
								size = $(this).offset()[s.origine] - $(this).data('source');
								$(this).prev().changeSize(s.vector, $(this).data('prev') + size, settings);
								$(this).next().changeSize(s.vector, $(this).data('next') - size, settings);
							},
							start: function(){
								$(this).data('source', $(this).offset()[s.origine]);
								$(this).data('prev', eval('$(this).prev().' + s.vector + '()'));
								$(this).data('next', eval('$(this).next().' + s.vector + '()'));
							},
							stop: function(event, ui){
								$(this).css(s.origine, 'auto');
								settings.element.parseTemplate($.extend(settings, {"path": null}));
							}
						})
					})(separator[i]);
				}
			}
		}
		else{
			li.data('path', settings.path);
		}
		return li;
	}
    $.fn.getValue = function(values){
        var pre = $(this).attr('id').replace('dialog-', '') + '_';
        for(var i in values){
			try{
	        	if(!('is_recursive' in values[i][0])){
	        		delete values[i];
	        	}
			}
			catch(e){}
        }
        $(this).find(":input").not('button').each(function(){
	        var id = $(this).attr( "id" ).replace(pre, '');
	        if(!$(this).hasClass('not-mapped')){
	        	values[id] = $(this).val();
	        	if($(this).hasClass('used-as-label')){
	        		values.label = $(this).val();
	        		if($(this).is('select')){
	        			values.label = $(this).find(":selected").text();
	        		}
	        	}
	        }
        });
    }
    $.fn.setValue = function(values){
    	var pre = $(this).attr('id').replace('dialog-', '') + '_';
    	var ref = $(this);
        ref.find(":input").each(function(){
        	var id = $(this).attr("id").replace(pre, '');
        	var value = '';
        	if(id in values){
        		value = values[id]
        	}
        	if(value != $(this).val()){
	    		$(this).val(value);
	        	try{
		        	if('change' in $._data($(this)[0], 'events')){
		        		$(this).change();
		        		ref.setValue(values);
		        	}
	        	}
	        	catch(e){}
        	}
    	});
    }
	$.fn.createSelect = function(label, id, values, value_key, value_label){
        var select = $( "<select/>", {"id": id, "name": id});
        $( "<option/>", {"value": "", "text": "--------"}).appendTo(select);
        for(var i in values){
            $( "<option/>", {"value": values[i][value_key], "text": values[i][value_label]}).appendTo(select);
        }
        $( "<label/>", {"text": label, "for": id}).appendTo($(this));
        select.appendTo($(this));
        return select;
    }
	$.fn.addSpan = function(name){
	    var span =  $(this).children('span.' + name);
	    if(span.length == 0){
			span = $( "<span/>", {"class": name}).appendTo($(this));
	    }
	    else{
	    	span.html('');
	    }
	    return span;
	}
	$.fn.changeSize = function(coordinate, size, settings){
		eval('size = 100 * (size) / $(this).offsetParent().' + coordinate + '();');
		eval($(this).data('path') + '.boPercent = ' + size);
		$(this).css(coordinate, size + '%');
	}
})(jQuery);

var dialog_parameter = {
    resizable: false,
    width:530,
    modal: true,
    autoOpen: false,
    open: function ( event, ui) {
        var data = $(this).data();
        var found = false;
        var buttons = $.extend({}, $(this).dialog("option", "allbuttons"));
        var addArray = $(this).dialog("option", "addArray");
        for(var i in addArray){
            if(addArray[i] in data.this_values){
                found = true;
                buttons["Add " + addArray[i].charAt(0).toUpperCase() + addArray[i].slice(1)] = (function (name){
                	return function(){
                		resetPercent(data.this_values[name]);
                        data.this_values[name].push({'is_recursive' : true});
                        $(this).dialog( "close" );
                	}
                })(addArray[i]);
            }
        }
        if(!found){
            for(var i in addArray){
                buttons["Add " + addArray[i].charAt(0).toUpperCase() + addArray[i].slice(1)] = (function (name){
                	return function(){
                        data.this_values[name] = new Array();
                        data.this_values[name].push({'is_recursive' : true});
                        $(this).dialog( "close" );
                	}
                })(addArray[i]);
            }
        }
        $(this).find("[type='submit']").each(function(){
            $(this).hide();
            buttons["Save"] = function(){
                var data = $(this).data();
                $(this).getValue(data.this_values);
                formatForSubmit(data.settings);
                var form = $(this).find('form');
                url = form.attr('action');
                params = form.serialize();
                $(this).dialog( "close" );
                treeAjaxCall(url, params);
           }
        });
        $(this).dialog("option", "buttons", buttons);
        $(this).setValue(data.this_values);
    },
    allbuttons: {
        "Apply": function() {
            var data = $(this).data();
            $(this).getValue(data.this_values);
            $(this).dialog( "close" );
        },
    },
    close: function ( event, ui) {
        var data = $(this).data();
        data.settings.element.parseTemplate($.extend(data.settings, {"path": null}));
    }
};

var tree_parameter = {
    extensions: ['persist', 'dnd'],
    /*persist: {
        expandLazy: true
    },*/
    autoActivate: false,
    autoScroll: true,
    clickFolderMode: 1,
    keyboard: false,
    selectMode: 1
};

var treeNodesMenuOptions = [
                            {'title': 'Créer une sous-page', 'cmd': 'createNode'},
                            {'title': 'Supprimer', 'cmd': 'deleteNode'}/*,
                            {'title': '----'},*/
                           ];

var treeTemplatesMenuOptions = [
                                {'title': 'Créer un template', 'cmd': 'createTemplate'},
                                {'title': 'Supprimer', 'cmd': 'deleteTemplate'}
                               ];

var treePreviousJs = {
        'deleteNode': function(){return confirmTreeDelete();},
        'deleteTemplate': function(){return confirmTemplateDelete();}
};

function treeAjaxCall(url, params)
{
    $('#rightbox-content').html('<h1><i class="fa fa-cog fa-spin"></i> Loading...</h1>');
    $.ajax({
        'type': 'POST',
        'url': url,
        'data': params,
        'success': function(response) {
            $('[id^="dialog-"]').dialog("destroy");
            
            for (var selector in response) {
                $(selector).html(response[selector]);
            }
        },
        'dataType': 'json'
    });
}

function confirmTreeDelete()
{
    return confirm("Vous êtes sur le point de supprimer une page ainsi que toute la sous-arborescence associée.\n\nSi vous souhaitez tout supprimer, cliquez sur \"Ok\", sinon cliquez sur \"Annuler\" et déplacez d'abord la sous-arborescence.")
}

function confirmTemplateDelete()
{
    return confirm("Etes-vous certain de vouloir supprimer ce template ?")
}

function confirmDragNDrop()
{
    return confirm("Etes-vous certain de vouloir déplacer la sous-arcborescence ici ?")
}
