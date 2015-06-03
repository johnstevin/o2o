function showSubMenu(rootI, isFast) {
    var cssChangeDelay = isFast ? 0 : cssDelay;
    if (rootMenuIndex == rootI && subMenuIndex == -1) {//相同的功能
        return;
    }
    if (menuJson[rootI].children == null || menuJson[rootI].children == "") {//没有下级功能
        if (rootI == 0) {//现在是首页
            if (rootMenuIndex != -1) {//且不是初始状态
                //取消选中状态样式
                $('#nav-root-li-' + rootMenuIndex).removeClass("active", cssChangeDelay);
            }
        }
        else {//现在不是首页
            if (rootMenuIndex != 0) {//且之前不是首页
                //取消选中样式
                $('#nav-root-li-' + rootMenuIndex).removeClass("active", cssChangeDelay);
            }
            //设置选中状态样式
            $('#nav-root-li-' + rootI).addClass("active", cssChangeDelay);
        }
        //修改参数记录
        rootMenuIndex = rootI;
        setCookie(cookiesPrefix + "rootMenuId", menuJson[rootMenuIndex].id);
        if (haveSubMenu) {//是左右结构
            haveSubMenu = 0;
            //变化布局之前需要清空面板，否则原先内容会显示到变化结束才刷新新的功能
            $('#rg-container-fun').html('<div class="panel-loading"></div>');
            //变更布局样式
            $("#rg-col-nav-sub").addClass("rg-hidden", 0);
            $("#rg-container-main").switchClass("container-fluid", "container", cssChangeDelay, cssEasing);
            $("#rg-col-nav-sub").removeClass("col-sm-3 col-md-2 col-lg-2", cssChangeDelay, cssEasing);
            //布局改变之后需要加载功能，在回调里边已经运行加载
            $("#rg-col-main").switchClass("col-sm-9 col-md-10 col-lg-10", "col-sm-12 col-md-12 col-lg-12", cssChangeDelay, cssEasing, function () {
                //变化完毕，加载功能
                if (!isFast) {//不是快速启动，快速启动是刷新，刷新在显示顶级菜单之时不加载功能
                    showFun(rootI, -1);
                }
            });

            //没有下级的功能就没有sub导航，删除sub导航菜单
            $('#rg-title-nav-sub').html("");
            $('#rg-menu-nav-sub').html("");
        }
        else {
            //加载功能
            //变化完毕，加载功能
            if (!isFast) {//不是快速启动，快速启动是刷新，刷新在显示顶级菜单之后不加载功能
                showFun(rootI, -1);
            }
        }
    }
    else {//有下级功能，显示列表
        //subMenu的处理
        if (rootMenuIndex != rootI) {//点击的是不同的root功能
            if (rootMenuIndex != 0) {//之前不是首页（如果不是首页，也有下级功能，那就一定初始化过了）
                //取消选中样式
                $('#nav-root-li-' + rootMenuIndex).removeClass("active", cssChangeDelay);
            }
            //修改参数记录
            rootMenuIndex = rootI;
            setCookie(cookiesPrefix + "rootMenuId", menuJson[rootMenuIndex].id);
            //设置菜单标题
            $('#rg-title-nav-sub').html(menuJson[rootI].text + "功能");
            //设置菜单
            var sunMenuStr = '';
            for (var i = 0; i < menuJson[rootI].children.length; i++) {
                sunMenuStr += '<a href="'+menuJson[rootI].children[i].url+'" class="list-group-item pjax" id="nav-sub-a-' + rootI + '-' + i + '">　' + menuJson[rootI].children[i].text + '</a>';
            }
            $('#rg-menu-nav-sub').html(sunMenuStr);
            //设置选中状态样式
            $('#nav-root-li-' + rootI).addClass("active", cssChangeDelay);
        }
        else {//点击的是相同的root功能
            //取消subMenu的选中状态。现在显示的是相同的root不同的sub功能，点击root功能按钮只需要回到默认功能，取消sub按钮的激活状态，不需要更新菜单
            $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).removeClass("active", 400);
        }
        //加载功能处理，必须放在subMenu处理的后边。因为subMenu处理中会用到已经激活的subMenuIndex，如果加载放在前边，这个参数会变成现在需要激活的
        if (!haveSubMenu) {//不是是左右结构
            haveSubMenu = 1;
            //变化布局之前需要清空面板，否则原先内容会显示到变化结束才刷新新的功能
            $('#rg-container-fun').html('<div class="panel-loading"></div>');
            //变更布局样式
            $("#rg-col-main").switchClass("col-sm-12 col-md-12 col-lg-12", "col-sm-9 col-md-10 col-lg-10", cssChangeDelay, cssEasing, function () {
                $("#rg-col-nav-sub").removeClass("rg-hidden", 0);
                $("#rg-col-nav-sub").addClass("col-sm-3 col-md-2 col-lg-2", 0);
                $("#rg-container-main").switchClass("container", "container-fluid", cssChangeDelay, cssEasing, function () {
                    //变化完毕，加载功能
                    if (!isFast) {//不是快速启动，快速启动是刷新，刷新在显示顶级菜单之后不加载功能
                        showFun(rootI, -1);
                    }
                });
            });
        }
        else {
            //加载功能
            //变化完毕，加载功能
            if (!isFast) {//不是快速启动，快速启动是刷新，刷新在显示顶级菜单之后不加载功能
                showFun(rootI, -1);
            }
        }
    }
    //点击一级按钮生成导航栏
    showRootPosition(rootI);
}

