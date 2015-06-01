var menuJson = "",
    rootMenuIndex = -1/*显示的菜单*/,
    subMenuIndex = -1/*运行的功能*/,
    haveSubMenu = 0,
    cssEasing = "easeOutBounce",//
    cssDelay = 500;

$(document).ready(function () {
    changeMainPanelHeight(true);
    // 加载导航
            result='' +
            '[{"id":"01","fid":null,"text":"商家","iconCls":null,"EnglishTitle":"System Manage","url":"/SysMgr/Default/Index",' +
            '"children":[{"id":"0101","fid":"01","text":"教师管理","EnglishTitle":"Teacher","url":"/SysMgr/Teachers/Index","iconCls":null},' +
            '{"id":"0102","fid":"01","text":"分校管理","EnglishTitle":"Branch","url":"/SysMgr/Branches/Index","iconCls":null},' +
            '{"id":"0103","fid":"01","text":"校区管理","EnglishTitle":"Campu","url":"/SysMgr/Campus/Index","iconCls":null}]},' +


            '{"id":"02","fid":null,"text":"用户","iconCls":null,"EnglishTitle":"Permission","url":"/PermissionMgr/Default/Index",' +
            '"children":[{"id":"0201","fid":"02","text":"权限分配","EnglishTitle":"Permission","url":"/PermissionMgr/Permissions/Index","iconCls":null},' +
            '{"id":"0202","fid":"02","text":"权限模板","EnglishTitle":"Permission Template","url":"/PermissionMgr/Tpl/Index","iconCls":null}]},' +


            '{"id":"03","fid":null,"text":"商品","iconCls":null,"EnglishTitle":"Basic Info","url":"/BasicInfo/Default/Index",' +
            '"children":[{"id":"0301","fid":"03","text":"字典类别","EnglishTitle":"Dictionary","url":"/BasicInfo/DataItems/List","iconCls":null},' +
            '{"id":"0302","fid":"03","text":"字典值","EnglishTitle":null,"url":"/BasicInfo/DataItemValues/Index","iconCls":null},' +
            '{"id":"0303","fid":"03","text":"客户级别","EnglishTitle":null,"url":"/BasicInfo/ClientLevels/Index","iconCls":null},' +
            '{"id":"0304","fid":"03","text":"上课效果","EnglishTitle":null,"url":"/BasicInfo/Effects/Index","iconCls":null}]},' +

            '{"id":"04","fid":null,"text":"系统","iconCls":null,"EnglishTitle":"Student","url":"/StudentMgr/Default/Index",' +
            '"children":[{"id":"0401","fid":"04","text":"学生信息","EnglishTitle":"Student Info","url":"/StudentMgr/Student/Index","iconCls":null},' +
            '{"id":"0402","fid":"04","text":"课程管理","EnglishTitle":null,"url":"/StudentMgr/StudentProduct/Index","iconCls":null}]},' +



            '{"id":"05","fid":null,"text":"产品管理","iconCls":null,"EnglishTitle":"Product","url":"/ProductMgr/Default/Index",' +
            '"children":[{"id":"0501","fid":"05","text":"产品信息","EnglishTitle":"Product Infomation","url":"/ProductMgr/Product/Index","iconCls":null}]},' +



            '{"id":"06","fid":null,"text":"个人绩效","iconCls":null,"EnglishTitle":"Enrollment","url":"/EnrollmentMgr/Default/Index",' +
            '"children":[{"id":"0601","fid":"06","text":"跟进记录","EnglishTitle":"Track","url":"/EnrollmentMgr/Tracks/Index","iconCls":null},' +
            '{"id":"0602","fid":"06","text":"跟进计划","EnglishTitle":"Plan","url":"/EnrollmentMgr/TrackPlans/Index","iconCls":null},' +
            '{"id":"0604","fid":"06","text":"跟进客户分配","EnglishTitle":null,"url":"/EnrollmentMgr/TrackStudents/Index","iconCls":null},' +
            '{"id":"0605","fid":"06","text":"统计表","EnglishTitle":null,"url":"/EnrollmentMgr/StatisticalList/Index","iconCls":null},' +
            '{"id":"0606","fid":"06","text":"统计图","EnglishTitle":null,"url":"/EnrollmentMgr/StatisticalChart/Index","iconCls":null}]}]';
            var navStr = "";
            menuJson = $.parseJSON(result);
            //首页每个人都有，所以不需要权限判断，直接在这里加上
            var indexObj =
            {
                'id': "00",
                "fid": null,
                "text": "主页",
                "iconCls": "",
                "url": "/AppHome/Entrance",
                //"url": "Entrance",
                "children": ""
            };
            menuJson.unshift(indexObj);
            //生成nav菜单
            var i = 0
            for (i = 1; i < menuJson.length; i++) {
                navStr += '<li id="nav-root-li-' + i + '"><a href=\"#\" onclick="showSubMenu(' + i + ',false)">' + menuJson[i].text + '</a></li>';
            }
            $('#nav-root-ul').html(navStr);
            //恢复或初始化subnav
            if (getCookie(cookiesPrefix + "rootMenuId") != null) {//有cookies记录显示的列表

                for (i = 0; i < menuJson.length; i++) {
                    if (menuJson[i].id == getCookie(cookiesPrefix + "rootMenuId")) {
                        break;
                    }
                }
                if (i < menuJson.length) {//记录的cookies有匹配的
                    showSubMenu(i, true);
                }
                else {//记录的cookies没有匹配的
                    showSubMenu(0, true);
                }
            }
            else {//显示列表初始化
                showSubMenu(0, true);
            }

            //恢复或初始化功能
            if (menuJson[rootMenuIndex].children != null && menuJson[rootMenuIndex].children != "") {//当前显示的功能有下级功能。
                if (getCookie(cookiesPrefix + "subMenuId") != null) {//有cookies记录当前功能
                    for (var i = 0; i < menuJson[rootMenuIndex].children.length; i++) {
                        if (menuJson[rootMenuIndex].children[i].id == getCookie(cookiesPrefix + "subMenuId")) {
                            break;
                        }
                    }
                    if (i < menuJson[rootMenuIndex].children.length) {//记录的cookies有匹配的
                        showFun(rootMenuIndex, i);
                    }
                    else {//记录的cookies没有匹配的
                        //打开默认功能
                        showFun(rootMenuIndex, -1);
                    }
                }
                else {//没有cookies记录
                    //打开默认功能
                    showFun(rootMenuIndex, -1);
                }
            }
            else {//没有cookies记录
                //打开默认功能
                showFun(rootMenuIndex, -1);
            }
});

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
                sunMenuStr += '<a href="#" class="list-group-item" id="nav-sub-a-' + rootI + '-' + i + '" onclick="showFun(' + rootI + ',' + i + ')">　' + menuJson[rootI].children[i].text + '</a>';
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
    if (subI == -1) {//一级菜单功能
        //一级功能的地址导航在显示菜单的地方生成，不在这里（显示功能）生成
        //设置当前参数
        subMenuIndex = subI;
        setCookie(cookiesPrefix + "subMenuId", menuJson[rootI].id);
        //更新面板高度
        changeMainPanelHeight(false);
        //加载功能
        if (menuJson[rootI].url != "" && menuJson[rootI].url != null) {//有路由地址
            //更新面板内容
            //TODO liuhui
            $('#rg-container-fun').html({
                href: menuJson[rootI].url
            });
        }
        else {//没有地址
            //更新面板内容
            $('#rg-container-fun').html('<h3>地址错误</h3>');
        }
    }
    else {
        if (subMenuIndex != subI) {//不是本功能
            //取消激活的sub导航的选中状态
            $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).removeClass("active", 400);
            //设置当前激活功能索引
            subMenuIndex = subI;
            setCookie(cookiesPrefix + "subMenuId", menuJson[rootI].children[subMenuIndex].id);
            //取消激活的sub导航的选中状态
            $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).addClass("active", 400);
        }
        else {//本功能
            return;
        }
        //设置位置导航
        if (menuJson[rootI].children[subI].EnglishTitle != null && menuJson[rootI].children[subI].EnglishTitle != "") {
            $('#rg-position').html('<li><a href="#" onclick="showSubMenu(0,false)">主页</a></li><li><a href="#" onclick="showSubMenu(' + rootI + ',false)">' + menuJson[rootI].text + '</a></li><li class="active">' + menuJson[rootI].children[subI].text + '<span>' + menuJson[rootI].children[subI].EnglishTitle + '</span></li>');
        }
        else {
            $('#rg-position').html('<li><a href="#" onclick="showSubMenu(0,false)">主页</a></li><li><a href="#" onclick="showSubMenu(' + rootI + ',false)">' + menuJson[rootI].text + '</a></li><li class="active">' + menuJson[rootI].children[subI].text + '</li>');
        }
        //更新面板高度
        changeMainPanelHeight(false);
        if (menuJson[rootI].children[subI].url != "" && menuJson[rootI].children[subI].url != null) {//有路由地址
            //更新面板内容
            //TODO liuhui
            $('#rg-container-fun').html({
                href: menuJson[rootI].children[subI].url,
                onLoadError: function () {
                    $('#rg-container-fun').html('<h3>出错了......</h3>');
                    // alert("错误");
                }
            });
        }
        else {//没有地址
            //更新面板内容
            $('#rg-container-fun').html('<h3>地址错误</h3>');
        }
    }
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
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
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