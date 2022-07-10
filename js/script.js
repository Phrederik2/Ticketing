function addEventListener(event) {
  $("[Form-Action-" + event + "]").on(event, function () {
    launchEvent(this);
  });
}

function launchEvent(item) {
  if ($(item).attr("LinkOption") !== undefined) {
    var nameO = $(item).attr("LinkOption");
    $('[name="' + nameO + '"]').attr("value", $(item).attr("Action"));
  }

  if ($(item).attr("LinkForm") !== undefined) {
    var nameF = $(item).attr("LinkForm");
    $('[name="' + nameF + '"]').submit();
  }
}

function addLink(event) {
  $(event).on("click", function () {
    if ($(this).attr("href") !== undefined) {
      href = $(this).attr("href");
      target = "_self";

      if ($(this).attr("target") !== undefined) {
        target = $(this).attr("target");
      }

      window.open(href, target);
    }
  });
}

addEventListener("click");
addEventListener("change");
addLink('input[type = "button"]');

$(function () {
  // $.switcher();
  $.switcher(".ONOFF");
});

/*$( function() {
    $( ".tabs" ).tabs();
 } );*/

$("textarea[edit=true]").trumbowyg({
  resetCss: true,
  removeformatPasted: true,
  semantic: false,
  btns: [
    /* ['viewHTML'],*/
    ["undo", "redo"],
    ["custom"],
    ["formatting"],
    ["fontfamily"],
    ["fontsize"],
    ["strong", "em", "del", "underline"],
    ["foreColor", "backColor"],
    ["link"],
    ["insertImage"],
    ["preformatted"],
    ["justifyLeft", "justifyCenter", "justifyRight", "justifyFull"],
    ["table"],
    ["unorderedList", "orderedList"],
    ["horizontalRule"],
    ["removeformat"],
    /* ['historyUndo', 'historyRedo'],*/
    ["fullscreen"],
  ],
});

/*  $('.tabs').notice({
        text: "Info message",
        type: "Error",
        canAutoHide: false,
        holdup: "5000"
    });*/

/*
$('textarea[edit=true]').closest(".trumbowyg-box").css("min-height", "500px");
$('textarea[edit=true]').prev(".trumbowyg-editor").css("min-height", "500px");
$('textarea[edit=true]').css("min-height", "500px");*/
