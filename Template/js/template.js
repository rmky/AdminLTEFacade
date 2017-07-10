$( document ).ready(function() {
	
	contextBarInit();
	
	// Stack modals (bootstrap tweak)
	$(document).on('show.bs.modal', '.modal', function (event) {
		$('.modal:visible').removeClass('modal-stack').not(this).addClass('modal-stack');
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
	$(document).on('hidden.bs.modal', '.modal', function (e) {
		// Remove the JS loaded with ajax dialogs when the corresponding dialog is closed
		// All this magic with the "reopen" class is due to the issue, that closing the config dialog of a DataTable
		// located in a dialog, would fire the hidden event on that dialog too - probably some kind of bug.
		if($(this).is('.modal:visible')){
			$(this).addClass('reopen');
			e.stopPropagation();
			return false;
		}
		if ($(this).parent('.ajax-wrapper').length == 1){
			if ($(this).hasClass('reopen')){
				$(this).removeClass('reopen').modal('show');
			} else {
				$(this).parent('.ajax-wrapper').remove();
			}
		}
		// Tell the page, that a dialog is still open
	    $('.modal:visible').length && $(document.body).addClass('modal-open');
	});
	
	// Fix bootstrap drowdowns go out of screen
	$(document).on("shown.bs.dropdown", ".dropdown", function () {
	    // calculate the required sizes, spaces
	    var $ul = $(this).children(".dropdown-menu");
	    var $button = $(this).children(".dropdown-toggle");
	    var ulOffset = $ul.offset();
	    // how much space would be left on the top if the dropdown opened that direction
	    var spaceUp = (ulOffset.top - $button.height() - $ul.height()) - $(window).scrollTop();
	    // how much space is left at the bottom
	    var spaceDown = $(window).scrollTop() + $(window).height() - (ulOffset.top + $ul.height());
	    // switch to dropup only if there is no space at the bottom AND there is space at the top, or there isn't either but it would be still better fit
	    if (spaceDown < 0 && (spaceUp >= 0 || spaceUp > spaceDown))
	      $(this).addClass("dropup");
	}).on("hidden.bs.dropdown", ".dropdown", function() {
	    // always reset after close
	    $(this).removeClass("dropup");
	});
	
	// Make sure masonry is relayouted when the sidebar is toggled
	$(document).on('click', "a[data-toggle='offcanvas']", function(){
		window.setTimeout(function(){
			$('.masonry').masonry('layout');
		},350);
	});
});

function contextBarInit(){
	$(document).ajaxSuccess(function(event, jqXHR, ajaxOptions, data){
		var extras = {};
		if (jqXHR.responseJson){
			extras = jqXHR.responseJson.extras;
		} else {
			try {
				extras = $.parseJSON(jqXHR.responseText).extras;
			} catch (err) {
				extras = {};
			}
		}
		if (extras.ContextBar){
			contextBarRefresh(extras.ContextBar);
		}
	});
	
	contextBarLoad();
	
	// Remove row from object basket table, when the object is removed
	$(document).on('exface.Core.ObjectBasketRemove.action.performed', function(e, requestData, inputElementId){
		var dt = $('#'+inputElementId).DataTable();
		dt.rows({selected: true}).remove();
		if (dt.rows().count() == 0){
			$('#'+inputElementId).closest('.modal').modal('hide');
		} else {
			dt.draw();
		}
	});
}

function contextBarLoad(delay){
	if (delay == undefined) delay = 100;
	
	setTimeout(function(){
		if ($.active == 0 && $('#contextBar .context-bar-spinner').length > 0){
			$.ajax({
				type: 'POST',
				url: 'exface/exface.php?exftpl=exface.AdminLteTemplate',
				dataType: 'json',
				data: {
					action: 'exface.Core.ShowWidget',
					resource: getPageId(),
					element: 'ContextBar'
				},
				success: function(data, textStatus, jqXHR) {
					contextBarRefresh(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					contextBarRefresh({});
				}
			});
		} else {
			contextBarLoad(delay*3);
		}
	}, delay);
}

function contextBarRefresh(data){
	$('#contextBar').children().not('.user-menu').remove();
	for (var id in data){
		var btn = $(' \
				<!-- '+data[id].hint+' --> \
					<li class="dropdown context-menu" id="'+id+'" data-widget="'+data[id].bar_widget_id+'"> \
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" title="'+data[id].hint+'" onclick="contextShowMenu(\'#'+id+'\');"> \
							<i class="'+data[id].icon+'"></i> \
							<span class="label label-warning context-indicator">'+data[id].indicator+'</span> \
						</a> \
						<ul class="dropdown-menu"> \
						</ul> \
					</li>');
		$('#contextBar').prepend(btn);
	}
}

function contextShowMenu(containerSelector){
	$(containerSelector).find('.dropdown-menu').empty().append('<li class="header"><div class="overlay text-center"><i class="fa fa-refresh fa-spin"></i></div></li>');
	$.ajax({
		type: 'POST',
		url: 'exface/exface.php?exftpl=exface.AdminLteTemplate',
		dataType: 'html',
		data: {
			action: 'exface.Core.ShowContextPopup',
			resource: getPageId(),
			element: $(containerSelector).data('widget')
		},
		success: function(data, textStatus, jqXHR) {
			var $data = $(data);
			$(containerSelector).find('.dropdown-menu').empty().append('<li></li>').children('li:first-of-type').append($data);
		},
		error: function(jqXHR, textStatus, errorThrown){
			adminLteCreateDialog($("body"), "error", jqXHR.responseText, jqXHR.status + " " + jqXHR.statusText);
		}
	});
}

function getPageId(){
	return $("meta[name='page_id']").attr("content");
}

function adminLteCreateDialog(parentElement, id, title, content){
	var dialog = $(' \
		<div class="modal" id="'+id+'"> \
			<div class="modal-dialog modal-lg"> \
				<div class="modal-content box"> \
					<div class="modal-header"> \
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> \
						<h4 class="modal-title">'+title+'</h4> \
					</div> \
					<div class="modal-body"> \
						' + content + ' \
					</div> \
				</div><!-- /.modal-content --> \
			</div><!-- /.modal-dialog --> \
		</div><!-- /.modal -->').resize();
	parentElement.append(dialog);
	$('#'+id).modal('show');
}

//compare arrays (http://stackoverflow.com/questions/7837456/how-to-compare-arrays-in-javascript)
//Warn if overriding existing method
if(Array.prototype.equals)
	console.warn("Overriding existing Array.prototype.equals. Possible causes: New API defines the method, there's a framework conflict or you've got double inclusions in your code.");
//attach the .equals method to Array's prototype to call it on any array
Array.prototype.equals = function (array) {
 // if the other array is a falsy value, return
 if (!array)
     return false;

 // compare lengths - can save a lot of time 
 if (this.length != array.length)
     return false;

 for (var i = 0, l=this.length; i < l; i++) {
     // Check if we have nested arrays
     if (this[i] instanceof Array && array[i] instanceof Array) {
         // recurse into the nested arrays
         if (!this[i].equals(array[i]))
             return false;       
     }           
     else if (this[i] != array[i]) { 
         // Warning - two different object instances will never be equal: {x:20} != {x:20}
         return false;   
     }           
 }       
 return true;
}
//Hide method from for-in loops
Object.defineProperty(Array.prototype, "equals", {enumerable: false});
