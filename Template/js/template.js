$( document ).ready(function() {
	pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');
	pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');
	
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
	
	// Refresh ObjectBasekt and favorites counter
	$(document).on('exface.Core.ObjectBasketAdd.action.performed', function(e){pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');});
	$(document).on('exface.Core.ObjectBasketRemove.action.performed', function(e){pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');});
	
	// Remove row from object basket table, when the object is removed
	$(document).on('exface.Core.ObjectBasketRemove.action.performed', function(e, data){
		// FIXME for some reason finding the table by jquery does not work after another dialog was open 
		// (e.g. a dialog of one of the basket buttons)
		//var dt = $('#object_basket').find('.dataTables_scrollBody>table.dataTable').first().DataTable();
		var dt = object_basket_DataTable_table;
		dt.rows({selected: true}).remove();
		if (dt.rows().count() == 0){
			$('#object_basket').modal('hide');
		} else {
			dt.draw();
		}
	});
	
	// Make sure masonry is relayouted when the sidebar is toggled
	$(document).on('click', "a[data-toggle='offcanvas']", function(){
		window.setTimeout(function(){
			$('.masonry').masonry('layout');
		},350);
	});
});

function pinnedObjectsRefresh(containerSelector, counterSelector){
	$(containerSelector).empty();
	$.post(
			pinnedObjectsBaseUrl() + "&action=exface.Core.ObjectBasketFetch", 
		function( data ) {
			pinnedObjectsMenu(data, containerSelector, counterSelector);
		},
		'json'
	);
}

function pinnedObjectsRemoveObject(objectId, containerSelector, counterSelector){
	$(containerSelector).empty();
	$.post(
		pinnedObjectsBaseUrl() + "&action=exface.Core.ObjectBasketRemove&fetch=1&object=" + objectId + '&data={"oId": "' + objectId + '"}', 
		function( data ) {
			pinnedObjectsMenu(data, containerSelector, counterSelector);
		},
		'json'
	);
}

function pinnedObjectsBaseUrl(){
	return "exface/exface.php?exftpl=exface.AdminLteTemplate&resource="+getPageId();
}

function pinnedObjectsMenu(data, containerSelector, counterSelector){
	var total = 0;
	for (var i=0; i<data.length; i++){
		var rowObjCount = data[i]['instance_counter'];
		total = total + rowObjCount;
		var btnRemove = '<a class="pull-left" href="javascript:pinnedObjectsRemoveObject(\'' + data[i]['object_id'] + '\',\'' + containerSelector + '\',\'' + counterSelector + '\');"><i class="fa fa-times" aria-hidden="true"></i></a>';
		var row = $('<li><span class="menu-actions pull-right">'+btnRemove+'</span><a href="#">' + rowObjCount + 'x ' + data[i]['object_name'] + '</a></li>');
		row.children('a').click({object: data[i]}, function(e){
			$.ajax({
				type: 'POST',
				url: 'exface/exface.php?exftpl=exface.AdminLteTemplate',
				dataType: 'html',
				data: {
					action: 'exface.Core.ObjectBasketFetch',
					resource: getPageId(),
					object: e.data.object.object_id,
					output_type: 'DIALOG'
				},
				success: function(data, textStatus, jqXHR) {
	               	if ($('#ajax-dialogs').length < 1){
	               		$('body').append('<div id=\"ajax-dialogs\"></div>');
	       			}
	               	$('#ajax-dialogs').append('<div class=\"ajax-wrapper\">'+data+'</div>');
                   	$('#ajax-dialogs').children().last().children('.modal').last().modal('show');
				},
				error: function(jqXHR, textStatus, errorThrown){
					adminLteCreateDialog($("body"), "error", jqXHR.responseText, jqXHR.status + " " + jqXHR.statusText);
				}
			});
		});
		$(containerSelector).append( row );
	}
	$(counterSelector).text(total);
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
