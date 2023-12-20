<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{env('APP_NAME')}}</title>

    <!-- Fonts -->
    <script src=" https://cdn.jsdelivr.net/npm/browser-scss@1.0.3/dist/browser-scss.min.js "></script>
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        body {
          background: #000;
      }

      #YakindaYazi {
          color: green;
          /*color: #8002f5;*/
          font-family: Monaco, monospace;
          font-size: 24px;
          width: 100%;
          text-align: center;
          position: absolute;
          top: 45%;
          left: 0;
          animation: 120ms infinite normal glitch;
      }

      span {
          animation: 1.5s infinite normal imleç;
      }

      ::-moz-selection {
          background: #7021d2;
          color: #fff;
      }

      ::selection {
          background: #7021d2;
          color: #fff;
      }

      @keyframes glitch {
          0% {
            opacity: 0;
            left: 0;
        }
        40%,
        80% {
            opacity: 1;
            left: -2px;
        }
    }

    @keyframes imleç {
      0% {
        opacity: 0;
        left: 0;
    }
    40% {
        opacity: 0;
        left: -2px;
    }
    80% {
        opacity: 1;
        left: -2px;
    }
}

</style>

<style>
    body {
        font-family: 'Nunito', sans-serif;
    }
</style>
</head>
<body oncontextmenu="return false" onselectstart="return false" ondragstart="return false">
    <div id="YakindaYazi">¦ ¦ ¦ <span style="color:black">¦ ¦ ¦ ¦ ¦ ¦ ¦ ¦ ¦ ¦ </span>100%
      <br>&gt; Site Sirojul Muhtadin Api Server&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <br>&gt; Sirojul Muhtadin  <span id="imleç">¦</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  </div>
</body>
</html>
