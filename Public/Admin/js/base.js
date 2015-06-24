$(document).ready(function () {

    $("#DlgModal").on("hidden.bs.modal", function () {
        $(this).removeData("bs.modal");
    });
    $("#DlgModal").on("loaded.bs.modal", function () {
        if ($(".modal-body").height() > ($("#DlgModal").height() - 300)) {
            //高度超标
            $('.modal-body').height($("#DlgModal").height() - 290);
        }
    });

    /**
     * message 弹出提示框
     * @type {{extraClasses: string, theme: string}}
     */
    Messenger.options = {
        extraClasses: 'messenger-fixed messenger-on-top',
        theme: 'block'
    }




// 加载导航
    var navStr = "";
    menuJson = $.parseJSON(result);
    //首页每个人都有，所以不需要权限判断，直接在这里加上
    var indexObj =
    {
        'id': "00",
        "fid": null,
        "text": "主页",
        "iconCls": "",
        "url": "",
        "children": ""
    };
    menuJson.unshift(indexObj);
    //生成nav菜单
    var i = 0;
    for (i = 1; i < menuJson.length; i++) {
        navStr += '<li id="nav-root-li-' + i + '"><a href="javascript:void(0)" onclick="showSubMenu(' + i + ',false)">' + menuJson[i].text + '</a></li>';
    }
    $('#nav-root-ul').html(navStr);

    //恢复或初始化subnav
    if (getCookie(cookiesPrefix + "rootMenuId") != null) {
        //有cookies记录显示的列表
        for (i = 0; i < menuJson.length; i++) {
            if (menuJson[i].id == getCookie(cookiesPrefix + "rootMenuId")) {
                break;
            }
        }
        if (i < menuJson.length) {
            //记录的cookies有匹配的
            showSubMenu(i, true);
        }
        else {
            //记录的cookies没有匹配的
            showSubMenu(0, true);
        }
    } else {
        //显示列表初始化
        showSubMenu(0, true);
    }

    //恢复或初始化功能
    if (menuJson[rootMenuIndex].children != null && menuJson[rootMenuIndex].children != "") {
        //当前显示的功能有下级功能。
        if (getCookie(cookiesPrefix + "subMenuId") != null) {
            //有cookies记录当前功能
            for (var i = 0; i < menuJson[rootMenuIndex].children.length; i++) {
                if (menuJson[rootMenuIndex].children[i].id == getCookie(cookiesPrefix + "subMenuId")) {
                    break;
                }
            }
            if (i < menuJson[rootMenuIndex].children.length) {
                //记录的cookies有匹配的
                showFun(rootMenuIndex, i);
            }
            else {
                //记录的cookies没有匹配的
                //打开默认功能
                showFun(rootMenuIndex, -1);
            }
        }
        else {
            //没有cookies记录
            //打开默认功能
            showFun(rootMenuIndex, -1);
        }
    }
    else {
        //没有cookies记录
        //打开默认功能
        showFun(rootMenuIndex, -1);
    }
});

