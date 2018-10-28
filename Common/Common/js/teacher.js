// JavaScript Document
$(function(){
	$('a.del').on('click',function(){
		var msg = "您真的确定要删除吗？"; 
		if (confirm(msg)==true){ 
			return true; 
		}else{ 
			return false; 
		}
	});

	$('a#back_button').on('click',function(){
		history.back();
		return false;
	});

});
