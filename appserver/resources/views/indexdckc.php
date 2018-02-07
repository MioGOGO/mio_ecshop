<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title></title>
    <script>/**
 * Created by levin on 2017/7/10.
 */
;(function(win, lib) {
    var doc = win.document;
    var docEl = doc.documentElement;
    var metaEl = doc.querySelector('meta[name="viewport"]');
    var flexibleEl = doc.querySelector('meta[name="flexible"]');
    var os = '';
    var dpr = 0;
    var scale = 0;
    var tid;
    var wh;
    var flexible = lib.flexible || (lib.flexible = {});

  if (metaEl) {
      console.warn('将根据已有的meta标签来设置缩放比例');
      var match = metaEl.getAttribute('content').match(/initial\-scale=([\d\.]+)/);
      if (match) {
          scale = parseFloat(match[1]);
          dpr = parseInt(1 / scale);
      }
  } else if (flexibleEl) {
      var content = flexibleEl.getAttribute('content');
      if (content) {
          var initialDpr = content.match(/initial\-dpr=([\d\.]+)/);
          var maximumDpr = content.match(/maximum\-dpr=([\d\.]+)/);
          if (initialDpr) {
              dpr = parseFloat(initialDpr[1]);
              scale = parseFloat((1 / dpr).toFixed(2));
          }
          if (maximumDpr) {
              dpr = parseFloat(maximumDpr[1]);
              scale = parseFloat((1 / dpr).toFixed(2));
          }
      }
  }

  if (!dpr && !scale) {
      var isIPhone = win.navigator.appVersion.match(/iphone/gi);
      var isAndroid = win.navigator.appVersion.match(/android/gi);
      var devicePixelRatio = win.devicePixelRatio;
      os = isIPhone? 'ios' : 'android';
      if (devicePixelRatio >= 3 && (!dpr || dpr >= 3)) {
          dpr = 3;
      } else if ((devicePixelRatio > 2 && devicePixelRatio < 3) && (!dpr || ( dpr > 2 && dpr < 3))){
          dpr = 3;
      }else if ((devicePixelRatio > 1 && devicePixelRatio <= 2) && (!dpr || ( dpr > 1 && dpr <= 2))){
          dpr = 2;
      } else {
          dpr = 1;
      }
      scale = 1 / dpr;
  }

  docEl.setAttribute('data-dpr', dpr);
  docEl.setAttribute('data-os', os);

  if (!metaEl) {
      metaEl = doc.createElement('meta');
      metaEl.setAttribute('name', 'viewport');
      metaEl.setAttribute('content', 'width=device-width,' + 'initial-scale=' + scale + ', maximum-scale=' + scale + ', minimum-scale=' + scale + ', user-scalable=no');
      if (docEl.firstElementChild) {
          docEl.firstElementChild.appendChild(metaEl);
      } else {
          var wrap = doc.createElement('div');
          wrap.appendChild(metaEl);
          doc.write(wrap.innerHTML);
      }
  }

  function refreshRem(){
      // 扭蛋机页一屏适配华为手机，获取宽高比
      if (navigator.userAgent.indexOf('Nexus') > 0 || navigator.userAgent.indexOf('huawei') > 0 || navigator.userAgent.indexOf('HONOR') > 0 || navigator.userAgent.indexOf('HUAWEI') > 0) {
          var iphone6p = 414/736;
          var width = window.innerWidth;
          var height = window.innerHeight;
          if(width/height > iphone6p && width < height){
              rem = width / 11.2;
              wh = 'rem'
          docEl.setAttribute('data-wh', wh);
          docEl.style.fontSize = rem + 'px';
          flexible.rem = win.rem = rem;
        }
          return;
      } else if (navigator.userAgent.indexOf('Nexus') > 0){

      }
      // 适配end
      var width = docEl.getBoundingClientRect().width;
      if (width / dpr > 540) {
          width = 540 * dpr;
      }
      var rem = width / 10;

      docEl.style.fontSize = rem + 'px';
      flexible.rem = win.rem = rem;
  }
  win.addEventListener('resize', function() {
      clearTimeout(tid);
      tid = setTimeout(refreshRem, 300);
  }, false);
  win.addEventListener('pageshow', function(e) {
      if (e.persisted) {
          clearTimeout(tid);
          tid = setTimeout(refreshRem, 300);
      }
  }, false);

  if (doc.readyState === 'complete') {
      doc.body.style.fontSize = 12 * dpr + 'px';
  } else {
      doc.addEventListener('DOMContentLoaded', function(e) {
          doc.body.style.fontSize = 12 * dpr + 'px';
      }, false);
  }


  refreshRem();

  flexible.dpr = win.dpr = dpr;
  flexible.refreshRem = refreshRem;

  flexible.rem2px = function(d) {
      var val = parseFloat(d) * this.rem;
      if (typeof d === 'string' && d.match(/rem$/)) {
          val += 'px';
      }
      return val;
  }
  flexible.px2rem = function(d) {
      var val = parseFloat(d) / this.rem;
      if (typeof d === 'string' && d.match(/px$/)) {
          val += 'rem';
      }
      return val;
  }

})(window, window['lib'] || (window['lib'] = {}));
</script>
    <script>
window.pageData ='<?php echo $pageData?>'
</script>
<link href="/static/css/app.032274e914b23a3863edbb759b0ebaff.css" rel="stylesheet"></head>
    <body>
    <div id="app"></div>
    <!-- built files will be auto injected -->
    <script type="text/javascript" charset="utf-8" src="https://webapi.amap.com/maps?v=1.3&key=84434c79220e778cfcb0dff329af7f7b"></script>
<script type="text/javascript" src="/static/js/manifest.83c4e7521d1e99f70b08.js"></script><script type="text/javascript" src="/static/js/vendor.4514bbd0cea951be88e7.js"></script><script type="text/javascript" src="/static/js/app.0d645364665ed02d6aae.js"></script></body>
</html>
