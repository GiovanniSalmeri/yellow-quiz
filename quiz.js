// Quiz extension, https://github.com/GiovanniSalmeri/yellow-quiz

"use strict";
document.addEventListener("DOMContentLoaded", function() {
    var t = document.getElementById('quiz-progresstext');
    var b = document.getElementById('quiz-progressbar');
    if (t) {
        var availSecs = t.getAttribute('data-time')*60;
        var secsLeft = availSecs;
        setInterval (function() {
            if (secsLeft >= 0) {
                var min = ~~(secsLeft/60);
                var sec = secsLeft%60;
                var w = 100 - (secsLeft*100 / availSecs);
                t.innerHTML = (min ? (min+' min') : '') + (sec ? ('&nbsp;'+sec+' s'): '') + '&nbsp;';
                b.style.width = w + '%';
                secsLeft--;
            } else {
                secsLeft = availSecs;
                document.getElementById('quiz-form').submit();
            }
        }, 1000);
    }
});
