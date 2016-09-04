$(function () {
    // Jeu
    var $timerContainer = $("[data-timer=container]");

    if (0 < $timerContainer.length) {
        var timeout     = $timerContainer.data("timeout");
        var countdown   = function (callback) {
            var i = timeout * 1000;

            timeoutInterval = setInterval(function () {
                var seconds         = Math.floor(i / 1000);
                seconds             = 10 > seconds ? "0" + seconds : seconds;
                var milliseconds    = i % 1000;
                milliseconds        = 10 > milliseconds ? "0" + milliseconds : milliseconds;
                var text            = seconds + "." + milliseconds + "s";

                $timerContainer.text(text);
                i = i - 10 || (clearInterval(timeoutInterval), callback());
            }, 10);
        };

        countdown(function () {
            $("[data-form]").submit();
        });

        $("[data-images]").removeClass("no-display");
    }

    var $answers = $("[data-answer]");

    if (0 < $answers.length) {
        $answers.SpatialNavigation()
            .focus(function() {
                $(this).addClass("hover");
            })
            .blur(function() {
                $(this).removeClass("hover");
            });

        $answers.on("mouseover", function () {
            $answers.removeClass("hover");
        });
    }

    // Game-over
    var $select = $("[data-select]");

    if (0 < $select.length) {
        $select.select();
    }
});
