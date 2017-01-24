$( document ).ready(function() {
	pinnedObjectsRefresh('#exf-pinned-list', '#exf-pinned-counter');
});

function pinnedObjectsRefresh(containerSelector, counterSelector){
	$(containerSelector).empty();
	$.post(
		"exface/exface.php?exftpl=exface.JEasyUiTemplate&action=exface.Core.ObjectBasketFetch&resource=[~id~]", 
		function( data ) {
			pinnedObjectsMenu(data, containerSelector, counterSelector);
		},
		'json'
	);
}

function pinnedObjectsRemoveObject(objectId, containerSelector, counterSelector){
	$(containerSelector).empty();
	$.post(
		"exface/exface.php?exftpl=exface.JEasyUiTemplate&action=exface.Core.ObjectBasketRemove&object=" + objectId + "&resource=[~id~]", 
		function( data ) {
			pinnedObjectsMenu(data, containerSelector, counterSelector);
		},
		'json'
	);
}

function pinnedObjectsMenu(data, containerSelector, counterSelector){
	var total = 0;
	for (var i=0; i<data.length; i++){
		var rowObjCount = Object.keys(data[i]['instances']).length;
		total = total + rowObjCount;
		var row = $('<li><span class="menu-actions pull-right"><a href="javascript:pinnedObjectsRemoveObject(\'' + data[i]['object_id'] + '\',\'' + containerSelector + '\',\'' + counterSelector + '\');"><i class="fa fa-trash" aria-hidden="true"></i></a></span><a href="#">' + rowObjCount + 'x ' + data[i]['object_name'] + '</a></li>');
		$(containerSelector).append( row );
	}
	$(counterSelector).text(total);
}