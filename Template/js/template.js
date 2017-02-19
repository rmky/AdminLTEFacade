$( document ).ready(function() {
	pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');
	
	// Remove the JS loaded with ajax dialogs when the corresponding dialog is closed
	$(document).on('hidden.bs.modal', '#ajax-dialogs>.modal', function (event) {
		$(this).next('script').remove();
		$(this).remove();
	});
	
	// Stack modals (bootstrap tweak)
	$(document).on('show.bs.modal', '.modal', function (event) {
		$('.modal:visible').removeClass('modal-stack').not(this).addClass('modal-stack');
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
	
	// Refresh ObjectBasekt and favorites counter
	$(document).on('exface.Core.ObjectBasketAdd.action.performed', function(e){pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');});
	$(document).on('exface.Core.ObjectBasketRemove.action.performed', function(e){pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');});
	
	// Remove row from object basket table, when the object is removed
	$(document).on('exface.Core.ObjectBasketRemove.action.performed', function(e, data){
		$('#object_basket').find('.dataTables_scrollBody>table.dataTable').DataTable().rows({selected: true}).remove().draw();
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
		//row.children('a').click({object: data[i]}, function(e){pinnedObjectsModalShow($('#pinned-modal'), e.data.object)});
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
	               	$('#ajax-dialogs').append(data);
	               	$('#ajax-dialogs').find('.modal').first().modal('show');
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
				<div class="modal-content"> \
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