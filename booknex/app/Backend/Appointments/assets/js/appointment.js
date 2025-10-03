(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		booknetic.dataTable.actionCallbacks['info'] = function (ids)
		{
			booknetic.loadModal('info', {'id': ids[0]});
		}

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			booknetic.loadModal('edit', {'id': ids[0]});
		}

		booknetic.dataTable.actionCallbacks['change_status'] = function (ids)
		{
			booknetic.loadModal('change_status', {'ids': ids});
		}

		$(document).on('click', '#addBtn', function ()
		{
			booknetic.loadModal('add_new', {});
		});

	});

})(jQuery);