function showFun(rootI, subI) {
    //if (subI == -1) {//一级菜单功能
    //    //一级功能的地址导航在显示菜单的地方生成，不在这里（显示功能）生成
    //    //设置当前参数
    //    subMenuIndex = subI;
    //    setCookie(cookiesPrefix + "subMenuId", menuJson[rootI].id);
    //    //更新面板高度
    //    changeMainPanelHeight(false);
    //    //加载功能
    //    if (menuJson[rootI].url != "" && menuJson[rootI].url != null) {//有路由地址
    //        //更新面板内容
    //        //TODO liuhui
    //        //loadContent(menuJson[rootI].children[subI].url);
    //    }
    //    else {//没有地址
    //        //更新面板内容
    //        $('#rg-container-fun').html('<h3>地址错误</h3>');
    //    }
    //}
    //else {
    //    if (subMenuIndex != subI) {//不是本功能
    //        //取消激活的sub导航的选中状态
    //        $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).removeClass("active", 400);
    //        //设置当前激活功能索引
    //        subMenuIndex = subI;
    //        setCookie(cookiesPrefix + "subMenuId", menuJson[rootI].children[subMenuIndex].id);
    //        //取消激活的sub导航的选中状态
    //        $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).addClass("active", 400);
    //    }
    //    else {//本功能
    //        return;
    //    }
    //    //设置位置导航
    //    if (menuJson[rootI].children[subI].EnglishTitle != null && menuJson[rootI].children[subI].EnglishTitle != "") {
    //        $('#rg-position').html('<li><a href="#" onclick="showSubMenu(0,false)">主页</a></li><li><a href="#" onclick="showSubMenu(' + rootI + ',false)">' + menuJson[rootI].text + '</a></li><li class="active">' + menuJson[rootI].children[subI].text + '<span>' + menuJson[rootI].children[subI].EnglishTitle + '</span></li>');
    //    }
    //    else {
    //        $('#rg-position').html('<li><a href="#" onclick="showSubMenu(0,false)">主页</a></li><li><a href="#" onclick="showSubMenu(' + rootI + ',false)">' + menuJson[rootI].text + '</a></li><li class="active">' + menuJson[rootI].children[subI].text + '</li>');
    //    }
    //    //更新面板高度
    //    changeMainPanelHeight(false);
    //    if (menuJson[rootI].children[subI].url != "" && menuJson[rootI].children[subI].url != null) {//有路由地址
    //        //更新面板内容
    //        //TODO liuhui
    //        //loadContent(menuJson[rootI].children[subI].url);
    //    }
    //    else {//没有地址
    //        //更新面板内容
    //        $('#rg-container-fun').html('<h3>地址错误</h3>');
    //    }
    //}
}

//一级功能显示地址栏
function showRootPosition(rootI) {//在点击一级菜单时使用，因为一级菜单会出现布局变化，布局变化号之后才加载功能，而地址导航变化要在点击按钮时。所以要分开处理
    //设置位置导航
    if (rootI == 0) {//主页
        $('#rg-position').html('<li class="active">主页</li>');
    }
    else {//非主页
        if (menuJson[rootI].EnglishTitle != null && menuJson[rootI].EnglishTitle != "") {
            $('#rg-position').html('<li><a href="#" onclick="showSubMenu(0,false)">主页</a></li><li class="active">' + menuJson[rootI].text + '<span>' + menuJson[rootI].EnglishTitle + '</span></li>');
        }
        else {
            $('#rg-position').html('<li><a href="#" onclick="showSubMenu(0,false)">主页</a></li><li class="active">' + menuJson[rootI].text + '</li>');
        }
    }
}

//写cookies
function setCookie(name, value) {
    //var Days = 30;
    //var exp = new Date();
    //exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
    //document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
}

