/**
 * Created by lingda 20160123
 * 用于在多选情况下，发送选中的id
 * 使用要求:
 * 用于多选的checkBox。name="ids[]"
 * 按钮或超链接中要有url属性，指定发送的目标。
 * 调用方法：
 * 在按钮或超链接上  onclick="postIds(this);"
 */
function postwith(url, args) {  
    var myForm = document.createElement("form");  
    myForm.method = "post";  
    myForm.action = url;
    for ( var k in args) {  
        var myInput = document.createElement("input");  
        myInput.setAttribute("name", k);  
        myInput.setAttribute("value", args[k]);  
        myForm.appendChild(myInput);  
    }  
    document.body.appendChild(myForm);  
    myForm.submit();  
    document.body.removeChild(myForm);  
} 
function postIds(element,name) {
	if(!name){
		name="ids";
	}
    var url = $(element).attr('url');
    var ids = $('.ids:checked');
    if (ids.length > 0) {
    	var param = new Array();
        var i=0;
        ids.each(function () {
            param[i]=$(this).val();
            i++;
        });
        if (url != undefined && url != '') {
        	var arg = new Array();
        	arg[name]=param;
            postwith(url,arg);
        }
    }
}
function postIdsforDel(element) {
	if(confirm("确定要删除吗？")){
	    var url = $(element).attr('url');
	    var id = $('.ids:checked');
	    if (id.length > 0) {
	    	var param = new Array();
	        var i=0;
	        id.each(function () {
	            param[i]=$(this).val();
	            i++;
	        });
	        if (url != undefined && url != '') {
	        	var ids = new Array();
	        	ids["ids"]=param;
	        	$.post(url, {
					ids : ids['ids']
				}, function(res) {
					if (res.status) {
						toast.success(res.info);
						setTimeout(function() {
							location.reload();
						}, 1500);
					} else {
						toast.error(res.info);
					}
				}, 'json');
	        }
	    }
	}
} 