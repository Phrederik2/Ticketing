# jQuery.notice.js
>A simple jQuery notice

## Screenshot

<img src="https://image.ibb.co/c4eQC5/image.png" alt="image" border="0">

[Live preview](https://senya-g.github.io/jQuery.notice.js/)

## Usage
````html
<link rel="stylesheet" type="text/css" href="notice.css" />
  
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="jquery.notice.js"></script>
````
````js
$.notice();
````
````js
$( "#demo-error" ).click(function() {
  $.notice({
    text: "Info message",
    type: "info"
  });
});
````

## API

| Name         | Type    | Default | Description |
| ------------ | ------- | ------- | ----------- |
| text | string | `'Lorem ipsum.'` | The text of the notification. |
| type | string | `'info'` | Style notifications (`'success'`, `'error'`, `'warning'`, `'info'`). |
| canAutoHide | boolean | `true` | Auto hide notification after the appearance. |
| holdup | string | ``2500`` | How much time must elapse in milliseconds to hide the notification. |
| fadeTime | string | ``500`` | Fade animation time. |
| canFadeHover | boolean | `true` | Fade when you hover on the notification. |
| hasCloseBtn | boolean | `true` | If vertical is `true`, the slider will be vertical. |
| canCloseClick | boolean | `false` | Display a button to close the notification. |
| position | string | `'top-right'` | The position of notifications on screen (`'top-right'`, `'top-left'`, `'bottom-right'`, `'bottom-left'` |
| zIndex | string | `'9999'` | Notification z-index |
| custom | string | `''` | The class name for custom style |

## License

`jQuery.notice.js` is released under the MIT License.