//读取cookies 
function getCookie(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");

    if (arr = document.cookie.match(reg))

        return unescape(arr[2]);
    else
        return null;
}

//删除cookies 
function delCookie(name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval = getCookie(name);
    if (cval != null)
        document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString();
}

function changeMainPanelHeight(isFirst) {
    if (window.innerWidth >= 768) {//非手机版本
        //更新面板高度
        if (isFirst) {
            //设置面板高度
            $("#rg-container-fun").height(window.innerHeight - 165);
        }
        else {
            $("#rg-container-fun").height(window.innerHeight - 230);
        }
    }
}

window.onresize = function () {
    changeMainPanelHeight(false);
    //alert("改变大小");
    /*
     var s = "网页可见区域宽 ：" + document.body.clientWidth;
     s += "\r\n网页可见区域高：" + document.body.clientHeight;
     s += "\r\n网页可见区域高：" + document.body.offsetHeight + " (包括边线的宽)";
     s += "\r\n网页正文全文宽：" + document.body.scrollWidth;
     s += "\r\n网页正文全文高：" + document.body.scrollHeight;
     s += "\r\n网页被卷去的高：" + document.body.scrollTop;
     s += "\r\n网页被卷去的左：" + document.body.scrollLeft;
     s += "\r\n网页正文部分上：" + window.screenTop;
     s += "\r\n网页正文部分左：" + window.screenLeft;
     s += "\r\n屏幕分辨率的高：" + window.screen.height;
     s += "\r\n屏幕分辨率的宽：" + window.screen.width;
     s += "\r\n屏幕可用工作区高度：" + window.screen.availHeight;
     s += "\r\n屏幕可用工作区宽度：" + window.screen.availWidth;
     alert(s);*/
}

function JsonDateTimeToDateTime(jsondate) {
    if (jsondate == null) {
        return null;
    }
    var date = new Date(parseInt(jsondate.replace("/Date(", "").replace(")/", ""), 10));
    return getDateTime(date);
}

function getDateTime(date) {
    if (date == null) { return null; }
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    var hh = date.getHours();
    var mm = date.getMinutes();
    var ss = date.getSeconds();
    return year + "-" + ('00' + month).slice(-2) + "-" + ('00' + day).slice(-2) + " " + ('00' + hh).slice(-2) + ":" + ('00' + mm).slice(-2) + ":" + ('00' + ss).slice(-2);
}

function JSONDateToDate(jsondate) {
    if (jsondate == null) {
        return null;
    }
    var date = new Date(parseInt(jsondate.replace("/Date(", "").replace(")/", ""), 10));
    return getDate(date);
}

function JSONDateToDateTime(jsondate) {
    if (jsondate == null) {
        return null;
    }
    var date = new Date(parseInt(jsondate.replace("/Date(", "").replace(")/", ""), 10));
    return getDateTime(date);
}

function getDate(date) {
    if (date == null) { return null; }
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    return year + "-" + ('00' + month).slice(-2) + "-" + ('00' + day).slice(-2);
}

function getDateAddDays(date, days) {
    if (date == null) { return null; }
    var d = date;
    d.setDate(d.getDate() + days);
    var day = d.getDate();
    var month = d.getMonth() + 1;
    var year = d.getFullYear();
    return year + "-" + ('00' + month).slice(-2) + "-" + ('00' + day).slice(-2);
}

function ChangePassword() {
    $('#DlgModal').modal({
        backdrop: false,
        remote: rootPath + "Manage/ChangePassword"
    });
    //$('#xgmm').dialog({
    //    title: "更改密码",
    //    closed: false,
    //    href: rootPath + "Manage/ChangePassword"
    //});
}

function ConfirmChangePassword() {
    $('#fm').submit();
}

function onSuccess(data) {
    var result = $.parseJSON(data);
    if (result.rslt) {
        // $('#xgmm').dialog('close');
        $('#DlgModal').modal('hide');
        // $("#dg").datagrid("reload");
    }

    window.top.$.messager.show({
        title: '提示',
        msg: result.msg,
        height: 140,
        timeout: 10000,
        showType: 'slide'
    });
}

//function loadContent(url){
    //$('#rg-container-fun').html();
    //$.ajax({
    //    type: "GET",
    //    url: url,
    //    //dataType: "json",
    //    beforeSend: function(){
    //        $('#rg-container-fun').html("正在加载...");
    //    },
    //    success: function(json){
    //        $('#rg-container-fun').html(json);
    //        //if(json.status==1){
    //        //    $('#rg-container-fun').html(json);
    //        //}else{
    //        //    $('#rg-container-fun').html(json.msg);
    //        //    return false;
    //        //}
    //        //return false;
    //    }
    //});
    //return false;
//}