function showSubMenu(rootI, isFast) {
    if (rootMenuIndex == rootI && subMenuIndex == -1) {//相同的功能
        return;
    }
    //subMenu的处理
    if (rootMenuIndex != rootI) {//点击的是不同的root功能
        if (rootMenuIndex != 0) {//之前不是首页（如果不是首页，也有下级功能，那就一定初始化过了）
            //取消选中样式
            $('#nav-root-li-' + rootMenuIndex).removeClass("active");
        }
        //修改参数记录
        rootMenuIndex = rootI;
        setCookie(cookiesPrefix + "rootMenuId", menuJson[rootMenuIndex].id);
        //设置菜单标题
        $('#rg-title-nav-sub').html(menuJson[rootI].text + "功能");
        //设置菜单
        var sunMenuStr = '';
        for (var i = 0; i < menuJson[rootI].children.length; i++) {
            sunMenuStr += '<a href="javascript:void(0)" class="list-group-item" id="nav-sub-a-' + rootI + '-' + i + '" onclick="showFun(' + rootI + ',' + i + ')">　' + menuJson[rootI].children[i].text + '</a>';
        }
        $('#rg-menu-nav-sub').html(sunMenuStr);
        //设置选中状态样式
        $('#nav-root-li-' + rootI).addClass("active");
    }
    else {//点击的是相同的root功能
        //取消subMenu的选中状态。现在显示的是相同的root不同的sub功能，点击root功能按钮只需要回到默认功能，取消sub按钮的激活状态，不需要更新菜单
        $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).removeClass("active", 400);
    }
    if (!isFast) {//不是快速启动，快速启动是刷新，刷新在显示顶级菜单之后不加载功能
        showFun(rootI, -1);
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
        changeMainPanelHeight();
        //加载功能
        if (menuJson[rootI].url != "" && menuJson[rootI].url != null) {//有路由地址
            //更新面板内容

                var target='/admin.php/' + menuJson[rootI].children[subI].url;
                contentload(target);
                $('#rg-container-fun').attr("href", '/admin.php/' + menuJson[rootI].children[subI].url)
        }
        else {//没有地址
            //更新面板内容
            $('#rg-container-fun').html('<div class="alert alert-danger" role="alert">地址解析错误！</div>');
        }
    }
    else {
        if (subMenuIndex != subI) {//不是本功能
            //取消激活的sub导航的选中状态
            $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).removeClass("active");
            //设置当前激活功能索引
            subMenuIndex = subI;
            setCookie(cookiesPrefix + "subMenuId", menuJson[rootI].children[subMenuIndex].id);
            //取消激活的sub导航的选中状态
            $('#nav-sub-a-' + rootMenuIndex + '-' + subMenuIndex).addClass("active");
        }
        else {//本功能
            return;
        }
        //设置位置导航
        if (menuJson[rootI].children[subI].EnglishTitle != null && menuJson[rootI].children[subI].EnglishTitle != "") {
            $('#rg-position').html('<li><a href="javascript:void(0)" onclick="showSubMenu(0,false)">主页</a></li><li><a href="javascript:void(0)" onclick="showSubMenu(' + rootI + ',false)">' + menuJson[rootI].text + '</a></li><li class="active">' + menuJson[rootI].children[subI].text + '<span>' + menuJson[rootI].children[subI].EnglishTitle + '</span></li>');
        }
        else {
            $('#rg-position').html('<li><a href="javascript:void(0)" onclick="showSubMenu(0,false)">主页</a></li><li><a href="javascript:void(0)" onclick="showSubMenu(' + rootI + ',false)">' + menuJson[rootI].text + '</a></li><li class="active">' + menuJson[rootI].children[subI].text + '</li>');
        }
        //更新面板高度
        changeMainPanelHeight();
        if (menuJson[rootI].children[subI].url != "" && menuJson[rootI].children[subI].url != null) {//有路由地址
            //更新面板内容
            var target='/admin.php/' + menuJson[rootI].children[subI].url;
            contentload(target);
            $('#rg-container-fun').attr("href", '/admin.php/' + menuJson[rootI].children[subI].url);
        }
        else {//没有地址
            //更新面板内容
            $('#rg-container-fun').html('<div class="alert alert-danger" role="alert">地址解析错误！</div>');
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
            $('#rg-position').html('<li><a href="javascript:void(0)" onclick="showSubMenu(0,false)">主页</a></li><li class="active">' + menuJson[rootI].text + '<span>' + menuJson[rootI].EnglishTitle + '</span></li>');
        }
        else {
            $('#rg-position').html('<li><a href="javascript:void(0)" onclick="showSubMenu(0,false)">主页</a></li><li class="active">' + menuJson[rootI].text + '</li>');
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

function changeMainPanelHeight() {
    if (window.innerWidth >= 768) {//非手机版本
        //更新面板高度
        $("#rg-container-fun").height(window.innerHeight - 174);
    }
}

window.onresize = function () {
    changeMainPanelHeight();
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









/**
 * @author liu hui
 * 给中间的div加载内容
 * @param result
 */
function onSuccess(result) {
    if (result.status) {
        $('#DlgModal').modal('hide');
        //更新内容
       var target= $('#rg-container-fun').attr("href")
        contentload(target);

        Messenger().post({
            message: result.info,
            type: 'success',
            showCloseButton: true,
            hideAfter: 2
        });
    } else {
        Messenger().post({
            message: result.info,
            type: 'error',
            showCloseButton: true,
            hideAfter: 2
        });
    }

}

/**
 * @author liu hui
 * 给中间的div加载内容
 * @param result
 */
function ajaxSuccess(result) {
    if (result.status) {
        //更新内容
        var target= $('#rg-container-fun').attr("href")
        contentload(target);

        Messenger().post({
            message: result.info,
            type: 'success',
            showCloseButton: true,
            hideAfter: 2
        });
    } else {
        Messenger().post({
            message: result.info,
            type: 'error',
            showCloseButton: true,
            hideAfter: 2
        });
    }

}



/**
 * @author liu hui
 * @description 将ThinkPHP的分页转换为 bootstrap分页
 * @param selector
 */
function initPagination(selector) {
    selector = selector || '.page';
    $(selector).each(function (i, o) {
        var html = '<ul class="pagination">';
        $(o).find('a,span').each(function (i2, o2) {
            var linkHtml = '';
            if ($(o2).is('a')) {
                linkHtml = '<a href="' + ($(o2).attr('href') || '#') + '">' + $(o2).text() + '</a>';
            } else if ($(o2).is('span')) {
                linkHtml = '<a>' + $(o2).text() + '</a>';
            }

            var css = '';
            if ($(o2).hasClass('current')) {
                css = ' class="active" ';
            }

            html += '<li' + css + '>' + linkHtml + '</li>';
        });

        html += '</ul>';
        $(o).html(html).fadeIn();
    });
}

/**
 * 更新面板内容
 */
function contentload(target){
    //更新面板内容
    $("#rg-container-fun").load(target,function (response, status, xhr) {

        if (status == "success") {

            $('#rg-container-fun').html(response);
        }
        else {
            $('#rg-container-fun').html("An error occured: <br/>" + xhr.status + " " + xhr.statusText)
        }


    });
}


 function baseJs(){
    //分页格式化
    initPagination('.page');


    //分页链接点击函数
    $('.pagination a').click(function () {
        var target;
        var that = this;
        if ((target = $(this).attr('href')) || (target = $(this).attr('url'))) {
            contentload(target);
        }

        return false;
    });


    //ajax get请求
    $('.ajax-get').click(function () {
        var target;
    //   var that = this;
        if ($(this).hasClass('confirm')) {
            if (!confirm('确认要执行该操作吗?')) {
                return false;
            }
        }
        if ((target = $(this).attr('href')) || (target = $(this).attr('url'))) {
            $.get(target).success(function (data) {
                ajaxSuccess(data);
            });
        }

        return false;
    });
}

function Save(Url,selector){
    $.ajax({
        type:'POST',
        url: Url,
        data:$('#'+selector).serialize(),
        dataType:'json',
        success:function(data) {
            onSuccess(data);
        },
        error : function() {
            alert("异常！");
        }
    })
}