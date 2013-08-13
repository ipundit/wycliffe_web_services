$(document).ready(function() {
	$('.contentFilter').click(function(evt) { fireContentFilterChange(); });
	$('#detail').change(function(evt) { fireDetailChange(); });
	fireDetailChange();
});

function fireDetailChange() {
	if (!fireContentFilterChange()) { return; }

	var startDate = "2013";
	var endDate = "2013";
	
	switch ($('#detail').val()) {
	case 'year':
		break;
	case 'quarter':
		startDate = "Q4/2013";
		endDate = "Q4/2013";
		break;
	case 'month':
		startDate = "08/2013";
		endDate = "08/2013";
		break;
	}
	
	$('#startDate').val(startDate);
	$('#endDate').val(endDate);
}

function fireContentFilterChange() {
	var error = '';
	if (supportedFilter()) {
		var checkboxes = $('.contentFilter:checked');
		if (checkboxes.length == 0) {
			$('#submit').attr('disabled','disabled');
		} else {
			$('#submit').removeAttr('disabled');
		}
		$('#error').html('');
		return true;
	}

	$('#submit').attr('disabled','disabled');
	$('#error').html('The quarter and month filters are only supported for Sections 4 and 5.');
	return false;
}

function supportedFilter() {
	var checkboxes = $('.contentFilter:checked');
	if (checkboxes.length == 0) {
		$('#submit').attr('disabled','disabled');
		return true;
	}

	if ($('#detail').val() == 'year') { return true; }
	return $('#show_budget').is(':checked') || $('#show_journals').is(':checked');
}

$('span.budgetenablelink a').click(function(evt){
  var budget_id = $(this).attr('href').replace('#','')

  var other_budget = $('a[name='+budget_id.replace(/\./g,'\\.')+']').closest('.budget_surround')
  other_budget.show()
  var budget_h3 = other_budget.find('h3')
  var hide_link = $('<span class="noprint headlinetag budgetlink budgetdisablelink">&nbsp; (<a href="#">Hide</a>)</span>')
  
  $(this).closest('span').addClass('hide')
  budget_h3.find('.budgetdisablelink').remove()
  budget_h3.append(hide_link)
  hide_link.click(function(){
    $('a[href=#'+budget_id.replace(/\./g,'\\.')+']').closest('span').removeClass('hide')
    other_budget.hide()
    return false
  })
})

$(function(){
  $('.has_mutiple_budgets .budget_surround h3').each(function(){
    if(!$(this).find('span').size()>0){
        $(this).closest('.budget_surround').hide()
    }
  })
  $('.budgetenablelink a').each(function(){
    var link = $(this)
    var id = link.attr('href').replace('#','')
    if($('a[name='+id.replace(/\./g,'\\.')+']').size()==0){
        link.closest('.budgetenablelink').hide()
    }
  })
});


// Custom plugin to handle expanding options on click
(function( $ ){
  $.fn.suboptionsOnCheck = function() {
    var elements = this
    var setVisibility = function(){
      elements.each(function(){
        var input = $(this)
        var div = input.nextAll('.suboptions').first()
		    if(div.size()==0){ div = input.parent().nextAll('.suboptions').first() }
        var is_checked = input.filter(':checked').size()>0||input.prop('selectedIndex')>0
        // Show/hide suboptions
        div.toggle(is_checked)
        // Disabled suboption inputs when hidden, so they don't submit
        div.find('input, select').each(function(){ $(this).attr('disabled',!is_checked) })
      })
    }
    setVisibility()
    elements.bind('change update',setVisibility)
    $('document').bind('load ready update',setVisibility)
  };
})(jQuery);


// Handle expandable sections.
// - Sections are visible with no JS
// - With JS, have a togglable show hide link
(function( $ ){
    $(function(){
        // Setup
        $("div.toggle-open").each(function(){
            var section$ = $(this).hide()
            var link$ = $("<a href=\"#\" class=\"toggle-link closed\"><span class=\"actionName\">Show</span> <span class=\"name\"></span></a>")
            link$.find(".name").text(section$.attr('data-toggle-name'))
            link$.insertBefore(section$).data('section', section$)
        })
        // Show on click
        $(document).delegate("a.toggle-link.closed","click", function(){
            link$ = $(this).addClass('open').removeClass('closed')
            link$.data('section').slideDown()
            link$.find(".actionName").text("Hide")
        })
        // Hide on click
        $(document).delegate("a.toggle-link.open","click", function(){
            link$ = $(this).addClass('closed').removeClass('open')
            link$.data('section').hide()
            link$.find(".actionName").text("Show")
        })
    })
})(jQuery);