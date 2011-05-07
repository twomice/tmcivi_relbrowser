var mouseX;
var mouseY;
var IE = false;

$(document).ready(function () {
    var windowHeight = getWindowHeight()
    graphHeight = graphHeight > windowHeight ? graphHeight : windowHeight;
    var windowWidth = getWindowWidth()
    if (hasNodes) {
        graphWidth = graphWidth > windowWidth ? graphWidth : windowWidth;
        $('#graph').css('height', graphHeight);
        $('#graph').css('width', graphWidth);
        $('body').addClass('grab');
        $('body').click(function (e) {
            return false
        });
        $('body').mousedown(function(e) {
            grab(e);
            return false;
        });

        $('body').mouseup(function() {
            drop();
            return false;
        });

    }
});

function grab(e) {
    if (window.event == undefined) {
        mouseX = e.pageX
        mouseY = e.pageY
    } else {
        IE = true;
        mouseX = window.event.clientX
        mouseY = window.event.clientY
    }

    $('body').removeClass('grab');
    $('body').addClass('grabbing');
    $('body').bind('mousemove', function(e) {
        drag(e);
    });
}

function drop() {
        $('body').removeClass('grabbing');
        $('body').addClass('grab');
        $('body').unbind('mousemove');
}

function drag(e){
    if (window.event == undefined) {
        var x = e.pageX
        var y = e.pageY
    } else {
        var x = window.event.clientX
        var y = window.event.clientY
    }


    if (x != mouseX || y != mouseY) {
        window.scrollBy(-(x - mouseX), -(y - mouseY));
        if (IE) {
            mouseX = x;
            mouseY = y;
        } else {
            mouseX = x-(x - mouseX);
            mouseY = y-(y - mouseY);
        }
    }
}


function getWindowWidth () {
    var myWidth = 0;
    if( typeof( window.innerWidth ) == 'number' ) {
        //Non-IE
        myWidth = window.innerWidth;
    } else if( document.documentElement &&  document.documentElement.clientWidth ) {
        //IE 6+ in 'standards compliant mode'
        myWidth = document.documentElement.clientWidth;
    } else if( document.body && document.body.clientWidth ) {
        //IE 4 compatible
        myWidth = document.body.clientWidth;
    }
    return myWidth;
}

function getWindowHeight () {
    var myHeight = 0;
    if( typeof( window.innerHeight ) == 'number' ) {
        //Non-IE
        myHeight = window.innerHeight;
    } else if( document.documentElement &&  document.documentElement.clientHeight ) {
        //IE 6+ in 'standards compliant mode'
        myHeight = document.documentElement.clientHeight;
    } else if( document.body && document.body.clientHeight ) {
        //IE 4 compatible
        myHeight = document.body.clientHeight;
    }
    return myHeight;
}

function submitNewMapRequest(contactid, maxsteps) {
    parent.window.location.href = base_url +"/civicrm/tm/form?tmref=viewRelationshipMap&cid="+ contactid +"&maxsteps="+ maxsteps
}