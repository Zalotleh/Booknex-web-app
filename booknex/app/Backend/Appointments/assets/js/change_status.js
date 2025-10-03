(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(".fs-modal").on('click', '#changeAppointmentStatusBtn', function ()
		{
			var ids		        =	$("#change_status_JS").data('appointment-ids'),
				status	        =	$('.appointment-status-btn > button').attr('data-status'),
				run_workflows	=	$("#input_run_workflows").is(':checked') ? 1 : 0;

			if( status == '' )
			{
				booknetic.toast(booknetic.__('fill_all_required'), 'unsuccess');
				return;
			}

			booknetic.ajax( 'appointments.change_status_save', {ids: String(ids).split(','), status: status, run_workflows: run_workflows}, function(result)
			{
				booknetic.modalHide($(".fs-modal"));
				booknetic.dataTable.reload( $("#fs_data_table_div") );
			});
		} ).on('click', '.appointment-status-panel [data-status]', function ()
		{
			$(this).closest('.appointment-status-panel').prev().attr('data-status', $(this).attr('data-status') );
			$(this).closest('.appointment-status-panel').prev().children('i').attr('class', $(this).children('i').attr('class') );
			$(this).closest('.appointment-status-panel').prev().children('i').attr('style', $(this).children('i').attr('style'));
			$(this).closest('.appointment-status-panel').prev().children('.c_status').text($(this).text().trim() );
		});

	});

})(jQuery);